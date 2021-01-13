<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;

use function get_class;
use function sprintf;

/**
 * Class that holds event arguments for a preUpdate event.
 */
final class PreUpdateEventArgs extends LifecycleEventArgs
{
    /** @var array */
    private $documentChangeSet;

    public function __construct(object $document, DocumentManager $dm, array $changeSet)
    {
        parent::__construct($document, $dm);
        $this->documentChangeSet = $changeSet;
    }

    public function getDocumentChangeSet(): array
    {
        return $this->documentChangeSet;
    }

    public function hasChangedField(string $field): bool
    {
        return isset($this->documentChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @return mixed
     */
    public function getOldValue(string $field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @return mixed
     */
    public function getNewValue(string $field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param mixed $value
     */
    public function setNewValue(string $field, $value): void
    {
        $this->assertValidField($field);

        $this->documentChangeSet[$field][1] = $value;
        $this->getDocumentManager()->getUnitOfWork()->setDocumentChangeSet($this->getDocument(), $this->documentChangeSet);
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @throws InvalidArgumentException If the field has no changeset.
     */
    private function assertValidField(string $field): void
    {
        if (! isset($this->documentChangeSet[$field])) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getDocument())
            ));
        }
    }
}
