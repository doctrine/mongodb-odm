<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSet;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Types\Incrementable;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use InvalidArgumentException;
use UnexpectedValueException;

use function array_search;
use function array_values;
use function assert;

/**
 * PersistenceBuilder builds the queries used by the persisters to update and insert
 * documents when a DocumentManager is flushed. It uses the changeset information in the
 * UnitOfWork to build queries using atomic operators like $set, $unset, etc.
 *
 * @internal
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 */
final class PersistenceBuilder
{
    /**
     * The DocumentManager instance.
     */
    private DocumentManager $dm;

    /**
     * The UnitOfWork instance.
     */
    private UnitOfWork $uow;

    /**
     * Initializes a new PersistenceBuilder instance.
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow)
    {
        $this->dm  = $dm;
        $this->uow = $uow;
    }

    /**
     * Prepares the array that is ready to be inserted to mongodb for a given object document.
     *
     * @param object $document
     *
     * @return array<string, mixed> $insertData
     */
    public function prepareInsertData($document): array
    {
        $class     = $this->dm->getClassMetadata($document::class);
        $changeSet = $this->uow->getChangeSet($document);

        $insertData = [];
        foreach ($class->fieldMappings as $mapping) {
            $this->applyInsertValue($insertData, $class, $mapping, $this->resolveInsertValue($changeSet, $mapping['fieldName']));
        }

        if (isset($class->discriminatorField)) {
            $insertData[$class->discriminatorField] = $this->resolveDiscriminatorValue($class);
        }

        return $insertData;
    }

    /**
     * A field that was never recorded as changed inserts as absent rather
     * than erroring: unlike an update, an insert has no prior state to diff
     * against, so every mapped field is either "new" (present in the
     * changeset) or simply unset on the document.
     */
    private function resolveInsertValue(ChangeSet $changeSet, string $fieldName): mixed
    {
        return $changeSet->hasChangedField($fieldName) ? $changeSet->getNewValue($fieldName) : null;
    }

    /**
     * @param array<string, mixed> $insertData
     * @phpstan-param ClassMetadata<object> $class
     * @phpstan-param FieldMapping $mapping
     */
    private function applyInsertValue(array &$insertData, ClassMetadata $class, array $mapping, mixed $new): void
    {
        if ($new === null) {
            if ($mapping['nullable']) {
                $insertData[$mapping['name']] = null;
            }

            return;
        }

        // @Field, @String, @Date, etc.
        if (! isset($mapping['association'])) {
            $insertData[$mapping['name']] = $class->getFieldType($mapping['fieldName'])->convertToDatabaseValue($new);

            return;
        }

        // @ReferenceOne
        if ($mapping['association'] === ClassMetadata::REFERENCE_ONE) {
            $insertData[$mapping['name']] = $this->prepareReferencedDocumentValue($mapping, $new);

            return;
        }

        // @EmbedOne
        if ($mapping['association'] === ClassMetadata::EMBED_ONE) {
            $insertData[$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

            return;
        }

        // @ReferenceMany, @EmbedMany
        if ($mapping['type'] === ClassMetadata::MANY) {
            $this->applyManyFieldInsert($insertData, $mapping, $new);

            return;
        }

        throw new UnexpectedValueException('Unsupported mapping association for field "' . $mapping['fieldName'] . '": ' . $mapping['association']);
    }

    /**
     * An inverse-side collection is never stored on the owning document, and
     * an `addToSet`-strategy collection is populated by its own atomic
     * operation rather than the insert document, to avoid a risk of
     * duplicated entries — both are silently skipped here rather than
     * treated as an error.
     *
     * @param array<string, mixed>                             $insertData
     * @param PersistentCollectionInterface<array-key, object> $new
     * @phpstan-param FieldMapping $mapping
     */
    private function applyManyFieldInsert(array &$insertData, array $mapping, PersistentCollectionInterface $new): void
    {
        if ($mapping['isInverseSide']) {
            return;
        }

        if ($new->isEmpty() && ! $mapping['storeEmptyArray']) {
            return;
        }

        if ($mapping['strategy'] === ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET && ! $mapping['storeEmptyArray']) {
            return;
        }

        $insertData[$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);
    }

    /**
     * Resolves the discriminator value to store for $class: its own explicit
     * value if mapped, or the class name itself when the class is absent
     * from the map (allowed only for an empty map, i.e. no map is enforced
     * at all).
     *
     * No return type is declared: {@see ClassMetadata::$discriminatorValue}
     * is documented as `class-string|null`, but a discriminator map keyed by
     * integers (e.g. `#[DiscriminatorMap([0 => Foo::class, 1 => Bar::class])]`)
     * assigns that integer key as the value instead, so `int` is also
     * possible in practice.
     *
     * @phpstan-param ClassMetadata<object> $class
     */
    private function resolveDiscriminatorValue(ClassMetadata $class): mixed
    {
        $discriminatorValue = $class->discriminatorValue;

        if ($discriminatorValue === null) {
            if (! empty($class->discriminatorMap)) {
                throw MappingException::unlistedClassInDiscriminatorMap($class->name);
            }

            $discriminatorValue = $class->name;
        }

        return $discriminatorValue;
    }

    /**
     * Prepares the update query to update a given document object in mongodb.
     *
     * @param object $document
     *
     * @return array<string, mixed> $updateData
     */
    public function prepareUpdateData($document): array
    {
        $class     = $this->dm->getClassMetadata($document::class);
        $changeSet = $this->uow->getChangeSet($document);

        $updateData = [];
        foreach ($changeSet->getFieldNames() as $fieldName) {
            $mapping = $class->fieldMappings[$fieldName];

            // skip non embedded document identifiers
            if (! $class->isEmbeddedDocument && ! empty($mapping['id'])) {
                continue;
            }

            $new = $changeSet->getNewValue($fieldName);

            if ($new === null) {
                $this->applyNullFieldUpdate($updateData, $mapping);
                continue;
            }

            // Scalar fields
            if (! isset($mapping['association'])) {
                $this->applyScalarFieldUpdate($updateData, $class, $mapping, $changeSet->getOldValue($fieldName), $new);

                continue;
            }

            // @EmbedOne
            if ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $this->applyEmbedOneFieldUpdate($updateData, $mapping, $new);

                continue;
            }

            // @ReferenceMany, @EmbedMany
            if ($mapping['type'] === ClassMetadata::MANY) {
                $this->applyManyFieldUpdate($updateData, $mapping, $changeSet->getOldValue($fieldName), $new);

                continue;
            }

            // @ReferenceOne
            if ($mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $this->applyReferenceOneField($updateData, $mapping, $new);

                continue;
            }

            throw new UnexpectedValueException('Unsupported mapping association for field "' . $mapping['fieldName'] . '": ' . $mapping['association']);
        }

        $this->applyScheduledCollectionsUpdate($updateData, $document);

        return $updateData;
    }

    /**
     * A null value is only ever recorded for a nullable field as an
     * explicit `$set`; for a non-nullable field, null means the field was
     * removed from the document entirely, so it's `$unset` instead.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyNullFieldUpdate(array &$updateData, array $mapping): void
    {
        if ($mapping['nullable'] === true) {
            $updateData['$set'][$mapping['name']] = null;
        } else {
            $updateData['$unset'][$mapping['name']] = true;
        }
    }

    /**
     * An INCREMENT-strategy field is written as the delta between old and
     * new value via `$inc`, so concurrent writers accumulate rather than
     * clobber each other; every other scalar field is simply overwritten.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param ClassMetadata<object> $class
     * @phpstan-param FieldMapping $mapping
     */
    private function applyScalarFieldUpdate(array &$updateData, ClassMetadata $class, array $mapping, mixed $old, mixed $new): void
    {
        if (isset($mapping['strategy']) && $mapping['strategy'] === ClassMetadata::STORAGE_STRATEGY_INCREMENT) {
            $operator = '$inc';
            $type     = $class->getFieldType($mapping['fieldName']);
            assert($type instanceof Incrementable);
            $value = $type->convertToDatabaseValue($type->diff($old, $new));
        } else {
            $operator = '$set';
            $value    = $class->getFieldType($mapping['fieldName'])->convertToDatabaseValue($new);
        }

        $updateData[$operator][$mapping['name']] = $value;
    }

    /**
     * A newly-created embedded document (not yet persisted anywhere) is
     * written whole via `$set`; an existing one is diffed recursively via
     * {@see self::prepareUpdateData()}, and its own `$set`/`$unset` keys are
     * nested under this field's dot-path.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyEmbedOneFieldUpdate(array &$updateData, array $mapping, object $new): void
    {
        if ($this->uow->isScheduledForInsert($new)) {
            $updateData['$set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

            return;
        }

        $update = $this->prepareUpdateData($new);
        foreach ($update as $cmd => $values) {
            foreach ($values as $key => $value) {
                $updateData[$cmd][$mapping['name'] . '.' . $key] = $value;
            }
        }
    }

    /**
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyManyFieldUpdate(array &$updateData, array $mapping, mixed $old, mixed $new): void
    {
        if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForUpdate($new)) {
            $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);

            return;
        }

        // Either the new or the old collection instance for this field (never
        // both) may carry a pending deletion; whichever one does needs
        // unscheduling now that this $unset takes care of it instead.
        if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($new)) {
            $updateData['$unset'][$mapping['name']] = true;
            $this->uow->unscheduleCollectionDeletion($new);

            return;
        }

        if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($old)) {
            $updateData['$unset'][$mapping['name']] = true;
            $this->uow->unscheduleCollectionDeletion($old);

            return;
        }

        if ($mapping['association'] !== ClassMetadata::EMBED_MANY) {
            return;
        }

        $this->applyEmbedManyFieldUpdate($updateData, $mapping, $new);
    }

    /**
     * A non-atomic embed-many collection is updated element by element: each
     * entry not itself scheduled for insert (which will be written whole by
     * the parent's own embed-many insert path) is diffed recursively via
     * {@see self::prepareUpdateData()}, nested under this field's dot-path
     * plus the entry's key.
     *
     * @param array<string, mixed>                             $updateData
     * @param PersistentCollectionInterface<array-key, object> $new
     * @phpstan-param FieldMapping $mapping
     */
    private function applyEmbedManyFieldUpdate(array &$updateData, array $mapping, PersistentCollectionInterface $new): void
    {
        foreach ($new as $key => $embeddedDoc) {
            if ($this->uow->isScheduledForInsert($embeddedDoc)) {
                continue;
            }

            $update = $this->prepareUpdateData($embeddedDoc);
            foreach ($update as $cmd => $values) {
                foreach ($values as $name => $value) {
                    $updateData[$cmd][$mapping['name'] . '.' . $key . '.' . $name] = $value;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyReferenceOneField(array &$updateData, array $mapping, object $new): void
    {
        $updateData['$set'][$mapping['name']] = $this->prepareReferencedDocumentValue($mapping, $new);
    }

    /**
     * Collections that aren't dirty but could still be subject to an atomic
     * update (or a pending deletion) are excluded from the changeset, since
     * neither condition changes a field value the changeset would track; go
     * through them separately here instead. Non-atomic `@ReferenceMany`
     * collections need no handling here: they're persisted via their own
     * write operations by {@see CollectionPersister}, not embedded in this
     * document's update.
     *
     * @param array<string, mixed> $updateData
     */
    private function applyScheduledCollectionsUpdate(array &$updateData, object $document): void
    {
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            $mapping = $coll->getMapping();
            if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForUpdate($coll)) {
                $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($coll, true);
            } elseif (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($coll)) {
                $updateData['$unset'][$mapping['name']] = true;
                $this->uow->unscheduleCollectionDeletion($coll);
            }
        }
    }

    /**
     * Prepares the update query to upsert a given document object in mongodb.
     *
     * @param object $document
     *
     * @return array<string, mixed> $updateData
     */
    public function prepareUpsertData($document): array
    {
        $class     = $this->dm->getClassMetadata($document::class);
        $changeSet = $this->uow->getChangeSet($document);

        $updateData = [];
        foreach ($changeSet->getFieldNames() as $fieldName) {
            $mapping = $class->fieldMappings[$fieldName];
            $new     = $changeSet->getNewValue($fieldName);

            // Fields with a null value should only be written for inserts
            if ($new === null) {
                $this->applyNullFieldUpsert($updateData, $mapping);
                continue;
            }

            // Scalar fields
            if (! isset($mapping['association'])) {
                $this->applyScalarFieldUpsert($updateData, $class, $mapping, $changeSet->getOldValue($fieldName), $new);

                continue;
            }

            // @EmbedOne
            if ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $this->applyEmbedOneFieldUpsert($updateData, $mapping, $new);

                continue;
            }

            // @ReferenceOne
            if ($mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $this->applyReferenceOneField($updateData, $mapping, $new);

                continue;
            }

            // @ReferenceMany, @EmbedMany
            if (
                $mapping['type'] === ClassMetadata::MANY && ! $mapping['isInverseSide']
                    && $new instanceof PersistentCollectionInterface && $new->isDirty()
                    && CollectionHelper::isAtomic($mapping['strategy'])
            ) {
                $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);

                continue;
            }

            // @EmbedMany and non-atomic @ReferenceMany are handled by CollectionPersister, so no need to throw here.
        }

        if (isset($class->discriminatorField)) {
            $updateData['$set'][$class->discriminatorField] = $this->resolveDiscriminatorValue($class);
        }

        return $updateData;
    }

    /**
     * A null value is only meaningful for an insert (`$setOnInsert`): an
     * upsert that matches an existing document must never overwrite one of
     * its fields back to null just because the in-memory value happens to be
     * null, so a non-nullable field is skipped entirely rather than unset.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyNullFieldUpsert(array &$updateData, array $mapping): void
    {
        if ($mapping['nullable'] === true) {
            $updateData['$setOnInsert'][$mapping['name']] = null;
        }
    }

    /**
     * Identical to {@see self::applyScalarFieldUpdate()}, except an
     * identifier field is never treated as an increment here: update()
     * already filters id fields out of its changeset walk before reaching
     * the scalar branch, but upsertData iterates the raw changeset, which
     * still contains a newly-assigned id field for a to-be-inserted document.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param ClassMetadata<object> $class
     * @phpstan-param FieldMapping $mapping
     */
    private function applyScalarFieldUpsert(array &$updateData, ClassMetadata $class, array $mapping, mixed $old, mixed $new): void
    {
        if (empty($mapping['id']) && isset($mapping['strategy']) && $mapping['strategy'] === ClassMetadata::STORAGE_STRATEGY_INCREMENT) {
            $operator = '$inc';
            $type     = $class->getFieldType($mapping['fieldName']);
            assert($type instanceof Incrementable);
            $value = $type->convertToDatabaseValue($type->diff($old, $new));
        } else {
            $operator = '$set';
            $value    = $class->getFieldType($mapping['fieldName'])->convertToDatabaseValue($new);
        }

        $updateData[$operator][$mapping['name']] = $value;
    }

    /**
     * Same shape as {@see self::applyEmbedOneFieldUpdate()}, but recurses
     * via {@see self::prepareUpsertData()} instead, so a nested embedded
     * document gets the same insert-vs-update-matching null handling as the
     * top-level document.
     *
     * @param array<string, mixed> $updateData
     * @phpstan-param FieldMapping $mapping
     */
    private function applyEmbedOneFieldUpsert(array &$updateData, array $mapping, object $new): void
    {
        if ($this->uow->isScheduledForInsert($new)) {
            $updateData['$set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

            return;
        }

        $update = $this->prepareUpsertData($new);
        foreach ($update as $cmd => $values) {
            foreach ($values as $key => $value) {
                $updateData[$cmd][$mapping['name'] . '.' . $key] = $value;
            }
        }
    }

    /**
     * Returns the reference representation to be stored in MongoDB.
     *
     * If the document does not have an identifier and the mapping calls for a
     * simple reference, null may be returned.
     *
     * @param object $document
     * @phpstan-param FieldMapping $referenceMapping
     */
    public function prepareReferencedDocumentValue(array $referenceMapping, $document): mixed
    {
        return $this->dm->createReference($document, $referenceMapping);
    }

    /**
     * Returns the embedded document to be stored in MongoDB.
     *
     * The return value will usually be an associative array with string keys
     * corresponding to field names on the embedded document. An object may be
     * returned if the document is empty, to ensure that a BSON object will be
     * stored in lieu of an array.
     *
     * If $includeNestedCollections is true, nested collections will be included
     * in this prepared value and the option will cascade to all embedded
     * associations. If any nested PersistentCollections (embed or reference)
     * within this value were previously scheduled for deletion or update, they
     * will also be unscheduled.
     *
     * @param object $embeddedDocument
     * @param bool   $includeNestedCollections
     * @phpstan-param FieldMapping  $embeddedMapping
     *
     * @return array<string, mixed>|object
     *
     * @throws UnexpectedValueException If an unsupported associating mapping is found.
     */
    public function prepareEmbeddedDocumentValue(array $embeddedMapping, $embeddedDocument, $includeNestedCollections = false): array|object
    {
        $embeddedDocumentValue = [];
        $class                 = $this->dm->getClassMetadata($embeddedDocument::class);

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            // Skip notSaved fields
            if (! empty($mapping['notSaved'])) {
                continue;
            }

            $rawValue = $class->propertyAccessors[$mapping['fieldName']]->getValue($embeddedDocument);

            $value = null;

            if ($rawValue !== null) {
                switch ($mapping['association'] ?? null) {
                    // @Field, @String, @Date, etc.
                    case null:
                        $value = $class->getFieldType($fieldName)->convertToDatabaseValue($rawValue);
                        break;

                    case ClassMetadata::EMBED_ONE:
                    case ClassMetadata::REFERENCE_ONE:
                        // Nested collections should only be included for embedded relationships
                        $value = $this->prepareAssociatedDocumentValue($mapping, $rawValue, $includeNestedCollections && isset($mapping['embedded']));
                        break;

                    case ClassMetadata::EMBED_MANY:
                    case ClassMetadata::REFERENCE_MANY:
                        // Skip PersistentCollections already scheduled for deletion
                        if (
                            ! $includeNestedCollections && $rawValue instanceof PersistentCollectionInterface
                            && $this->uow->isCollectionScheduledForDeletion($rawValue)
                        ) {
                            break;
                        }

                        // Prepare persistent collection if it's not already one
                        $collection = $rawValue instanceof PersistentCollectionInterface
                            ? $rawValue
                            : $this->preparePersistentCollection($mapping, $embeddedDocument, $rawValue);

                        $value = $this->prepareAssociatedCollectionValue($collection, $includeNestedCollections);
                        break;

                    default:
                        throw new UnexpectedValueException('Unsupported mapping association: ' . $mapping['association']);
                }
            }

            // Omit non-nullable fields that would have a null value
            if ($value === null && $mapping['nullable'] === false) {
                continue;
            }

            $embeddedDocumentValue[$mapping['name']] = $value;
        }

        /* Add a discriminator value if the embedded document is not mapped
         * explicitly to a targetDocument class.
         */
        if (! isset($embeddedMapping['targetDocument'])) {
            $discriminatorField = $embeddedMapping['discriminatorField'];
            if (! empty($embeddedMapping['discriminatorMap'])) {
                $discriminatorValue = array_search($class->name, $embeddedMapping['discriminatorMap']);

                if ($discriminatorValue === false) {
                    throw MappingException::unlistedClassInDiscriminatorMap($class->name);
                }
            } else {
                $discriminatorValue = $class->name;
            }

            $embeddedDocumentValue[$discriminatorField] = $discriminatorValue;
        }

        /* If the class has a discriminator (field and value), use it. A child
         * class that is not defined in the discriminator map may only have a
         * discriminator field and no value, so default to the full class name.
         */
        if (isset($class->discriminatorField)) {
            $discriminatorValue = $class->discriminatorValue;

            if ($discriminatorValue === null) {
                if (! empty($class->discriminatorMap)) {
                    throw MappingException::unlistedClassInDiscriminatorMap($class->name);
                }

                $discriminatorValue = $class->name;
            }

            $embeddedDocumentValue[$class->discriminatorField] = $discriminatorValue;
        }

        // Ensure empty embedded documents are stored as BSON objects
        if (empty($embeddedDocumentValue)) {
            return (object) $embeddedDocumentValue;
        }

        /* @todo Consider always casting the return value to an object, or
         * building $embeddedDocumentValue as an object instead of an array, to
         * handle the edge case where all database field names are sequential,
         * numeric keys.
         */
        return $embeddedDocumentValue;
    }

    /**
     * Returns the embedded document or reference representation to be stored.
     *
     * @param object $document
     * @param bool   $includeNestedCollections
     * @phpstan-param FieldMapping  $mapping
     *
     * A reference can resolve to a bare identifier (see
     * {@see self::prepareReferencedDocumentValue()}), so this can't be
     * narrowed past `mixed`.
     *
     * @throws InvalidArgumentException If the mapping is neither embedded nor reference.
     */
    public function prepareAssociatedDocumentValue(array $mapping, $document, $includeNestedCollections = false): mixed
    {
        if (isset($mapping['embedded'])) {
            return $this->prepareEmbeddedDocumentValue($mapping, $document, $includeNestedCollections);
        }

        if (isset($mapping['reference'])) {
            return $this->prepareReferencedDocumentValue($mapping, $document);
        }

        throw new InvalidArgumentException('Mapping is neither embedded nor reference.');
    }

    /**
     * Returns the collection representation to be stored and unschedules it afterwards.
     *
     * @param PersistentCollectionInterface<array-key, object> $coll
     * @param bool                                             $includeNestedCollections
     *
     * @return mixed[]
     */
    public function prepareAssociatedCollectionValue(PersistentCollectionInterface $coll, $includeNestedCollections = false): array
    {
        $mapping  = $coll->getMapping();
        $pb       = $this;
        $callback = isset($mapping['embedded'])
            ? static fn ($v) => $pb->prepareEmbeddedDocumentValue($mapping, $v, $includeNestedCollections)
            : static fn ($v) => $pb->prepareReferencedDocumentValue($mapping, $v);

        $setData = $coll->map($callback)->toArray();
        if (CollectionHelper::isList($mapping['strategy'])) {
            $setData = array_values($setData);
        }

        $this->uow->unscheduleCollectionDeletion($coll);
        $this->uow->unscheduleCollectionUpdate($coll);

        return $setData;
    }

    /**
     * @param array<string, mixed>                                 $mapping
     * @param array<array-key, mixed>|Collection<array-key, mixed> $rawValue
     *
     * @return PersistentCollectionInterface<array-key, object>
     */
    private function preparePersistentCollection(array $mapping, object $owner, array|Collection $rawValue): PersistentCollectionInterface
    {
        if ($rawValue instanceof PersistentCollectionInterface) {
            return $rawValue;
        }

        // If $actualData[$name] is not a Collection then use an ArrayCollection.
        if (! $rawValue instanceof Collection) {
            $rawValue = new ArrayCollection($rawValue);
        }

        // Inject PersistentCollection
        $coll = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create($this->dm, $mapping, $rawValue);
        $coll->setOwner($owner, $mapping);
        $coll->setDirty(! $rawValue->isEmpty());

        return $coll;
    }
}
