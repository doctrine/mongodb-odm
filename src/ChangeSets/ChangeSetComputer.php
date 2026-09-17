<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\ChangeSets;

use Closure;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use MongoDB\BSON\UTCDateTime;
use SplObjectStorage;

/**
 * Computes the field-level {@see ChangeSet} for one or more documents, given
 * their original and actual data.
 *
 * Stateless: does not walk associations and does not schedule anything
 * (orphan removal, collection deletion, marking a document for update) —
 * callers inspect the returned ChangeSet themselves, using the same class
 * metadata passed into the request, to decide what needs scheduling.
 *
 * @internal
 */
final class ChangeSetComputer
{
    /**
     * @param iterable<ChangeSetComputationRequest>                           $requests
     * @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion
     *
     * @return SplObjectStorage<object, ChangeSet> keyed by each request's document
     */
    public function computeChangeSets(iterable $requests, Closure $isCollectionScheduledForDeletion): SplObjectStorage
    {
        $changeSets = new SplObjectStorage();
        foreach ($requests as $request) {
            $changeSets[$request->document] = $this->computeSingleChangeSet($request, $isCollectionScheduledForDeletion);
        }

        return $changeSets;
    }

    /**
     * Computes the changeset for a single document and derives everything
     * the caller needs to schedule as a consequence (orphan removal,
     * collection deletion), merging onto an existing changeset when one is
     * given. Scheduling is derived from this pass's own diff, before that
     * merge happens.
     *
     * @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion
     */
    public function computeChangeSet(ChangeSetComputationRequest $request, Closure $isCollectionScheduledForDeletion): ChangeSetComputationResult
    {
        $changeSet     = $this->computeChangeSets([$request], $isCollectionScheduledForDeletion)[$request->document];
        $isNewDocument = $request->originalData === null;

        if ($isNewDocument || $changeSet->isEmpty()) {
            return new ChangeSetComputationResult($changeSet, $isNewDocument);
        }

        [$orphansToRemove, $collectionsToDelete] = $this->determineScheduling($changeSet, $request->class);

        if ($request->existingChangeSet !== null && $request->existingChangeSet !== $changeSet) {
            $request->existingChangeSet->merge($changeSet);
            $changeSet = $request->existingChangeSet;
        }

        return new ChangeSetComputationResult($changeSet, false, $orphansToRemove, $collectionsToDelete);
    }

    /**
     * Pure policy decision: given the fields this pass found changed, which
     * old values need orphan removal and which old collections need
     * deletion.
     *
     * @phpstan-param ClassMetadata<object> $class
     *
     * @return array{0: list<object>, 1: list<PersistentCollectionInterface<array-key, object>>}
     */
    private function determineScheduling(ChangeSet $changeSet, ClassMetadata $class): array
    {
        $orphansToRemove     = [];
        $collectionsToDelete = [];

        foreach ($changeSet->getFieldNames() as $propName) {
            $mapping = $class->fieldMappings[$propName] ?? null;
            if ($mapping === null) {
                continue;
            }

            if (isset($mapping['embedded']) && $mapping['type'] === ClassMetadata::ONE) {
                $orgValue = $changeSet->getOldValue($propName);
                if ($orgValue !== null) {
                    $orphansToRemove[] = $orgValue;
                }

                continue;
            }

            if (isset($mapping['reference']) && $mapping['type'] === ClassMetadata::ONE && $mapping['isOwningSide']) {
                $orgValue = $changeSet->getOldValue($propName);
                if ($orgValue !== null && $mapping['orphanRemoval']) {
                    $orphansToRemove[] = $orgValue;
                }

                continue;
            }

            if (! isset($mapping['type']) || $mapping['type'] !== ClassMetadata::MANY) {
                continue;
            }

            $orgValue    = $changeSet->getOldValue($propName);
            $actualValue = $changeSet->getNewValue($propName);
            if ($actualValue && $actualValue->isDirty() && CollectionHelper::usesSet($mapping['strategy'])) {
                continue;
            }

            if ($orgValue === $actualValue || ! ($orgValue instanceof PersistentCollectionInterface)) {
                continue;
            }

            $collectionsToDelete[] = $orgValue;
        }

        return [$orphansToRemove, $collectionsToDelete];
    }

    /** @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion */
    private function computeSingleChangeSet(ChangeSetComputationRequest $request, Closure $isCollectionScheduledForDeletion): ChangeSet
    {
        $class      = $request->class;
        $document   = $request->document;
        $actualData = $request->actualData;

        if ($request->originalData === null) {
            // Document is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $changeSet = new ChangeSet($document, []);
            foreach ($actualData as $propName => $actualValue) {
                // ignore inverse side of reference relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                    continue;
                }

                $changeSet->recordChange($propName, $actualValue);
            }

            return $changeSet;
        }

        if ($class->isReadOnly) {
            return $request->existingChangeSet ?? new ChangeSet($document, $request->originalData);
        }

        // Document is "fully" MANAGED: it was already fully persisted before
        // and we have a copy of the original data
        $originalData           = $request->originalData;
        $isChangeTrackingNotify = $request->isChangeTrackingNotify;
        $changeSet              = $isChangeTrackingNotify && ! $request->forceRecompute && $request->existingChangeSet !== null
            ? $request->existingChangeSet
            : new ChangeSet($document, $originalData);

        $gridFSMetadataProperty = null;

        if ($class->isFile) {
            try {
                $gridFSMetadata         = $class->getFieldMappingByDbFieldName('metadata');
                $gridFSMetadataProperty = $gridFSMetadata['fieldName'];
            } catch (MappingException) {
            }
        }

        foreach ($actualData as $propName => $actualValue) {
            // skip not saved fields
            if (
                (isset($class->fieldMappings[$propName]['notSaved']) && $class->fieldMappings[$propName]['notSaved'] === true) ||
                ($class->isFile && $propName !== $gridFSMetadataProperty)
            ) {
                continue;
            }

            $orgValue = $originalData[$propName] ?? null;

            // skip if value has not changed
            if ($orgValue === $actualValue) {
                if (! $actualValue instanceof PersistentCollectionInterface) {
                    continue;
                }

                if (! $actualValue->isDirty() && ! $isCollectionScheduledForDeletion($actualValue)) {
                    // consider dirty collections as changed as well
                    continue;
                }
            }

            // if relationship is a embed-one, the caller schedules orphan removal for $orgValue itself
            if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::ONE) {
                $changeSet->recordChange($propName, $actualValue);
                continue;
            }

            // if owning side of reference-one relationship
            if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::ONE && $class->fieldMappings[$propName]['isOwningSide']) {
                $changeSet->recordChange($propName, $actualValue);
                continue;
            }

            if ($isChangeTrackingNotify) {
                continue;
            }

            // ignore inverse side of reference relationship
            if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                continue;
            }

            // if embed-many or reference-many relationship
            if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::MANY) {
                $changeSet->recordChange($propName, $actualValue);
                continue;
            }

            // skip equivalent date values
            if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'date') {
                $dateType      = $class->getFieldType($propName);
                $dbOrgValue    = $dateType->convertToDatabaseValue($orgValue);
                $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

                // Loose comparison is only safe when both values are UTC dates. A custom
                // type overriding "date" may produce a different database representation.
                if ($dbOrgValue instanceof UTCDateTime && $dbActualValue instanceof UTCDateTime) {
                    // We rely on loose comparison to compare every field
                    // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
                    if ($dbOrgValue == $dbActualValue) {
                        continue;
                    }
                }
            }

            // regular field
            $changeSet->recordChange($propName, $actualValue);
        }

        return $changeSet;
    }
}
