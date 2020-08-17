<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Proxy\Factory\ProxyFactory;
use Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver;
use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;
use RuntimeException;

/**
 * The DocumentManager Interface 
 */
interface DocumentManagerInterface extends ObjectManager
{
    /**
     * Gets the proxy factory used by the DocumentManager to create document proxies.
     */
    public function getProxyFactory() : ProxyFactory;

    /**
     * Creates a new Document that operates on the given Mongo connection
     * and uses the given Configuration.
     */
    public static function create(?Client $client = null, ?Configuration $config = null, ?EventManager $eventManager = null) : DocumentManagerInterface;

    /**
     * Gets the EventManager used by the DocumentManager.
     */
    public function getEventManager() : EventManager;

    /**
     * Gets the MongoDB client instance that this DocumentManager wraps.
     */
    public function getClient() : Client;

    /**
     * Gets the UnitOfWork used by the DocumentManager to coordinate operations.
     */
    public function getUnitOfWork() : UnitOfWork;

    /**
     * Gets the Hydrator factory used by the DocumentManager to generate and get hydrators
     * for each type of document.
     */
    public function getHydratorFactory() : HydratorFactory;

    /**
     * Returns SchemaManager, used to create/drop indexes/collections/databases.
     */
    public function getSchemaManager() : SchemaManager;

    /** Returns the class name resolver which is used to resolve real class names for proxy objects. */
    public function getClassNameResolver() : ClassNameResolver;

    /**
     * Returns the MongoDB instance for a class.
     */
    public function getDocumentDatabase(string $className) : Database;

    /**
     * Gets the array of instantiated document database instances.
     *
     * @return Database[]
     */
    public function getDocumentDatabases() : array;

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
     * Gets the array of instantiated document collection instances.
     *
     * @return Collection[]
     */
    public function getDocumentCollections() : array;

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
     * Acquire a lock on the given document.
     *
     * @throws InvalidArgumentException
     * @throws LockException
     */
    public function lock(object $document, int $lockMode, ?int $lockVersion = null) : void;

    /**
     * Releases a lock on the given document.
     */
    public function unlock(object $document) : void;

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
     * Gets the Configuration used by the DocumentManager.
     */
    public function getConfiguration() : Configuration;

    /**
     * Returns a reference to the supplied document.
     *
     * @return mixed The reference for the document in question, according to the desired mapping
     *
     * @throws MappingException
     * @throws RuntimeException
     */
    public function createReference(object $document, array $referenceMapping);
    /**
     * Check if the Document manager is open or closed.
     */
    public function isOpen() : bool;

    /**
     * Gets the filter collection.
     */
    public function getFilterCollection() : FilterCollection;
    
    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return ClassMetadataFactory
     */
    public function getMetadataFactory();


    /**
     * Returns the metadata for a class.
     *
     * @param string $className The class name.
     */
    public function getClassMetadata($className) : ClassMetadata;
}
