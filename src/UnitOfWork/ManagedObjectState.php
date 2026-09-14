<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\UnitOfWork;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Bundles the long-lived state tracked for a single document while it is
 * known to the DocumentManager: its persistence state, the snapshot of data
 * used for dirty checking at flush time, and (for embedded documents) the
 * parent association it was found through.
 *
 * @internal This class is not part of the public API and is subject to change.
 *
 * @phpstan-import-type AssociationFieldMapping from ClassMetadata
 */
final class ManagedObjectState
{
    /** @phpstan-var array{0: AssociationFieldMapping, 1: object|null, 2: string}|null */
    private ?array $parentAssociation = null;

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

    /** @phpstan-return array{0: AssociationFieldMapping, 1: object|null, 2: string}|null */
    public function getParentAssociation(): ?array
    {
        return $this->parentAssociation;
    }

    /** @phpstan-param AssociationFieldMapping $mapping */
    public function setParentAssociation(array $mapping, ?object $parent, string $propertyPath): void
    {
        $this->parentAssociation = [$mapping, $parent, $propertyPath];
    }
}
