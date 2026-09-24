<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\ChangeSets;

use InvalidArgumentException;

use function array_key_exists;
use function array_keys;
use function array_replace;
use function get_class;
use function sprintf;

/**
 * The set of field changes detected for a single document during changeset
 * computation, together with the original data snapshot the changes were
 * diffed against.
 *
 * Old values are never stored directly: {@see self::getOldValue()} always
 * derives them from the original data snapshot the instance was constructed
 * with, which this class never mutates itself.
 *
 * TODO: $originalData is a snapshot copied at construction time, not a live
 * view of the document's actual state. Every caller that builds a ChangeSet
 * from a partial view of that state (see UnitOfWork::setDocumentChangeSet())
 * risks the same class of desync bug. Holding a reference to the document's
 * ManagedObjectState instead would close this structurally, but needs the
 * insert path (which stamps the object state's originalData immediately
 * after diffing) reworked first so "old" values don't silently become "new".
 *
 * @phpstan-type LegacyChangeSet array{mixed, mixed}
 * @phpstan-type LegacyChangeSetArray array<string, LegacyChangeSet>
 */
final class ChangeSet
{
    /** @var array<string, mixed> */
    private array $newValues = [];

    /** @param array<string, mixed> $originalData */
    public function __construct(
        public readonly object $document,
        private readonly array $originalData,
    ) {
    }

    /**
     * Builds a changeset that already carries changes, e.g. from the legacy
     * array shape passed to {@see \Doctrine\ODM\MongoDB\UnitOfWork::setDocumentChangeSet()}.
     *
     * @internal
     *
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $newValues
     */
    public static function fromChanges(object $document, array $originalData, array $newValues): self
    {
        $changeSet            = new self($document, $originalData);
        $changeSet->newValues = $newValues;

        return $changeSet;
    }

    public function isEmpty(): bool
    {
        return $this->newValues === [];
    }

    /** @return list<string> */
    public function getFieldNames(): array
    {
        return array_keys($this->newValues);
    }

    public function hasChangedField(string $field): bool
    {
        return array_key_exists($field, $this->newValues);
    }

    public function getOldValue(string $field): mixed
    {
        $this->assertFieldChanged($field);

        return $this->originalData[$field] ?? null;
    }

    public function getNewValue(string $field): mixed
    {
        $this->assertFieldChanged($field);

        return $this->newValues[$field];
    }

    /**
     * Records a field as changed, upserting its new value. The old value is
     * never provided here: it is always read from the original data snapshot.
     *
     * @internal
     */
    public function recordChange(string $field, mixed $newValue): void
    {
        $this->newValues[$field] = $newValue;
    }

    /**
     * Copies every recorded change from $other onto this instance, overwriting
     * a new value already recorded here for the same field. Old values are
     * unaffected — they stay anchored to this instance's own original-data
     * snapshot, never $other's.
     *
     * @internal
     */
    public function merge(self $other): void
    {
        foreach ($other->getFieldNames() as $field) {
            $this->recordChange($field, $other->getNewValue($field));
        }
    }

    /**
     * The original data snapshot with all recorded changes merged in — the
     * new baseline once the document has actually been persisted.
     *
     * @return array<string, mixed>
     */
    public function applyToOriginalData(): array
    {
        return array_replace($this->originalData, $this->newValues);
    }

    /**
     * @return array<string, array{mixed, mixed}>
     * @phpstan-return LegacyChangeSetArray
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->newValues as $field => $newValue) {
            $result[$field] = [$this->originalData[$field] ?? null, $newValue];
        }

        return $result;
    }

    /** @throws InvalidArgumentException If the field has no recorded change. */
    private function assertFieldChanged(string $field): void
    {
        if (! $this->hasChangedField($field)) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in ChangeSet.',
                $field,
                get_class($this->document),
            ));
        }
    }
}
