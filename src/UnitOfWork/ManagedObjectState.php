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

    /** @param array<string, mixed>|null $originalData */
    public function __construct(private PersistenceState $state, private ?array $originalData = null)
    {
    }

    public function getState(): PersistenceState
    {
        return $this->state;
    }

    public function setState(PersistenceState $state): void
    {
        $this->state = $state;
    }

    /**
     * Whether original data has ever been recorded for this document.
     */
    public function hasOriginalData(): bool
    {
        return $this->originalData !== null;
    }

    /** @return array<string, mixed> */
    public function getOriginalData(): array
    {
        return $this->originalData ?? [];
    }

    /** @param array<string, mixed> $data */
    public function setOriginalData(array $data): void
    {
        $this->originalData = $data;
    }

    public function setOriginalDataField(string $field, mixed $value): void
    {
        $this->originalData[$field] = $value;
    }

    public function clearOriginalData(): void
    {
        $this->originalData = null;
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
