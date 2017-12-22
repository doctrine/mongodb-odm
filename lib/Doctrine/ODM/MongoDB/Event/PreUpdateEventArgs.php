<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @since 1.0
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $documentChangeSet;

    /**
     * Constructor.
     *
     * @param object          $document
     * @param DocumentManager $dm
     * @param array           $changeSet
     */
    public function __construct($document, DocumentManager $dm, array $changeSet)
    {
        parent::__construct($document, $dm);
        $this->documentChangeSet = $changeSet;
    }

    /**
     * Retrieves the document changeset.
     *
     * @return array
     */
    public function getDocumentChangeSet()
    {
        return $this->documentChangeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChangedField($field)
    {
        return isset($this->documentChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed  $value
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->documentChangeSet[$field][1] = $value;
        $this->getDocumentManager()->getUnitOfWork()->setDocumentChangeSet($this->getDocument(), $this->documentChangeSet);
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @param string $field
     * @throws \InvalidArgumentException if the field has no changeset
     */
    private function assertValidField($field)
    {
        if ( ! isset($this->documentChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getDocument())
            ));
        }
    }
}
