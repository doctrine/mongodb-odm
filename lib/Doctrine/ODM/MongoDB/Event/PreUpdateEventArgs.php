<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use InvalidArgumentException;
use MongoDB\Driver\Session;

use function get_class;
use function sprintf;

/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @phpstan-import-type ChangeSet from UnitOfWork
 */
final class PreUpdateEventArgs extends LifecycleEventArgs
{
    /** @param array<string, ChangeSet> $changeSet */
    public function __construct(
        object $document,
        DocumentManager $dm,
        private array $changeSet,
        ?Session $session = null,
    ) {
        parent::__construct($document, $dm, $session);

        $this->changeSet = $changeSet;
    }

    /** @return array<string, ChangeSet> */
    public function getDocumentChangeSet(): array
    {
        return $this->changeSet;
    }

    public function hasChangedField(string $field): bool
    {
        return isset($this->changeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @return mixed
     */
    public function getOldValue(string $field)
    {
        $this->assertValidField($field);

        return $this->changeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @return mixed
     */
    public function getNewValue(string $field)
    {
        $this->assertValidField($field);

        return $this->changeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param mixed $value
     */
    public function setNewValue(string $field, $value): void
    {
        $this->assertValidField($field);

        $this->changeSet[$field][1] = $value;
        $this->getDocumentManager()->getUnitOfWork()->setDocumentChangeSet($this->getDocument(), $this->changeSet);
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @throws InvalidArgumentException If the field has no changeset.
     */
    private function assertValidField(string $field): void
    {
        if (! isset($this->changeSet[$field])) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getDocument()),
            ));
        }
    }
}
