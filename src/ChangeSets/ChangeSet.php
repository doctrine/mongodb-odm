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
 * @phpstan-type ChangeSetArray array<string, array{mixed, mixed}>
 */
final class ChangeSet
{
    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $newValues
     */
    public function __construct(
        private readonly object $document,
        private readonly array $originalData,
        private array $newValues = [],
    ) {
    }

    public function getDocument(): object
    {
        return $this->document;
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
     * Overwrites the new value of an already-changed field.
     *
     * @throws InvalidArgumentException If the field has no recorded change.
     */
    public function setNewValue(string $field, mixed $value): void
    {
        $this->assertFieldChanged($field);

        $this->newValues[$field] = $value;
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
     * @phpstan-return ChangeSetArray
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
