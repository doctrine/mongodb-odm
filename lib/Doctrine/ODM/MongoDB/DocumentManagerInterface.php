<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\Persistence\ObjectManager;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;

interface DocumentManagerInterface extends ObjectManager
{
    /**
     * Returns the MongoDB instance for a class.
     */
    public function getDocumentDatabase(string $className) : Database;

    /**
     * Returns the collection instance for a class.
     *
     * @throws MongoDBException When the $className param is not mapped to a collection.
     */
    public function getDocumentCollection(string $className) : Collection;

    /**
     * Returns the bucket instance for a class.
     *
     * @throws MongoDBException When the $className param is not mapped to a collection.
     */
    public function getDocumentBucket(string $className) : Bucket;

    /**
     * Create a new Query instance for a class.
     *
     * @param string[]|string|null $documentName (optional) an array of document names, the document name, or none
     */
    public function createQueryBuilder($documentName = null) : Query\Builder;

    /**
     * Creates a new aggregation builder instance for a class.
     */
    public function createAggregationBuilder(string $documentName) : Aggregation\Builder;

    /**
     * Gets a reference to the document identified by the given type and identifier
     * without actually loading it.
     *
     * If partial objects are allowed, this method will return a partial object that only
     * has its identifier populated. Otherwise a proxy is returned that automatically
     * loads itself on first access.
     *
     * @param mixed $identifier
     */
    public function getReference(string $documentName, $identifier) : object;

    /**
     * Gets a partial reference to the document identified by the given type and identifier
     * without actually loading it, if the document is not yet loaded.
     *
     * The returned reference may be a partial object if the document is not yet loaded/managed.
     * If it is a partial object it will not initialize the rest of the document state on access.
     * Thus you can only ever safely access the identifier of a document obtained through
     * this method.
     *
     * The use-cases for partial references involve maintaining bidirectional associations
     * without loading one side of the association or to update a document without loading it.
     * Note, however, that in the latter case the original (persistent) document data will
     * never be visible to the application (especially not event listeners) as it will
     * never be loaded in the first place.
     *
     * @param mixed $identifier The document identifier.
     */
    public function getPartialReference(string $documentName, $identifier) : object;

    /**
     * Finds a Document by its identifier.
     *
     * This is just a convenient shortcut for getRepository($documentName)->find($id).
     *
     * @param string $documentName
     * @param mixed  $identifier
     * @param int    $lockMode
     * @param int    $lockVersion
     */
    public function find($documentName, $identifier, $lockMode = LockMode::NONE, $lockVersion = null) : ?object;

    /**
     * Closes the DocumentManager. All documents that are currently managed
     * by this DocumentManager become detached. The DocumentManager may no longer
     * be used after it is closed.
     */
    public function close();

    /**
     * Check if the Document manager is open or closed.
     */
    public function isOpen() : bool;

    /**
     * Gets the filter collection.
     */
    public function getFilterCollection() : FilterCollection;
}
