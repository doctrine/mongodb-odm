<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use BackedEnum;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\ODM\MongoDB\Utility\SortHelper;
use Doctrine\Persistence\Mapping\MappingException;
use MongoDB\BSON\ObjectId;
use SortDirection;

use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function assert;
use function count;
use function explode;
use function get_object_vars;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function str_contains;

/**
 * CriteriaPreparer translates PHP field names and values used in find()/Repository
 * criteria into the field names and BSON representations expected by MongoDB.
 *
 * @internal
 *
 * @template T of object = object
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @phpstan-import-type SortMeta from Sort
 * @phpstan-import-type SortShape from Sort
 */
final class CriteriaPreparer
{
    private CriteriaMerger $cm;

    /** @phpstan-param ClassMetadata<T> $class */
    public function __construct(
        private DocumentManager $dm,
        private PersistenceBuilder $pb,
        private ClassMetadata $class,
        ?CriteriaMerger $cm = null,
    ) {
        $this->cm = $cm ?? new CriteriaMerger();
    }

    /**
     * Prepare a projection array by converting keys, which are PHP property
     * names, to MongoDB field names.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public function prepareProjection(array $fields): array
    {
        $preparedFields = [];

        foreach ($fields as $key => $value) {
            $preparedFields[$this->prepareFieldName($key)] = $value;
        }

        return $preparedFields;
    }

    /**
     * Prepare a sort specification array by converting keys to MongoDB field
     * names and changing direction strings to int.
     *
     * @param array<string, int|string|SortDirection|array<string, string>> $fields
     * @param list<string>                                                  $allowedMetaSort
     * @phpstan-param SortShape $fields
     *
     * @phpstan-return array<string, -1|1|SortMeta>
     *
     * @throws \InvalidArgumentException if a sort direction is invalid.
     */
    public function prepareSort(array $fields, array $allowedMetaSort = []): array
    {
        $sortFields = [];

        foreach (SortHelper::normalizeSortDirections($fields, $allowedMetaSort) as $key => $value) {
            // Field names that are integer-like strings become integer array keys in PHP
            $sortFields[$this->prepareFieldName((string) $key)] = $value;
        }

        return $sortFields;
    }

    /**
     * Prepare a mongodb field name and convert the PHP property names to
     * MongoDB field names.
     */
    public function prepareFieldName(string $fieldName): string
    {
        $fieldNames = $this->prepareQueryElement($fieldName, null, null, false);

        return $fieldNames[0][0];
    }

    /**
     * Adds discriminator criteria to an already-prepared query.
     *
     * If the class we're querying has a discriminator field set, we add all
     * possible discriminator values to the query. The list of possible
     * discriminator values is based on the discriminatorValue of the class
     * itself as well as those of all its subclasses.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called before
     * {@link CriteriaPreparer::addFilterToPreparedQuery()}.
     *
     * @param array<string, mixed> $preparedQuery
     *
     * @return array<string, mixed>
     */
    public function addDiscriminatorToPreparedQuery(array $preparedQuery): array
    {
        if ($this->class->discriminatorField === null || isset($preparedQuery[$this->class->discriminatorField])) {
            return $preparedQuery;
        }

        $discriminatorValues = $this->getClassDiscriminatorValues($this->class);

        if ($discriminatorValues === []) {
            return $preparedQuery;
        }

        if (count($discriminatorValues) === 1) {
            $preparedQuery[$this->class->discriminatorField] = $discriminatorValues[0];
        } else {
            $preparedQuery[$this->class->discriminatorField] = ['$in' => $discriminatorValues];
        }

        return $preparedQuery;
    }

    /**
     * Adds filter criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called after
     * {@link CriteriaPreparer::addDiscriminatorToPreparedQuery()}.
     *
     * @param array<string, mixed> $preparedQuery
     *
     * @return array<string, mixed>
     */
    public function addFilterToPreparedQuery(array $preparedQuery): array
    {
        /* If filter criteria exists for this class, prepare it and merge
         * over the existing query.
         */
        $filterCriteria = $this->dm->getFilterCollection()->getFilterCriteria($this->class);
        if ($filterCriteria) {
            $preparedQuery = $this->cm->merge($preparedQuery, $this->prepareQueryOrNewObj($filterCriteria));
        }

        return $preparedQuery;
    }

    /**
     * Prepares the query criteria or new document object.
     *
     * PHP field names and types will be converted to those used by MongoDB.
     *
     * @param array<string|int, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function prepareQueryOrNewObj(array $query, bool $isNewObj = false): array
    {
        $preparedQuery = [];

        foreach ($query as $field => $value) {
            $field = (string) $field;

            // Recursively prepare logical query clauses, treating each value as a separate query element
            if (in_array($field, ['$and', '$or', '$nor'], true) && is_array($value)) {
                $preparedQuery[$field] = array_map(
                    fn ($v) => $this->prepareQueryOrNewObj($v, $isNewObj),
                    $value,
                );

                continue;
            }

            // Recursively prepare nested operators, treating the value as a single query element
            if (isset($field[0]) && $field[0] === '$' && is_array($value)) {
                $preparedQuery[$field] = $this->prepareQueryOrNewObj($value, $isNewObj);

                continue;
            }

            // Prepare a single query element. This may produce multiple queries (e.g. for references)
            $preparedQueryElements = $this->prepareQueryElement($field, $value, null, true, $isNewObj);
            foreach ($preparedQueryElements as [$preparedKey, $preparedValue]) {
                $preparedQuery[$preparedKey] = $preparedValue;
            }
        }

        return $preparedQuery;
    }

    /**
     * @phpstan-param FieldMapping $mapping
     *
     * @phpstan-return array<array{
     *     string,
     *     string|ObjectId|array<string, mixed>
     * }>
     */
    public function prepareReference(string $fieldName, object $value, array $mapping, bool $inNewObj): array
    {
        $reference = $this->dm->createReference($value, $mapping);

        // If a reference is stored as an identifier, we can always match it
        // directly. For ReferenceMany, multi-key indexes are used to match
        // array elements
        if ($inNewObj || $mapping['storeAs'] === ClassMetadata::REFERENCE_STORE_AS_ID) {
            return [[$fieldName, $reference]];
        }

        // For other ReferenceMany fields, we need to use $elemMatch to find a
        // single array element that matches all fields
        if ($mapping['type'] === ClassMetadata::MANY) {
            return [[$fieldName, ['$elemMatch' => $reference]]];
        }

        // For ReferenceOne fields, we can use multiple conditions on individual
        // fields, prefixed with the field name of the reference
        return array_map(
            static fn ($key) => [$fieldName . '.' . $key, $reference[$key]],
            array_keys($reference),
        );
    }

    /**
     * Converts a single value to its database representation based on the mapping type if possible.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function convertToDatabaseValue(string $fieldName, $value, ?ClassMetadata $class = null)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($k === '$exists' || $k === '$type' || $k === '$currentDate') {
                    continue;
                }

                $value[$k] = $this->convertToDatabaseValue($fieldName, $v, $class);
            }

            return $value;
        }

        if (! $class || ! $class->hasField($fieldName)) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            return $this->dm->getTypeGuesser()->convertToDatabaseValue($value);
        }

        $mapping  = $class->fieldMappings[$fieldName];
        $typeName = $mapping['type'];

        if (! empty($mapping['reference']) || ! empty($mapping['embedded'])) {
            return $value;
        }

        if ($value instanceof BackedEnum && isset($mapping['enumType'])) {
            $value = $value->value;
        }

        if (in_array($typeName, ['collection', 'hash'])) {
            return $value;
        }

        return $class->getFieldType($mapping['fieldName'])->convertToDatabaseValue($value);
    }

    private function prepareQueryReference(mixed $value, ClassMetadata $class): mixed
    {
        if (
            // Scalar values are prepared immediately
            ! is_array($value)
            // Objects without operators can be prepared immediately
            || ! $this->hasQueryOperators($value)
            // Objects with DBRef fields can be prepared immediately
            || $this->hasDBRefFields($value)
        ) {
            return $class->getDatabaseIdentifierValue($value);
        }

        return $this->prepareQueryExpression($value, $class);
    }

    /**
     * Prepares a query value and converts the PHP value to the database value
     * if it is an identifier.
     *
     * It also handles converting $fieldName to the database name if they are
     * different.
     *
     * @param mixed $value
     *
     * @return array<array{string, mixed}> Returns an array of tuples containing the prepared field name and value
     */
    private function prepareQueryElement(string $originalFieldName, $value = null, ?ClassMetadata $class = null, bool $prepareValue = true, bool $inNewObj = false, string $fieldNamePrefix = ''): array
    {
        $class   ??= $this->class;
        $fieldName = $fieldNamePrefix . $originalFieldName;

        // Process identifier fields
        if (($class->hasField($originalFieldName) && $class->isIdentifier($originalFieldName)) || $originalFieldName === '_id') {
            $fieldName = $fieldNamePrefix . '_id';

            return [[$fieldName, $prepareValue ? $this->prepareQueryReference($value, $class) : $value]];
        }

        // Process all non-identifier fields by translating field names
        if ($class->hasField($originalFieldName)) {
            $mapping   = $class->fieldMappings[$originalFieldName];
            $fieldName = $fieldNamePrefix . $mapping['name'];

            if (! $prepareValue) {
                return [[$fieldName, $value]];
            }

            // Prepare mapped, embedded objects
            if (
                ! empty($mapping['embedded']) && is_object($value) &&
                ! $this->dm->getMetadataFactory()->isTransient($value::class)
            ) {
                return [[$fieldName, $this->pb->prepareEmbeddedDocumentValue($mapping, $value)]];
            }

            if (! empty($mapping['reference']) && is_object($value) && ! ($value instanceof ObjectId)) {
                try {
                    return $this->prepareReference($fieldName, $value, $mapping, $inNewObj);
                } catch (MappingException) {
                    // do nothing in case passed object is not mapped document
                }
            }

            // No further preparation unless we're dealing with a simple reference
            if (empty($mapping['reference']) || $mapping['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID || empty((array) $value)) {
                return [[$fieldName, $this->convertToDatabaseValue($originalFieldName, $value, $class)]];
            }

            // Additional preparation for one or more simple reference values
            $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if (! is_array($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if (! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $targetClass)]];
        }

        // No processing for unmapped, non-identifier, non-dotted field names
        if (! str_contains($originalFieldName, '.')) {
            return [[$fieldName, $prepareValue ? $this->convertToDatabaseValue($originalFieldName, $value, $class) : $value]];
        }

        /* Process "fieldName.objectProperty" queries (on arrays or objects).
         *
         * We can limit parsing here, since at most three segments are
         * significant: "fieldName.objectProperty" with an optional index or key
         * for collections stored as either BSON arrays or objects.
         */
        $fieldNameParts = explode('.', $originalFieldName, 4);
        $partCount      = count($fieldNameParts);
        assert($partCount >= 2);

        // No further processing for unmapped fields
        if (! $class->hasField($fieldNameParts[0])) {
            return [[$fieldName, $prepareValue ? $this->convertToDatabaseValue($fieldNameParts[0], $value, $class) : $value]];
        }

        $mapping   = $class->fieldMappings[$fieldNameParts[0]];
        $fieldName = $fieldNamePrefix . $mapping['name'] . '.' . implode('.', array_slice($fieldNameParts, 1));

        // Hash and raw fields will not be prepared beyond the field name
        if ($mapping['type'] === Type::HASH || $mapping['type'] === Type::RAW) {
            return [[$fieldName, $value]];
        }

        if (isset($mapping['targetDocument'])) {
            // For associations with a targetDocument (i.e. embedded or reference), get the class metadata for the target document
            $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        } elseif (is_object($value) && ! $this->dm->getMetadataFactory()->isTransient($value::class)) {
            // For associations without a targetDocument, try to infer the class metadata from the object
            $targetClass = $this->dm->getClassMetadata($value::class);
        } else {
            // Without a target document, no further processing is possible
            return [[$fieldName, $prepareValue ? $this->convertToDatabaseValue($fieldNameParts[0], $value) : $value]];
        }

        // Don't recurse for references in queries. Instead, prepare them directly
        if (! $inNewObj && ! empty($mapping['reference'])) {
            // First part is the name of the reference
            // Second part is either a positional operator, index/key, or the name of a field
            // Third part (if any) is the name of a field
            // That means, we can implode all field parts except the first as the next field name
            if ($fieldNameParts[1] === '$') {
                assert($partCount >= 3);
                $objectProperty  = $fieldNameParts[2];
                $referencePrefix = $fieldNamePrefix . $mapping['name'] . '.$';
            } else {
                $objectProperty  = $fieldNameParts[1];
                $referencePrefix = $fieldNamePrefix . $mapping['name'];
            }

            if ($targetClass->hasField($objectProperty) && $targetClass->isIdentifier($objectProperty)) {
                $fieldName = ClassMetadata::getReferenceFieldName($mapping['storeAs'], $referencePrefix);

                return [[$fieldName, $prepareValue ? $this->prepareQueryReference($value, $targetClass) : $value]];
            }

            return [[$fieldName, $prepareValue ? $this->convertToDatabaseValue($objectProperty, $value, $targetClass) : $value]];
        }

        /*
         * 1 element: impossible (because of the dot)
         * 2 elements: fieldName.objectProperty, fieldName.<index>, or fieldName.$. For EmbedMany and ReferenceMany, treat the second element as index if $inNewObj is true and convert the value. Otherwise, recurse.
         * 3+ elements: fieldname.foo.bar, fieldName.<index>.foo, or fieldName.$.foo. For EmbedMany and ReferenceMany, treat the second element as index, and recurse into the third element. Otherwise, recurse with the second element as field name.
         */
        if ($mapping['type'] === ClassMetadata::MANY) {
            if ($inNewObj || CollectionHelper::isHash($mapping['strategy'])) {
                // When there are only two segments in a hash or when serialising a new object, we seem to be replacing an entire element. Don't recurse, just convert the value.
                if ($partCount === 2) {
                    // In order to prepare the embedded document value, we need to recurse with the original field name, then append the second segment
                    $prepared = $this->prepareQueryElement(
                        $mapping['name'],
                        $value,
                        $targetClass,
                        $prepareValue,
                        $inNewObj,
                        $fieldNamePrefix,
                    );

                    $preparedFieldName = $prepared[0][0];
                    $preparedValue     = $prepared[0][1];

                    return [[$preparedFieldName . '.' . $fieldNameParts[1], $preparedValue]];
                }

                // When there are more than two segments, treat the second segment (index/key/positional operator) as part of the field name and recurse into the rest
                $newPrefix    = $fieldNamePrefix . $mapping['name'] . '.' . $fieldNameParts[1] . '.';
                $newFieldName = implode('.', array_slice($fieldNameParts, 2));
            } else {
                // When serializing a query, the second segment is a positional operator ($), a numeric index for collections, or anything else for a hash.
                $newPrefix    = $fieldNamePrefix . $mapping['name'] . '.';
                $newFieldName = implode('.', array_slice($fieldNameParts, 1));
            }

            return $this->prepareQueryElement(
                $newFieldName,
                $value,
                $targetClass,
                $prepareValue,
                $inNewObj,
                $newPrefix,
            );
        }

        // For everything else, recurse with the first segment as field name and the target document class
        return $this->prepareQueryElement(
            implode('.', array_slice($fieldNameParts, 1)),
            $value,
            $targetClass,
            $prepareValue,
            $inNewObj,
            $fieldNamePrefix . $mapping['name'] . '.',
        );
    }

    /**
     * @param array<string, mixed> $expression
     *
     * @return array<string, mixed>
     */
    private function prepareQueryExpression(array $expression, ClassMetadata $class): array
    {
        foreach ($expression as $k => $v) {
            // Ignore query operators whose arguments need no type conversion
            if (in_array($k, ['$exists', '$type', '$mod', '$size'])) {
                continue;
            }

            // Process query operators whose argument arrays need type conversion
            if (in_array($k, ['$in', '$nin', '$all']) && is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($v2 instanceof $class->name) {
                        // If a value in a query is a target document, e.g. ['referenceField' => $targetDocument],
                        // retreive id from target document and convert this id using it's type
                        $expression[$k][$k2] = $class->getDatabaseIdentifierValue($class->getIdentifierValue($v2));

                        continue;
                    }

                    // Otherwise if a value in a query is already id, e.g. ['referenceField' => $targetDocumentId],
                    // just convert id to it's database representation using it's type
                    $expression[$k][$k2] = $class->getDatabaseIdentifierValue($v2);
                }

                continue;
            }

            // Recursively process expressions within a $not or $elemMatch operator
            if ($k === '$elemMatch' && is_array($v)) {
                $expression[$k] = $this->prepareQueryOrNewObj($v, false);
                continue;
            }

            if ($k === '$not' && is_array($v)) {
                $expression[$k] = $this->prepareQueryExpression($v, $class);
                continue;
            }

            if ($v instanceof $class->name) {
                $expression[$k] = $class->getDatabaseIdentifierValue($class->getIdentifierValue($v));
            } else {
                $expression[$k] = $class->getDatabaseIdentifierValue($v);
            }
        }

        return $expression;
    }

    /**
     * Checks whether the value has DBRef fields.
     *
     * This method doesn't check if the the value is a complete DBRef object,
     * although it should return true for a DBRef. Rather, we're checking that
     * the value has one or more fields for a DBref. In practice, this could be
     * $elemMatch criteria for matching a DBRef.
     *
     * @param mixed $value
     */
    private function hasDBRefFields($value): bool
    {
        if (! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $value) {
            if ($key === '$ref' || $key === '$id' || $key === '$db') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the value has query operators.
     *
     * @param mixed $value
     */
    private function hasQueryOperators($value): bool
    {
        if (! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $notUsedValue) {
            $key = (string) $key;

            if (isset($key[0]) && $key[0] === '$') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of discriminator values for the given ClassMetadata
     *
     * @return list<class-string|null>
     */
    private function getClassDiscriminatorValues(ClassMetadata $metadata): array
    {
        $discriminatorValues = [];

        if ($metadata->discriminatorValue !== null) {
            $discriminatorValues[] = $metadata->discriminatorValue;
        }

        foreach ($metadata->subClasses as $className) {
            $key = array_search($className, $metadata->discriminatorMap);
            if ($key === false) {
                continue;
            }

            $discriminatorValues[] = $key;
        }

        // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
        if ($metadata->defaultDiscriminatorValue && in_array($metadata->defaultDiscriminatorValue, $discriminatorValues)) {
            $discriminatorValues[] = null;
        }

        return $discriminatorValues;
    }
}
