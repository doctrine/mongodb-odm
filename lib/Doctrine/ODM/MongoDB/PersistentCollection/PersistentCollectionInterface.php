<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Interface for persistent collection classes.
 *
 * @internal
 */
interface PersistentCollectionInterface extends Collection
{
    /**
     * Sets the document manager and unit of work (used during merge operations).
     */
    public function setDocumentManager(DocumentManager $dm);

    /**
     * Sets the array of raw mongo data that will be used to initialize this collection.
     *
     * @param array $mongoData
     */
    public function setMongoData(array $mongoData);

    /**
     * Gets the array of raw mongo data that will be used to initialize this collection.
     *
     * @return array $mongoData
     */
    public function getMongoData();

    /**
     * Set hints to account for during reconstitution/lookup of the documents.
     *
     * @param array $hints
     */
    public function setHints(array $hints);

    /**
     * Get hints to account for during reconstitution/lookup of the documents.
     *
     * @return array $hints
     */
    public function getHints();

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize();

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return bool TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty();

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param bool $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty);

    /**
     * Sets the collection's owning entity together with the AssociationMapping that
     * describes the association between the owner and the elements of the collection.
     */
    public function setOwner(object $document, array $mapping);

    /**
     * Tells this collection to take a snapshot of its current state reindexing
     * itself numerically if using save strategy that is enforcing BSON array.
     * Reindexing is safe as snapshot is taken only after synchronizing collection
     * with database or clearing it.
     */
    public function takeSnapshot();

    /**
     * Clears the internal snapshot information and sets isDirty to true if the collection
     * has elements.
     */
    public function clearSnapshot();

    /**
     * Returns the last snapshot of the elements in the collection.
     *
     * @return array The last snapshot of the elements.
     */
    public function getSnapshot();

    /**
     * @return array
     */
    public function getDeleteDiff();

    /**
     * Get objects that were removed, unlike getDeleteDiff this doesn't care about indices.
     *
     * @return array
     */
    public function getDeletedDocuments();

    /**
     * @return array
     */
    public function getInsertDiff();

    /**
     * Get objects that were added, unlike getInsertDiff this doesn't care about indices.
     *
     * @return array
     */
    public function getInsertedDocuments();

    /**
     * Gets the collection owner.
     */
    public function getOwner(): ?object;

    /**
     * @return array
     */
    public function getMapping();

    /**
     * @return ClassMetadata
     *
     * @throws MongoDBException
     */
    public function getTypeClass();

    /**
     * Sets the initialized flag of the collection, forcing it into that state.
     *
     * @param bool $bool
     */
    public function setInitialized($bool);

    /**
     * Checks whether this collection has been initialized.
     *
     * @return bool
     */
    public function isInitialized();

    /**
     * Returns the wrapped Collection instance.
     *
     * @return Collection
     */
    public function unwrap();
}
