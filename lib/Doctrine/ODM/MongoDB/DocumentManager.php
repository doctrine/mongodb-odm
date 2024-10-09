<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Proxy\Factory\ProxyFactory;
use Doctrine\ODM\MongoDB\Proxy\Factory\StaticProxyFactory;
use Doctrine\ODM\MongoDB\Proxy\Resolver\CachingClassNameResolver;
use Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver;
use Doctrine\ODM\MongoDB\Proxy\Resolver\ProxyManagerClassNameResolver;
use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Jean85\PrettyVersions;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\ReadPreference;
use MongoDB\GridFS\Bucket;
use ProxyManager\Proxy\GhostObjectInterface;
use RuntimeException;
use Throwable;

use function array_search;
use function assert;
use function gettype;
use function is_object;
use function ltrim;
use function sprintf;
use function trigger_deprecation;

/**
 * The DocumentManager class is the central access point for managing the
 * persistence of documents.
 *
 *     <?php
 *
 *     $config = new Configuration();
 *     $dm = DocumentManager::create(new Connection(), $config);
 *
 * @phpstan-import-type CommitOptions from UnitOfWork
 * @phpstan-import-type FieldMapping from ClassMetadata
 */
class DocumentManager implements ObjectManager
{
    public const CLIENT_TYPEMAP = ['root' => 'array', 'document' => 'array'];

    /**
     * The Doctrine MongoDB connection instance.
     */
    private Client $client;

    /**
     * The used Configuration.
     */
    private Configuration $config;

    /**
     * The metadata factory, used to retrieve the ODM metadata of document classes.
     */
    private ClassMetadataFactoryInterface $metadataFactory;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     */
    private UnitOfWork $unitOfWork;

    /**
     * The event manager that is the central point of the event system.
     */
    private EventManager $eventManager;

    /**
     * The Hydrator factory instance.
     */
    private HydratorFactory $hydratorFactory;

    /**
     * The Proxy factory instance.
     */
    private ProxyFactory $proxyFactory;

    /**
     * The repository factory used to create dynamic repositories.
     */
    private RepositoryFactory $repositoryFactory;

    /**
     * SchemaManager instance
     */
    private SchemaManager $schemaManager;

    /**
     * Array of cached document database instances that are lazily loaded.
     *
     * @var Database[]
     */
    private array $documentDatabases = [];

    /**
     * Array of cached document collection instances that are lazily loaded.
     *
     * @var Collection[]
     */
    private array $documentCollections = [];

    /**
     * Array of cached document bucket instances that are lazily loaded.
     *
     * @var Bucket[]
     */
    private array $documentBuckets = [];

    /**
     * Whether the DocumentManager is closed or not.
     */
    private bool $closed = false;

    /**
     * Collection of query filters.
     */
    private ?FilterCollection $filterCollection = null;

    /** @var ProxyClassNameResolver&ClassNameResolver  */
    private ProxyClassNameResolver $classNameResolver;

    private static ?string $version = null;

    /**
     * Creates a new Document that operates on the given Mongo connection
     * and uses the given Configuration.
     */
    protected function __construct(?Client $client = null, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->config       = $config ?: new Configuration();
        $this->eventManager = $eventManager ?: new EventManager();
        $this->client       = $client ?: new Client(
            'mongodb://127.0.0.1',
            [],
            [
                'driver' => [
                    'name' => 'doctrine-odm',
                    'version' => self::getVersion(),
                ],
            ],
        );

        $this->classNameResolver = new CachingClassNameResolver(new ProxyManagerClassNameResolver($this->config));

        $metadataFactoryClassName = $this->config->getClassMetadataFactoryName();
        $this->metadataFactory    = new $metadataFactoryClassName();
        $this->metadataFactory->setDocumentManager($this);
        $this->metadataFactory->setConfiguration($this->config);
        $this->metadataFactory->setProxyClassNameResolver($this->classNameResolver);

        $cacheDriver = $this->config->getMetadataCache();
        if ($cacheDriver) {
            $this->metadataFactory->setCache($cacheDriver);
        }

        $hydratorDir           = $this->config->getHydratorDir();
        $hydratorNs            = $this->config->getHydratorNamespace();
        $this->hydratorFactory = new HydratorFactory(
            $this,
            $this->eventManager,
            $hydratorDir,
            $hydratorNs,
            $this->config->getAutoGenerateHydratorClasses(),
        );

        $this->unitOfWork        = new UnitOfWork($this, $this->eventManager, $this->hydratorFactory);
        $this->schemaManager     = new SchemaManager($this, $this->metadataFactory);
        $this->proxyFactory      = new StaticProxyFactory($this);
        $this->repositoryFactory = $this->config->getRepositoryFactory();
    }

    /**
     * Gets the proxy factory used by the DocumentManager to create document proxies.
     */
    public function getProxyFactory(): ProxyFactory
    {
        return $this->proxyFactory;
    }

    /**
     * Creates a new Document that operates on the given Mongo connection
     * and uses the given Configuration.
     */
    public static function create(?Client $client = null, ?Configuration $config = null, ?EventManager $eventManager = null): DocumentManager
    {
        return new static($client, $config, $eventManager);
    }

    /**
     * Gets the EventManager used by the DocumentManager.
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    /**
     * Gets the MongoDB client instance that this DocumentManager wraps.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return ClassMetadataFactoryInterface
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * This method is a no-op for other objects.
     *
     * @param object $obj
     */
    public function initializeObject($obj)
    {
        $this->unitOfWork->initializeObject($obj);
    }

    /**
     * Helper method to check whether a lazy loading proxy or persistent collection has been initialized.
     */
    public function isUninitializedObject(object $obj): bool
    {
        return $this->unitOfWork->isUninitializedObject($obj);
    }

    /**
     * Gets the UnitOfWork used by the DocumentManager to coordinate operations.
     */
    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    /**
     * Gets the Hydrator factory used by the DocumentManager to generate and get hydrators
     * for each type of document.
     */
    public function getHydratorFactory(): HydratorFactory
    {
        return $this->hydratorFactory;
    }

    /**
     * Returns SchemaManager, used to create/drop indexes/collections/databases.
     */
    public function getSchemaManager(): SchemaManager
    {
        return $this->schemaManager;
    }

    /**
     * Returns the class name resolver which is used to resolve real class names for proxy objects.
     *
     * @deprecated Fetch metadata for any class string (e.g. proxy object class) and read the class name from the metadata object
     */
    public function getClassNameResolver(): ClassNameResolver
    {
        return $this->classNameResolver;
    }

    /**
     * Returns the metadata for a class.
     *
     * @param class-string<T> $className The class name.
     *
     * @return ClassMetadata<T>
     *
     * @template T of object
     */
    public function getClassMetadata($className): ClassMetadata
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * Returns the MongoDB instance for a class.
     *
     * @param class-string $className
     */
    public function getDocumentDatabase(string $className): Database
    {
        $metadata = $this->metadataFactory->getMetadataFor($className);

        $className = $metadata->getName();

        if (isset($this->documentDatabases[$className])) {
            return $this->documentDatabases[$className];
        }

        $db                                  = $metadata->getDatabase();
        $db                                  = $db ?: $this->config->getDefaultDB();
        $db                                  = $db ?: 'doctrine';
        $this->documentDatabases[$className] = $this->client->selectDatabase($db);

        return $this->documentDatabases[$className];
    }

    /**
     * Gets the array of instantiated document database instances.
     *
     * @return Database[]
     */
    public function getDocumentDatabases(): array
    {
        return $this->documentDatabases;
    }

    /**
     * Returns the collection instance for a class.
     *
     * @throws MongoDBException When the $className param is not mapped to a collection.
     */
    public function getDocumentCollection(string $className): Collection
    {
        $metadata = $this->metadataFactory->getMetadataFor($className);

        if ($metadata->isFile) {
            return $this->getDocumentBucket($className)->getFilesCollection();
        }

        $collectionName = $metadata->getCollection();

        if (! $collectionName) {
            throw MongoDBException::documentNotMappedToCollection($className);
        }

        if (! isset($this->documentCollections[$className])) {
            $db = $this->getDocumentDatabase($className);

            $options = ['typeMap' => self::CLIENT_TYPEMAP];
            if ($metadata->readPreference !== null) {
                $options['readPreference'] = new ReadPreference($metadata->readPreference, $metadata->readPreferenceTags);
            }

            $this->documentCollections[$className] = $db->selectCollection($collectionName, $options);
        }

        return $this->documentCollections[$className];
    }

    /**
     * Returns the bucket instance for a class.
     *
     * @throws MongoDBException When the $className param is not mapped to a collection.
     */
    public function getDocumentBucket(string $className): Bucket
    {
        $metadata = $this->metadataFactory->getMetadataFor($className);

        if (! $metadata->isFile) {
            throw MongoDBException::documentBucketOnlyAvailableForGridFSFiles($className);
        }

        $bucketName = $metadata->getBucketName();

        if (! $bucketName) {
            throw MongoDBException::documentNotMappedToCollection($className);
        }

        if (! isset($this->documentBuckets[$className])) {
            $db = $this->getDocumentDatabase($className);

            $options = ['bucketName' => $bucketName, 'typeMap' => self::CLIENT_TYPEMAP];
            if ($metadata->readPreference !== null) {
                $options['readPreference'] = new ReadPreference($metadata->readPreference, $metadata->readPreferenceTags);
            }

            $this->documentBuckets[$className] = $db->selectGridFSBucket($options);
        }

        return $this->documentBuckets[$className];
    }

    /**
     * Gets the array of instantiated document collection instances.
     *
     * @return Collection[]
     */
    public function getDocumentCollections(): array
    {
        return $this->documentCollections;
    }

    /**
     * Create a new Query instance for a class.
     *
     * @param string[]|string|null $documentName (optional) an array of document names, the document name, or none
     */
    public function createQueryBuilder($documentName = null): Query\Builder
    {
        return new Query\Builder($this, $documentName);
    }

    /**
     * Creates a new aggregation builder instance for a class.
     */
    public function createAggregationBuilder(string $documentName): Aggregation\Builder
    {
        return new Aggregation\Builder($this, $documentName);
    }

    /**
     * Tells the DocumentManager to make an instance managed and persistent.
     *
     * The document will be entered into the database at or before transaction
     * commit or as a result of the flush operation.
     *
     * NOTE: The persist operation always considers documents that are not yet known to
     * this DocumentManager as NEW. Do not pass detached documents to the persist operation.
     *
     * @param object $object The instance to make managed and persistent.
     *
     * @throws InvalidArgumentException When the given $object param is not an object.
     */
    public function persist($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        $this->errorIfClosed();
        $this->unitOfWork->persist($object);
    }

    /**
     * Removes a document instance.
     *
     * A removed document will be removed from the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $object The document instance to remove.
     *
     * @throws InvalidArgumentException When the $object param is not an object.
     */
    public function remove($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        $this->errorIfClosed();
        $this->unitOfWork->remove($object);
    }

    /**
     * Refreshes the persistent state of a document from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $object The document to refresh.
     *
     * @throws InvalidArgumentException When the given $object param is not an object.
     */
    public function refresh($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        $this->errorIfClosed();
        $this->unitOfWork->refresh($object);
    }

    /**
     * Detaches a document from the DocumentManager, causing a managed document to
     * become detached.  Unflushed changes made to the document if any
     * (including removal of the document), will not be synchronized to the database.
     * Documents which previously referenced the detached document will continue to
     * reference it.
     *
     * @param object $object The document to detach.
     *
     * @throws InvalidArgumentException When the $object param is not an object.
     */
    public function detach($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        $this->unitOfWork->detach($object);
    }

    /**
     * Merges the state of a detached document into the persistence context
     * of this DocumentManager and returns the managed copy of the document.
     * The document passed to merge will not become associated/managed with this DocumentManager.
     *
     * @param object $object The detached document to merge into the persistence context.
     *
     * @return object The managed copy of the document.
     *
     * @throws LockException
     * @throws InvalidArgumentException If the $object param is not an object.
     */
    public function merge($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        $this->errorIfClosed();

        return $this->unitOfWork->merge($object);
    }

    /**
     * Acquire a lock on the given document.
     *
     * @throws InvalidArgumentException
     * @throws LockException
     */
    public function lock(object $document, int $lockMode, ?int $lockVersion = null): void
    {
        $this->unitOfWork->lock($document, $lockMode, $lockVersion);
    }

    /**
     * Releases a lock on the given document.
     */
    public function unlock(object $document): void
    {
        $this->unitOfWork->unlock($document);
    }

    /**
     * Gets the repository for a document class.
     *
     * @param class-string<T> $className The name of the Document.
     *
     * @return DocumentRepository<T>|GridFSRepository<T>|ViewRepository<T>  The repository.
     *
     * @template T of object
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * @param array $options Array of options to be used with batchInsert(), update() and remove()
     * @phpstan-param CommitOptions $options
     *
     * @throws MongoDBException
     * @throws Throwable From event listeners.
     */
    public function flush(array $options = [])
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit($options);
    }

    /**
     * Gets a reference to the document identified by the given type and identifier
     * without actually loading it.
     *
     * If partial objects are allowed, this method will return a partial object that only
     * has its identifier populated. Otherwise a proxy is returned that automatically
     * loads itself on first access.
     *
     * @param mixed           $identifier
     * @param class-string<T> $documentName
     *
     * @return T|(T&GhostObjectInterface<T>)
     *
     * @template T of object
     */
    public function getReference(string $documentName, $identifier): object
    {
        /** @var ClassMetadata<T> $class */
        $class = $this->metadataFactory->getMetadataFor(ltrim($documentName, '\\'));
        assert($class instanceof ClassMetadata);
        /** @phpstan-var T|false $document */
        $document = $this->unitOfWork->tryGetById($identifier, $class);

        // Check identity map first, if its already in there just return it.
        if ($document !== false) {
            return $document;
        }

        /** @var T&GhostObjectInterface<T> $document */
        $document = $this->proxyFactory->getProxy($class, $identifier);
        $this->unitOfWork->registerManaged($document, $identifier, []);

        return $document;
    }

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
    public function getPartialReference(string $documentName, $identifier): object
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($documentName, '\\'));

        $document = $this->unitOfWork->tryGetById($identifier, $class);

        // Check identity map first, if its already in there just return it.
        if ($document) {
            return $document;
        }

        $document = $class->newInstance();
        $class->setIdentifierValue($document, $identifier);
        $this->unitOfWork->registerManaged($document, $identifier, []);

        return $document;
    }

    /**
     * Finds a Document by its identifier.
     *
     * This is just a convenient shortcut for getRepository($documentName)->find($id).
     *
     * @param class-string<T> $className
     * @param mixed           $id
     * @param int             $lockMode
     * @param int             $lockVersion
     *
     * @return T|null
     *
     * @template T of object
     */
    public function find($className, $id, $lockMode = LockMode::NONE, $lockVersion = null): ?object
    {
        $repository = $this->getRepository($className);
        if ($repository instanceof DocumentRepository) {
            return $repository->find($id, $lockMode, $lockVersion);
        }

        return $repository->find($id);
    }

    /**
     * Clears the DocumentManager.
     *
     * All documents that are currently managed by this DocumentManager become
     * detached.
     *
     * @param string|null $objectName if given, only documents of this type will get detached
     */
    public function clear($objectName = null)
    {
        if ($objectName !== null) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.4',
                'Calling %s() with any arguments to clear specific documents is deprecated and will not be supported in Doctrine ODM 3.0.',
                __METHOD__,
            );
        }

        $this->unitOfWork->clear($objectName);
    }

    /**
     * Closes the DocumentManager. All documents that are currently managed
     * by this DocumentManager become detached. The DocumentManager may no longer
     * be used after it is closed.
     *
     * @return void
     */
    public function close()
    {
        $this->clear();
        $this->closed = true;
    }

    /**
     * Determines whether a document instance is managed in this DocumentManager.
     *
     * @param object $object
     *
     * @return bool TRUE if this DocumentManager currently manages the given document, FALSE otherwise.
     *
     * @throws InvalidArgumentException When the $object param is not an object.
     */
    public function contains($object)
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException(gettype($object));
        }

        return $this->unitOfWork->isScheduledForInsert($object) ||
            $this->unitOfWork->isInIdentityMap($object) &&
            ! $this->unitOfWork->isScheduledForDelete($object);
    }

    /**
     * Gets the Configuration used by the DocumentManager.
     */
    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    /**
     * Returns a reference to the supplied document.
     *
     * @phpstan-param FieldMapping $referenceMapping
     *
     * @return mixed The reference for the document in question, according to the desired mapping
     *
     * @throws MappingException
     * @throws RuntimeException
     */
    public function createReference(object $document, array $referenceMapping)
    {
        $class = $this->getClassMetadata($document::class);
        $id    = $this->unitOfWork->getDocumentIdentifier($document);

        if ($id === null) {
            throw new RuntimeException(
                sprintf('Cannot create a DBRef for class %s without an identifier. Have you forgotten to persist/merge the document first?', $class->name),
            );
        }

        $storeAs = $referenceMapping['storeAs'] ?? null;
        switch ($storeAs) {
            case ClassMetadata::REFERENCE_STORE_AS_ID:
                if ($class->inheritanceType === ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION) {
                    throw MappingException::simpleReferenceMustNotTargetDiscriminatedDocument($referenceMapping['targetDocument']);
                }

                return $class->getDatabaseIdentifierValue($id);

            case ClassMetadata::REFERENCE_STORE_AS_REF:
                $reference = ['id' => $class->getDatabaseIdentifierValue($id)];
                break;

            case ClassMetadata::REFERENCE_STORE_AS_DB_REF:
                $reference = [
                    '$ref' => $class->getCollection(),
                    '$id'  => $class->getDatabaseIdentifierValue($id),
                ];
                break;

            case ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                $reference = [
                    '$ref' => $class->getCollection(),
                    '$id'  => $class->getDatabaseIdentifierValue($id),
                    '$db'  => $this->getDocumentDatabase($class->name)->getDatabaseName(),
                ];
                break;

            default:
                throw new InvalidArgumentException(sprintf('Reference type %s is invalid.', $storeAs));
        }

        return $reference + $this->getDiscriminatorData($referenceMapping, $class);
    }

    /**
     * Build discriminator portion of reference for specified reference mapping and class metadata.
     *
     * @param array                 $referenceMapping Mappings of reference for which discriminator data is created.
     * @param ClassMetadata<object> $class            Metadata of reference document class.
     * @phpstan-param FieldMapping $referenceMapping
     *
     * @return array<string, class-string> with next structure [{discriminator field} => {discriminator value}]
     *
     * @throws MappingException When discriminator map is present and reference class in not registered in it.
     */
    private function getDiscriminatorData(array $referenceMapping, ClassMetadata $class): array
    {
        $discriminatorField = null;
        $discriminatorValue = null;
        $discriminatorMap   = null;

        if (isset($referenceMapping['discriminatorField'])) {
            $discriminatorField = $referenceMapping['discriminatorField'];

            if (isset($referenceMapping['discriminatorMap'])) {
                $discriminatorMap = $referenceMapping['discriminatorMap'];
            }
        } else {
            $discriminatorField = $class->discriminatorField;
            $discriminatorValue = $class->discriminatorValue;
            $discriminatorMap   = $class->discriminatorMap;
        }

        if ($discriminatorField === null) {
            return [];
        }

        if ($discriminatorValue === null) {
            if (! empty($discriminatorMap)) {
                $pos = array_search($class->name, $discriminatorMap);

                if ($pos !== false) {
                    $discriminatorValue = $pos;
                }
            } else {
                $discriminatorValue = $class->name;
            }
        }

        if ($discriminatorValue === null) {
            throw MappingException::unlistedClassInDiscriminatorMap($class->name);
        }

        return [$discriminatorField => $discriminatorValue];
    }

    /**
     * Throws an exception if the DocumentManager is closed or currently not active.
     *
     * @throws MongoDBException If the DocumentManager is closed.
     */
    private function errorIfClosed(): void
    {
        if ($this->closed) {
            throw MongoDBException::documentManagerClosed();
        }
    }

    /**
     * Check if the Document manager is open or closed.
     */
    public function isOpen(): bool
    {
        return ! $this->closed;
    }

    /**
     * Gets the filter collection.
     */
    public function getFilterCollection(): FilterCollection
    {
        if ($this->filterCollection === null) {
            $this->filterCollection = new FilterCollection($this);
        }

        return $this->filterCollection;
    }

    /**
     * Gets the class name for an association (embed or reference) with respect
     * to any discriminator value.
     *
     * @internal
     *
     * @param FieldMapping              $mapping
     * @param array<string, mixed>|null $data
     *
     * @return class-string
     */
    public function getClassNameForAssociation(array $mapping, $data): string
    {
        $discriminatorField = $mapping['discriminatorField'] ?? null;

        $discriminatorValue = null;
        if (isset($discriminatorField, $data[$discriminatorField])) {
            $discriminatorValue = $data[$discriminatorField];
        } elseif (isset($mapping['defaultDiscriminatorValue'])) {
            $discriminatorValue = $mapping['defaultDiscriminatorValue'];
        }

        if ($discriminatorValue !== null) {
            return $mapping['discriminatorMap'][$discriminatorValue]
                ?? (string) $discriminatorValue;
        }

        $class = $this->getClassMetadata($mapping['targetDocument']);

        if (isset($class->discriminatorField, $data[$class->discriminatorField])) {
            $discriminatorValue = $data[$class->discriminatorField];
        } elseif ($class->defaultDiscriminatorValue !== null) {
            $discriminatorValue = $class->defaultDiscriminatorValue;
        }

        if ($discriminatorValue !== null) {
            return $class->discriminatorMap[$discriminatorValue] ?? $discriminatorValue;
        }

        return $mapping['targetDocument'];
    }

    private static function getVersion(): string
    {
        if (self::$version === null) {
            try {
                self::$version = PrettyVersions::getVersion('doctrine/mongodb-odm')->getPrettyVersion();
            } catch (Throwable) {
                return 'unknown';
            }
        }

        return self::$version;
    }
}
