<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\ChangeSets;

use Closure;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Types\DateType;
use Doctrine\ODM\MongoDB\Types\Type;

use function assert;
use function spl_object_id;

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
     * @return array<int, ChangeSet> keyed by spl_object_id() of each request's document
     */
    public function computeChangeSets(iterable $requests, Closure $isCollectionScheduledForDeletion): array
    {
        $changeSets = [];
        foreach ($requests as $request) {
            $changeSets[spl_object_id($request->document)] = $this->computeChangeSet($request, $isCollectionScheduledForDeletion);
        }

        return $changeSets;
    }

    /**
     * Pure policy decision: which already-managed documents of $class should
     * even be examined this commit, given the class's change-tracking policy.
     *
     * @param array<string, object> $identityMapForClass
     * @param array<int, object>    $scheduledForSynchronization
     *
     * @return iterable<object>
     */
    public function selectDocumentsForChangeSetComputation(
        ClassMetadata $class,
        array $identityMapForClass,
        array $scheduledForSynchronization,
    ): iterable {
        if ($class->isChangeTrackingDeferredImplicit()) {
            return $identityMapForClass;
        }

        return $scheduledForSynchronization;
    }

    /** @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion */
    private function computeChangeSet(ChangeSetComputationRequest $request, Closure $isCollectionScheduledForDeletion): ChangeSet
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
                $dateType = Type::getType('date');
                assert($dateType instanceof DateType);
                $dbOrgValue    = $dateType->convertToDatabaseValue($orgValue);
                $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

                // We rely on loose comparison to compare every field (including microseconds)
                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
                if ($dbOrgValue == $dbActualValue) {
                    continue;
                }
            }

            // regular field
            $changeSet->recordChange($propName, $actualValue);
        }

        return $changeSet;
    }
}
