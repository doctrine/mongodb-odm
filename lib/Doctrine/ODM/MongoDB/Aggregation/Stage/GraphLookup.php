<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;
use LogicException;

use function array_map;
use function is_array;
use function is_string;
use function substr;

/** @phpstan-import-type FieldMapping from ClassMetadata */
class GraphLookup extends Stage
{
    private ?string $from;

    /** @var string|Expr|mixed[]|null */
    private string|Expr|array|null $startWith;

    private ?string $connectFromField = null;

    private ?string $connectToField = null;

    private ?string $as = null;

    private ?int $maxDepth = null;

    private ?string $depthField = null;

    private Stage\GraphLookup\MatchStage $restrictSearchWithMatch;

    private ?ClassMetadata $targetClass = null;

    /**
     * @param string $from Target collection for the $graphLookup operation to
     * search, recursively matching the connectFromField to the connectToField.
     */
    public function __construct(Builder $builder, string $from, private DocumentManager $dm, private ClassMetadata $class)
    {
        parent::__construct($builder);

        $this->restrictSearchWithMatch = new GraphLookup\MatchStage($this->builder, $this);
        $this->from($from);
    }

    /**
     * Name of the array field added to each output document.
     *
     * Contains the documents traversed in the $graphLookup stage to reach the
     * document.
     */
    public function alias(string $alias): static
    {
        $this->as = $alias;

        return $this;
    }

    /**
     * Field name whose value $graphLookup uses to recursively match against the
     * connectToField of other documents in the collection.
     *
     * Optionally, connectFromField may be an array of field names, each of
     * which is individually followed through the traversal process.
     */
    public function connectFromField(string $connectFromField): static
    {
        // No targetClass mapping - simply use field name as is
        if (! $this->targetClass) {
            $this->connectFromField = $connectFromField;

            return $this;
        }

        // connectFromField doesn't have to be a reference - in this case, just convert the field name
        if (! $this->targetClass->hasReference($connectFromField)) {
            $this->connectFromField = $this->convertTargetFieldName($connectFromField);

            return $this;
        }

        // connectFromField is a reference - do a sanity check
        $referenceMapping = $this->targetClass->getFieldMapping($connectFromField);
        if ($referenceMapping['targetDocument'] !== $this->targetClass->name) {
            throw MappingException::connectFromFieldMustReferenceSameDocument($connectFromField);
        }

        $this->connectFromField = $this->getReferencedFieldName($connectFromField, $referenceMapping);

        return $this;
    }

    /**
     * Field name in other documents against which to match the value of the
     * field specified by the connectFromField parameter.
     */
    public function connectToField(string $connectToField): static
    {
        $this->connectToField = $this->convertTargetFieldName($connectToField);

        return $this;
    }

    /**
     * Name of the field to add to each traversed document in the search path.
     *
     * The value of this field is the recursion depth for the document,
     * represented as a NumberLong. Recursion depth value starts at zero, so the
     * first lookup corresponds to zero depth.
     */
    public function depthField(string $depthField): static
    {
        $this->depthField = $depthField;

        return $this;
    }

    /**
     * Target collection for the $graphLookup operation to search, recursively
     * matching the connectFromField to the connectToField.
     *
     * @param class-string|string $from
     */
    public function from(string $from): static
    {
        // $from can either be
        // a) a field name indicating a reference to a different document. Currently, only REFERENCE_STORE_AS_ID is supported
        // b) a Class name
        // c) a collection name
        // In cases b) and c) the local and foreign fields need to be filled
        if ($this->class->hasReference($from)) {
            return $this->fromReference($from);
        }

        // Check if mapped class with given name exists
        try {
            $this->targetClass = $this->dm->getClassMetadata($from);
        } catch (BaseMappingException) {
            $this->from = $from;

            return $this;
        }

        $this->from = $this->targetClass->getCollection();

        return $this;
    }

    public function getExpression(): array
    {
        $restrictSearchWithMatch = $this->restrictSearchWithMatch->getExpression() ?: (object) [];

        $graphLookup = [
            'from' => $this->from,
            'startWith' => $this->convertExpression($this->startWith),
            'connectFromField' => $this->connectFromField,
            'connectToField' => $this->connectToField,
            'as' => $this->as,
            'restrictSearchWithMatch' => $restrictSearchWithMatch,
            'maxDepth' => $this->maxDepth,
            'depthField' => $this->depthField,
        ];

        foreach (['maxDepth', 'depthField'] as $field) {
            if ($graphLookup[$field] !== null) {
                continue;
            }

            unset($graphLookup[$field]);
        }

        return ['$graphLookup' => $graphLookup];
    }

    /**
     * Non-negative integral number specifying the maximum recursion depth.
     */
    public function maxDepth(int $maxDepth): static
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    /**
     * A document specifying additional conditions for the recursive search.
     */
    public function restrictSearchWithMatch(): GraphLookup\MatchStage
    {
        return $this->restrictSearchWithMatch;
    }

    /**
     * Expression that specifies the value of the connectFromField with which to
     * start the recursive search.
     *
     * Optionally, startWith may be array of values, each of which is
     * individually followed through the traversal process.
     *
     * @param string|mixed[]|Expr $expression
     */
    public function startWith($expression): static
    {
        $this->startWith = $expression;

        return $this;
    }

    /** @throws MappingException */
    private function fromReference(string $fieldName): static
    {
        if (! $this->class->hasReference($fieldName)) {
            MappingException::referenceMappingNotFound($this->class->name, $fieldName);
        }

        $referenceMapping  = $this->class->getFieldMapping($fieldName);
        $this->targetClass = $this->dm->getClassMetadata($referenceMapping['targetDocument']);

        $this->from = $this->targetClass->getCollection();

        $referencedFieldName = $this->getReferencedFieldName($fieldName, $referenceMapping);

        if ($referenceMapping['isOwningSide']) {
            $this
                ->startWith('$' . $referencedFieldName)
                ->connectToField('_id');
        } else {
            $this
                ->startWith('$' . $referencedFieldName)
                ->connectToField('_id');
        }

        // A self-reference indicates that we can also fill the "connectFromField" accordingly
        if ($this->targetClass->name === $this->class->name) {
            $this->connectFromField($referencedFieldName);
        }

        return $this;
    }

    /**
     * @param array|mixed|string $expression
     *
     * @return array|mixed|string
     */
    private function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        }

        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister($this->class)->prepareFieldName(substr($expression, 1));
        }

        return Type::convertPHPToDatabaseValue(Expr::convertExpression($expression));
    }

    private function convertTargetFieldName(string $fieldName): string
    {
        if (! $this->targetClass) {
            return $fieldName;
        }

        return $this->getDocumentPersister($this->targetClass)->prepareFieldName($fieldName);
    }

    private function getDocumentPersister(ClassMetadata $class): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
    }

    /** @phpstan-param FieldMapping $mapping */
    private function getReferencedFieldName(string $fieldName, array $mapping): string
    {
        if (! $this->targetClass) {
            throw new LogicException('Cannot use getReferencedFieldName when no target mapping was given.');
        }

        if (! $mapping['isOwningSide']) {
            if (isset($mapping['repositoryMethod']) || ! isset($mapping['mappedBy'])) {
                throw MappingException::repositoryMethodLookupNotAllowed($this->class->name, $fieldName);
            }

            $mapping = $this->targetClass->getFieldMapping($mapping['mappedBy']);
        }

        switch ($mapping['storeAs']) {
            case ClassMetadata::REFERENCE_STORE_AS_ID:
            case ClassMetadata::REFERENCE_STORE_AS_REF:
                return ClassMetadata::getReferenceFieldName($mapping['storeAs'], $mapping['name']);

            default:
                throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
        }
    }
}
