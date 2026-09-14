<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Bundles the long-lived state tracked for a single document while it is
 * known to the DocumentManager: its persistence state, the snapshot of data
 * used for dirty checking at flush time, and (for embedded documents) the
 * parent association it was found through.
 *
 * @internal This class is not part of the public API and is subject to change.
 */
final class ManagedObjectState
{
    public ?ParentAssociation $parentAssociation = null;

    /**
     * $originalData is null until it is recorded for the first time (e.g. a
     * document that has been persisted but not yet flushed); it is never
     * reset back to null afterwards, even for an empty snapshot.
     *
     * @param array<string, mixed>|null $originalData
     */
    public function __construct(
        public PersistenceState $state,
        public ?array $originalData = null,
    ) {
    }
}
