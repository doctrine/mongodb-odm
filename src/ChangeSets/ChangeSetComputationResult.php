<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\ChangeSets;

use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;

/**
 * The result of {@see ChangeSetComputer::computeChangeSet()}: the computed
 * changeset (already merged onto an existing one, if one was passed in),
 * plus what the caller needs to schedule as a consequence of this pass's
 * diff. The computer does not schedule anything itself; the caller derives
 * orphan removal / collection deletion from these fields.
 *
 * @internal
 */
final class ChangeSetComputationResult
{
    /**
     * @param list<object>                                           $orphansToRemove
     * @param list<PersistentCollectionInterface<array-key, object>> $collectionsToDelete
     */
    public function __construct(
        public readonly ChangeSet $changeSet,
        public readonly bool $isNewDocument,
        public readonly array $orphansToRemove = [],
        public readonly array $collectionsToDelete = [],
    ) {
    }
}
