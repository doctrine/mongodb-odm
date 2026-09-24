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

        [$orphansToRemove, $collectionsToDelete] = $this->determineScheduling($changeSet, $request->class, $request->isChangeTrackingNotify);

        if ($request->existingChangeSet !== null && $request->existingChangeSet !== $changeSet) {
            $request->existingChangeSet->merge($changeSet);
            $changeSet = $request->existingChangeSet;
        }

        return new ChangeSetComputationResult($changeSet, false, $orphansToRemove, $collectionsToDelete);
    }

    /**
     * Pure policy decision: given the fields this pass found changed, which
     * old values need orphan removal and which old collections need
     * deletion. Only single-valued embeds/references being replaced can
     * produce an orphan, and only a to-many association whose collection
     * instance itself was swapped out (as opposed to merely being mutated)
     * can produce a collection to delete.
     *
     * @phpstan-param ClassMetadata<object> $class
     *
     * @return array{0: list<object>, 1: list<PersistentCollectionInterface<array-key, object>>}
     */
    private function determineScheduling(ChangeSet $changeSet, ClassMetadata $class, bool $isChangeTrackingNotify): array
    {
        $orphansToRemove     = [];
        $collectionsToDelete = [];

        foreach ($changeSet->getFieldNames() as $propName) {
            if (! isset($class->fieldMappings[$propName])) {
                continue;
            }

            if ($class->isSingleValuedEmbed($propName)) {
                $orphan = $changeSet->getOldValue($propName);
                // A field can be present in the changeset without the value
                // having actually changed: under NOTIFY tracking, a reused
                // changeset can carry a field that was set and then set back
                // to its original value within the same pass. Only a genuine
                // replacement produces an orphan.
                if ($orphan !== null && $orphan !== $changeSet->getNewValue($propName)) {
                    $orphansToRemove[] = $orphan;
                }

                continue;
            }

            if ($class->isSingleValuedReference($propName) && $class->fieldMappings[$propName]['isOwningSide']) {
                $orphan = $changeSet->getOldValue($propName);
                if (
                    $orphan !== null
                    && $orphan !== $changeSet->getNewValue($propName)
                    && $class->fieldMappings[$propName]['orphanRemoval']
                ) {
                    $orphansToRemove[] = $orphan;
                }

                continue;
            }

            // Under NOTIFY change tracking, a to-many field is never diffed against
            // its snapshot here in the first place (buildManagedChangeSet() records
            // it only via propertyChanged(), which has no old collection to compare
            // against) — so when $changeSet is the NOTIFY-reused, cross-pass instance
            // (see computeChangeSet()), a to-many field it carries may simply be a
            // propertyChanged()-recorded change unrelated to a collection swap in
            // this pass, and must not be scanned for a collection to delete.
            if ($isChangeTrackingNotify) {
                continue;
            }

            $collection = $this->collectionReplacedForDeletion($changeSet, $class, $propName);
            if ($collection === null) {
                continue;
            }

            $collectionsToDelete[] = $collection;
        }

        return [$orphansToRemove, $collectionsToDelete];
    }

    /**
     * A to-many field's old collection only needs deleting when the field's
     * value was swapped for a genuinely different collection instance. A
     * collection that merely had elements added or removed (dirty) is
     * updated in place instead — except when it uses the "set" write
     * strategy, which always rewrites the field's whole array anyway and
     * would otherwise get its still-in-use collection queued for deletion.
     *
     * @phpstan-param ClassMetadata<object> $class
     *
     * @return PersistentCollectionInterface<array-key, object>|null the old collection to delete, if any
     */
    private function collectionReplacedForDeletion(ChangeSet $changeSet, ClassMetadata $class, string $propName): PersistentCollectionInterface|null
    {
        if (! $class->isCollectionValuedAssociation($propName)) {
            return null;
        }

        $orgValue    = $changeSet->getOldValue($propName);
        $actualValue = $changeSet->getNewValue($propName);

        if ($actualValue && $actualValue->isDirty() && CollectionHelper::usesSet($class->fieldMappings[$propName]['strategy'])) {
            return null;
        }

        if ($orgValue === $actualValue || ! ($orgValue instanceof PersistentCollectionInterface)) {
            return null;
        }

        return $orgValue;
    }

    /** @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion */
    private function computeSingleChangeSet(ChangeSetComputationRequest $request, Closure $isCollectionScheduledForDeletion): ChangeSet
    {
        $class      = $request->class;
        $document   = $request->document;
        $actualData = $request->actualData;

        // A document with no original-data snapshot has never been fully loaded
        // from (or written to) the database: it's either brand new, or a managed
        // document that only has an identifier so far. Either way there is nothing
        // to diff against, so every current field value is recorded as a change
        // and the whole document becomes an INSERT.
        if ($request->originalData === null) {
            return $this->buildInsertChangeSet($class, $document, $actualData);
        }

        // Read-only documents never produce a changeset: whatever their fields
        // say now is irrelevant. We just echo back whatever changeset (if any)
        // the caller already had.
        if ($class->isReadOnly) {
            return $request->existingChangeSet ?? new ChangeSet($document, $request->originalData);
        }

        return $this->buildManagedChangeSet($request, $actualData, $isCollectionScheduledForDeletion);
    }

    /**
     * Builds the changeset for a document being INSERTed: every field is "new"
     * by definition, except the inverse side of a reference, which carries no
     * data of its own (it's populated by, and saved via, the owning side).
     *
     * @param array<string, mixed> $actualData
     * @phpstan-param ClassMetadata<object> $class
     */
    private function buildInsertChangeSet(ClassMetadata $class, object $document, array $actualData): ChangeSet
    {
        $changeSet = new ChangeSet($document, []);
        foreach ($actualData as $propName => $actualValue) {
            if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                continue;
            }

            $changeSet->recordChange($propName, $actualValue);
        }

        return $changeSet;
    }

    /**
     * Diffs a fully managed document's original data against its actual data,
     * field by field. Most of the complexity here is in deciding, per field,
     * whether a value that differs from the snapshot is actually worth
     * recording: associations are recorded unconditionally (so the caller can
     * later derive orphan-removal/collection-deletion scheduling from them),
     * dates need a smarter-than-`===` equivalence check, and some fields are
     * excluded outright (notSaved fields, non-metadata GridFS fields, inverse
     * references, and — under NOTIFY change tracking — anything that isn't
     * itself an association, since NOTIFY already recorded real changes as
     * they happened).
     *
     * @param array<string, mixed>                                            $actualData
     * @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion
     */
    private function buildManagedChangeSet(ChangeSetComputationRequest $request, array $actualData, Closure $isCollectionScheduledForDeletion): ChangeSet
    {
        $class                  = $request->class;
        $document               = $request->document;
        $originalData           = $request->originalData;
        $isChangeTrackingNotify = $request->isChangeTrackingNotify;

        // Under NOTIFY change tracking, the document itself pushes field-level
        // changes into an existing changeset as they happen; that changeset is
        // reused (and merely extended below) instead of being diffed from
        // scratch — unless the caller explicitly asked to recompute, in which
        // case we fall back to a full diff against the snapshot, same as any
        // other tracking policy.
        $changeSet = $isChangeTrackingNotify && ! $request->forceRecompute && $request->existingChangeSet !== null
            ? $request->existingChangeSet
            : new ChangeSet($document, $originalData);

        $gridFsMetadataProperty = $this->resolveGridFsMetadataProperty($class);

        foreach ($actualData as $propName => $actualValue) {
            if ($this->isUnsavedField($class, $propName, $gridFsMetadataProperty)) {
                continue;
            }

            $orgValue = $originalData[$propName] ?? null;

            if (! $this->hasFieldValueChanged($orgValue, $actualValue, $isCollectionScheduledForDeletion)) {
                continue;
            }

            // Single-valued embeds, and the owning side of single-valued
            // references, are always recorded once they differ: the caller
            // relies on the field being present in the changeset to know it
            // must schedule orphan removal for the old value.
            $isSingleValuedAssociation = $class->isSingleValuedEmbed($propName)
                || ($class->isSingleValuedReference($propName) && $class->fieldMappings[$propName]['isOwningSide']);
            if ($isSingleValuedAssociation) {
                $changeSet->recordChange($propName, $actualValue);
                continue;
            }

            if ($isChangeTrackingNotify) {
                continue;
            }

            // The inverse side of a reference has no data of its own to persist.
            if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                continue;
            }

            // To-many embeds/references are always recorded once dirty or
            // replaced, for the same reason as single-valued associations above.
            if ($class->isCollectionValuedAssociation($propName)) {
                $changeSet->recordChange($propName, $actualValue);
                continue;
            }

            if ($this->isEquivalentDate($class, $propName, $orgValue, $actualValue)) {
                continue;
            }

            $changeSet->recordChange($propName, $actualValue);
        }

        return $changeSet;
    }

    /**
     * Fields flagged `notSaved` are computed/derived and are never diffed. A
     * GridFS file document is a special case on top of that: only its
     * `metadata` field (whatever its PHP property is actually called) is
     * writable, so every other field is skipped regardless of mapping.
     *
     * @phpstan-param ClassMetadata<object> $class
     */
    private function isUnsavedField(ClassMetadata $class, string $propName, string|null $gridFsMetadataProperty): bool
    {
        if (($class->fieldMappings[$propName]['notSaved'] ?? false) === true) {
            return true;
        }

        return $class->isFile && $propName !== $gridFsMetadataProperty;
    }

    /**
     * Resolves the PHP field name backing a GridFS file's `metadata` DB field,
     * or null if the class isn't a GridFS file (or, defensively, doesn't map
     * that field at all).
     *
     * @phpstan-param ClassMetadata<object> $class
     */
    private function resolveGridFsMetadataProperty(ClassMetadata $class): string|null
    {
        if (! $class->isFile) {
            return null;
        }

        try {
            return $class->getFieldMappingByDbFieldName('metadata')['fieldName'];
        } catch (MappingException) {
            return null;
        }
    }

    /**
     * A field counts as unchanged only when its value is strictly identical to
     * the snapshot. Persistent collections are the one exception: even the
     * very same collection instance counts as "changed" when it has pending
     * element changes, or is already scheduled for deletion, since a
     * collection's elements are mutated in place rather than the collection
     * being replaced wholesale.
     *
     * @param Closure(PersistentCollectionInterface<array-key, object>): bool $isCollectionScheduledForDeletion
     */
    private function hasFieldValueChanged(mixed $orgValue, mixed $actualValue, Closure $isCollectionScheduledForDeletion): bool
    {
        if ($orgValue !== $actualValue) {
            return true;
        }

        return $actualValue instanceof PersistentCollectionInterface
            && ($actualValue->isDirty() || $isCollectionScheduledForDeletion($actualValue));
    }

    /**
     * Dates are compared via their DB representation with loose equality
     * rather than `===`, because two distinct DateTime-family objects (or a
     * DateTime vs. a DateTimeImmutable) can represent the exact same instant,
     * including microseconds, without being identical.
     *
     * @phpstan-param ClassMetadata<object> $class
     */
    private function isEquivalentDate(ClassMetadata $class, string $propName, mixed $orgValue, mixed $actualValue): bool
    {
        if (($class->fieldMappings[$propName]['type'] ?? null) !== 'date') {
            return false;
        }

        $dateType      = $class->getFieldType($propName);
        $dbOrgValue    = $dateType->convertToDatabaseValue($orgValue);
        $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

        // Loose comparison is only safe when both values are UTC dates. A custom
        // type overriding "date" may produce a different database representation.
        if (! $dbOrgValue instanceof UTCDateTime || ! $dbActualValue instanceof UTCDateTime) {
            return false;
        }

        // We rely on loose comparison to compare every field (including microseconds)
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        return $dbOrgValue == $dbActualValue;
    }
}
