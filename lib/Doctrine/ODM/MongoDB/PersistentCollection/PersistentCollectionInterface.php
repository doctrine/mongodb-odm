<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Interface for persistent collection classes.
 *
 * @internal
 *
 * @phpstan-import-type FieldMapping from \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
 * @phpstan-import-type Hints from UnitOfWork
 *
 * @template TKey of array-key
 * @template T of object
 * @template-extends Collection<TKey, T>
 */
interface PersistentCollectionInterface extends Collection
{
    /**
     * Sets the document manager and unit of work (used during merge operations).
     *
     * @return void
     */
    public function setDocumentManager(DocumentManager $dm);

    /**
     * Sets the array of raw mongo data that will be used to initialize this collection.
     *
     * @param mixed[] $mongoData
     *
     * @return void
     */
    public function setMongoData(array $mongoData);

    /**
     * Gets the array of raw mongo data that will be used to initialize this collection.
     *
     * @return mixed[] $mongoData
     */
    public function getMongoData();

    /**
     * Set hints to account for during reconstitution/lookup of the documents.
     *
     * @param Hints $hints
     *
     * @return void
     */
    public function setHints(array $hints);

    /**
     * Get hints to account for during reconstitution/lookup of the documents.
     *
     * @return Hints $hints
     */
    public function getHints();

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     *
     * @return void
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
     *
     * @return void
     */
    public function setDirty($dirty);

    /**
     * Sets the collection's owning document together with the AssociationMapping that
     * describes the association between the owner and the elements of the collection.
     *
     * @phpstan-param FieldMapping $mapping
     *
     * @return void
     */
    public function setOwner(object $document, array $mapping);

    /**
     * Tells this collection to take a snapshot of its current state reindexing
     * itself numerically if using save strategy that is enforcing BSON array.
     * Reindexing is safe as snapshot is taken only after synchronizing collection
     * with database or clearing it.
     *
     * @return void
     */
    public function takeSnapshot();

    /**
     * Clears the internal snapshot information and sets isDirty to true if the collection
     * has elements.
     *
     * @return void
     */
    public function clearSnapshot();

    /**
     * Returns the last snapshot of the elements in the collection.
     *
     * @return object[] The last snapshot of the elements.
     */
    public function getSnapshot();

    /** @return array<string, object> */
    public function getDeleteDiff();

    /**
     * Get objects that were removed, unlike getDeleteDiff this doesn't care about indices.
     *
     * @return list<object>
     */
    public function getDeletedDocuments();

    /** @return array<string, object> */
    public function getInsertDiff();

    /**
     * Get objects that were added, unlike getInsertDiff this doesn't care about indices.
     *
     * @return list<object>
     */
    public function getInsertedDocuments();

    /**
     * Gets the collection owner.
     */
    public function getOwner(): ?object;

    /**
     * @return array
     * @phpstan-return FieldMapping
     */
    public function getMapping();

    /**
     * @return ClassMetadata
     * @phpstan-return ClassMetadata<T>
     *
     * @throws MongoDBException
     */
    public function getTypeClass();

    /**
     * Sets the initialized flag of the collection, forcing it into that state.
     *
     * @param bool $bool
     *
     * @return void
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
     * @return Collection<TKey, T>
     */
    public function unwrap();
}
