<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Doctrine\MongoDB\Connection,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Proxy\ProxyFactory,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\Hydrator\HydratorFactory,
    Doctrine\Common\Persistence\ObjectManager;

/**
 * The DocumentManager class is the central access point for managing the
 * persistence of documents.
 *
 *     <?php
 *
 *     $config = new Configuration();
 *     $dm = DocumentManager::create(new Connection(), $config);
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class DocumentManager implements ObjectManager
{
    /**
     * The Doctrine MongoDB connection instance.
     *
     * @var \Doctrine\MongoDB\Connection
     */
    private $connection;

    /**
     * The used Configuration.
     *
     * @var \Doctrine\ODM\MongoDB\Configuration
     */
    private $config;

    /**
     * The metadata factory, used to retrieve the ODM metadata of document classes.
     *
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * The DocumentRepository instances.
     *
     * @var array
     */
    private $repositories = array();

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * The event manager that is the central point of the event system.
     *
     * @var Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * The Hydrator factory instance.
     *
     * @var HydratorFactory
     */
    private $hydratorFactory;

    /**
     * SchemaManager instance
     *
     * @var Doctrine\ODM\MongoDB\SchemaManager
     */
    private $schemaManager;

    /**
     * Array of cached document database instances that are lazily loaded.
     *
     * @var array
     */
    private $documentDatabases = array();

    /**
     * Array of cached document collection instances that are lazily loaded.
     *
     * @var array
     */
    private $documentCollections = array();

    /**
     * Whether the DocumentManager is closed or not.
     *
     * @var bool
     */
    private $closed = false;

    /**
     * Mongo command character
     *
     * @var string
     */
    private $cmd;

    /**
     * Creates a new Document that operates on the given Mongo connection
     * and uses the given Configuration.
     *
     * @param \Doctrine\MongoDB\Connection|null $conn
     * @param Configuration|null $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     */
    protected function __construct(Connection $conn = null, Configuration $config = null, EventManager $eventManager = null)
    {
        $this->config = $config ?: new Configuration();
        $this->eventManager = $eventManager ?: new EventManager();
        $this->cmd = $this->config->getMongoCmd();
        $this->connection = $conn ?: new Connection(null, array(), $this->config, $this->eventManager);

        $metadataFactoryClassName = $this->config->getClassMetadataFactoryName();
        $this->metadataFactory = new $metadataFactoryClassName();
        $this->metadataFactory->setDocumentManager($this);
        $this->metadataFactory->setConfiguration($this->config);
        if ($cacheDriver = $this->config->getMetadataCacheImpl()) {
            $this->metadataFactory->setCacheDriver($cacheDriver);
        }

        $hydratorDir = $this->config->getHydratorDir();
        $hydratorNs = $this->config->getHydratorNamespace();
        $this->hydratorFactory = new HydratorFactory(
          $this,
          $this->eventManager,
          $hydratorDir,
          $hydratorNs,
          $this->config->getAutoGenerateHydratorClasses(),
          $this->config->getMongoCmd()
        );

        $this->unitOfWork = new UnitOfWork($this, $this->eventManager, $this->hydratorFactory, $this->cmd);
        $this->hydratorFactory->setUnitOfWork($this->unitOfWork);
        $this->schemaManager = new SchemaManager($this, $this->metadataFactory);
        $this->proxyFactory = new ProxyFactory($this,
                $this->config->getProxyDir(),
                $this->config->getProxyNamespace(),
                $this->config->getAutoGenerateProxyClasses()
        );
    }

    /**
     * Gets the proxy factory used by the DocumentManager to create document proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * Creates a new Document that operates on the given Mongo connection
     * and uses the given Configuration.
     *
     * @static
     * @param \Doctrine\MongoDB\Connection|null $conn
     * @param Configuration|null $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     * @return DocumentManager
     */
    public static function create(Connection $conn = null, Configuration $config = null, EventManager $eventManager = null)
    {
        return new DocumentManager($conn, $config, $eventManager);
    }

    /**
     * Gets the EventManager used by the DocumentManager.
     *
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Gets the PHP Mongo instance that this DocumentManager wraps.
     * 
     * @return \Doctrine\MongoDB\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
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
     * Gets the UnitOfWork used by the DocumentManager to coordinate operations.
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * Gets the Hydrator factory used by the DocumentManager to generate and get hydrators
     * for each type of document.
     *
     * @return \Doctrine\ODM\MongoDB\Hydrator\HydratorInterface
     */
    public function getHydratorFactory()
    {
        return $this->hydratorFactory;
    }

    /**
     * Retuns SchemaManager, used to create/drop indexes/collections/databases
     *
     * @return \Doctrine\ODM\MongoDB\SchemaManager
     */
    public function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * Returns the metadata for a class.
     *
     * @param string $className The class name.
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     * @internal Performance-sensitive method.
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * Returns the MongoDB instance for a class.
     *
     * @param string $className The class name.
     * @return \Doctrine\MongoDB\Database
     */
    public function getDocumentDatabase($className)
    {
        if (isset($this->documentDatabases[$className])) {
            return $this->documentDatabases[$className];
        }
        $metadata = $this->metadataFactory->getMetadataFor($className);
        $db = $metadata->getDatabase();
        $db = $db ? $db : $this->config->getDefaultDB();
        $db = $db ? $db : 'doctrine';
        $this->documentDatabases[$className] = $this->connection->selectDatabase($db);
        return $this->documentDatabases[$className];
    }

    /**
     * Gets the array of instantiated document database instances.
     *
     * @return array
     */
    public function getDocumentDatabases()
    {
        return $this->documentDatabases;
    }

    /**
     * Returns the MongoCollection instance for a class.
     *
     * @param string $className The class name.
     * @return \Doctrine\MongoDB\Collection
     */
    public function getDocumentCollection($className)
    {
        $metadata = $this->metadataFactory->getMetadataFor($className);
        $collection = $metadata->getCollection();

        if ( ! $collection) {
            throw MongoDBException::documentNotMappedToCollection($className);
        }

        $db = $this->getDocumentDatabase($className);
        if ( ! isset($this->documentCollections[$className])) {
            if ($metadata->isFile()) {
                $this->documentCollections[$className] = $db->getGridFS($collection);
            } else {
                $this->documentCollections[$className] = $db->selectCollection($collection);
            }
        }
        return $this->documentCollections[$className];
    }

    /**
     * Gets the array of instantiated document collection instances.
     *
     * @return array
     */
    public function getDocumentCollections()
    {
        return $this->documentCollections;
    }

    /**
     * Create a new Query instance for a class.
     *
     * @param string $documentName The document class name.
     * @return Query\Builder
     */
    public function createQueryBuilder($documentName = null)
    {
        return new Query\Builder($this, $this->cmd, $documentName);
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
     * @param object $document The instance to make managed and persistent.
     */
    public function persist($document)
    {
        if ( ! is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->errorIfClosed();
        $this->unitOfWork->persist($document);
    }

    /**
     * Removes a document instance.
     *
     * A removed document will be removed from the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $document The document instance to remove.
     */
    public function remove($document)
    {
        if ( ! is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->errorIfClosed();
        $this->unitOfWork->remove($document);
    }

    /**
     * Refreshes the persistent state of a document from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $document The document to refresh.
     */
    public function refresh($document)
    {
        if ( ! is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->errorIfClosed();
        $this->unitOfWork->refresh($document);
    }

    /**
     * Detaches a document from the DocumentManager, causing a managed document to
     * become detached.  Unflushed changes made to the document if any
     * (including removal of the document), will not be synchronized to the database.
     * Documents which previously referenced the detached document will continue to
     * reference it.
     *
     * @param object $document The document to detach.
     */
    public function detach($document)
    {
        if ( ! is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->unitOfWork->detach($document);
    }

    /**
     * Merges the state of a detached document into the persistence context
     * of this DocumentManager and returns the managed copy of the document.
     * The document passed to merge will not become associated/managed with this DocumentManager.
     *
     * @param object $document The detached document to merge into the persistence context.
     * @return object The managed copy of the document.
     */
    public function merge($document)
    {
        if ( ! is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->errorIfClosed();
        return $this->unitOfWork->merge($document);
    }

    /**
     * Acquire a lock on the given document.
     *
     * @param object $document
     * @param int $lockMode
     * @param int $lockVersion
     * @throws LockException
     * @throws LockException
     */
    public function lock($document, $lockMode, $lockVersion = null)
    {
        $this->unitOfWork->lock($document, $lockMode, $lockVersion);
    }

    /**
     * Releases a lock on the given document.
     *
     * @param object $document
     */
    public function unlock($document)
    {
        $this->unitOfWork->unlock($document);
    }

    /**
     * Gets the repository for a document class.
     *
     * @param string $documentName  The name of the Document.
     * @return DocumentRepository  The repository.
     */
    public function getRepository($documentName)
    {
        if (isset($this->repositories[$documentName])) {
            return $this->repositories[$documentName];
        }

        $metadata = $this->getClassMetadata($documentName);
        $customRepositoryClassName = $metadata->customRepositoryClassName;

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $this->unitOfWork, $metadata);
        } else {
            $repository = new DocumentRepository($this, $this->unitOfWork, $metadata);
        }

        $this->repositories[$documentName] = $repository;

        return $repository;
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * @param array $options Array of options to be used with batchInsert(), update() and remove()
     */
    public function flush(array $options = array())
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
     * @param string $documentName
     * @param string|object $identifier
     * @return mixed|object The document reference.
     */
    public function getReference($documentName, $identifier)
    {
        $class = $this->metadataFactory->getMetadataFor($documentName);

        // Check identity map first, if its already in there just return it.
        if ($document = $this->unitOfWork->tryGetById($identifier, $class->rootDocumentName)) {
            return $document;
        }
        $document = $this->proxyFactory->getProxy($class->name, $identifier);
        $this->unitOfWork->registerManaged($document, $identifier, array());

        return $document;
    }

    /**
     * Gets a partial reference to the document identified by the given type and identifier
     * without actually loading it, if the document is not yet loaded.
     *
     * The returned reference may be a partial object if the document is not yet loaded/managed.
     * If it is a partial object it will not initialize the rest of the document state on access.
     * Thus you can only ever safely access the identifier of an document obtained through
     * this method.
     *
     * The use-cases for partial references involve maintaining bidirectional associations
     * without loading one side of the association or to update an document without loading it.
     * Note, however, that in the latter case the original (persistent) document data will
     * never be visible to the application (especially not event listeners) as it will
     * never be loaded in the first place.
     *
     * @param string $documentName The name of the document type.
     * @param mixed $identifier The document identifier.
     * @return object The (partial) document reference.
     */
    public function getPartialReference($documentName, $identifier)
    {
        $class = $this->metadataFactory->getMetadataFor($documentName);

        // Check identity map first, if its already in there just return it.
        if ($document = $this->unitOfWork->tryGetById($identifier, $class->rootDocumentName)) {
            return $document;
        }
        $document = $class->newInstance();
        $class->setIdentifierValue($document, $identifier);
        $this->unitOfWork->registerManaged($document, $identifier, array());

        return $document;
    }

    /**
     * Finds a Document by its identifier.
     *
     * This is just a convenient shortcut for getRepository($documentName)->find($id).
     *
     * @param string $documentName
     * @param mixed $identifier
     * @param int $lockMode
     * @param int $lockVersion
     * @return object $document
     */
    public function find($documentName, $identifier, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository($documentName)->find($identifier, $lockMode, $lockVersion);
    }

    /**
     * Clears the DocumentManager. All entities that are currently managed
     * by this DocumentManager become detached.
     *
     * @param string $documentName
     */
    public function clear($documentName = null)
    {
        if ($documentName === null) {
            $this->unitOfWork->clear();
        } else {
            //TODO
            throw new MongoDBException("DocumentManager#clear(\$documentName) not yet implemented.");
        }
    }

    /**
     * Closes the DocumentManager. All documents that are currently managed
     * by this DocumentManager become detached. The DocumentManager may no longer
     * be used after it is closed.
     */
    public function close()
    {
        $this->clear();
        $this->closed = true;
    }

    /**
     * Determines whether a document instance is managed in this DocumentManager.
     *
     * @param object $document
     * @return boolean TRUE if this DocumentManager currently manages the given document, FALSE otherwise.
     */
    public function contains($document)
    {
        return $this->unitOfWork->isScheduledForInsert($document) ||
               $this->unitOfWork->isInIdentityMap($document) &&
               ! $this->unitOfWork->isScheduledForDelete($document);
    }

    /**
     * Gets the Configuration used by the DocumentManager.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    public function getClassNameFromDiscriminatorValue(array $mapping, $value)
    {
        $discriminatorField = isset($mapping['discriminatorField']) ? $mapping['discriminatorField'] : '_doctrine_class_name';
        if (is_array($value) && isset($value[$discriminatorField])) {
            $discriminatorValue = $value[$discriminatorField];
            return isset($mapping['discriminatorMap'][$discriminatorValue]) ? $mapping['discriminatorMap'][$discriminatorValue] : $discriminatorValue;
        } else {
            $class = $this->getClassMetadata($mapping['targetDocument']);

            // @TODO figure out how to remove this
            if ($class->discriminatorField) {
                if (isset($value[$class->discriminatorField['name']])) {
                    return $class->discriminatorMap[$value[$class->discriminatorField['name']]];
                }
            }
        }
        return $mapping['targetDocument'];
    }

    /**
     * Returns a DBRef array for the supplied document.
     *
     * @param mixed $document A document object
     * @param array $referenceMapping Mapping for the field the references the document
     *
     * @return array A DBRef array
     */
    public function createDBRef($document, array $referenceMapping = null)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Cannot create a DBRef, the document is not an object');
        }
        $className = get_class($document);
        $class = $this->getClassMetadata($className);
        $id = $this->unitOfWork->getDocumentIdentifier($document);

        if (isset($referenceMapping['simple']) && $referenceMapping['simple']) {
            return $class->getDatabaseIdentifierValue($id);
        }

        $dbRef = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id'  => $class->getDatabaseIdentifierValue($id),
            $this->cmd . 'db'  => $this->getDocumentDatabase($className)->getName()
        );

        if ($class->discriminatorField) {
            $dbRef[$class->discriminatorField['name']] = $class->discriminatorValue;
        }

        // add a discriminator value if the referenced document is not mapped explicitely to a targetDocument
        if ($referenceMapping && ! isset($referenceMapping['targetDocument'])) {
            $discriminatorField = isset($referenceMapping['discriminatorField']) ? $referenceMapping['discriminatorField'] : '_doctrine_class_name';
            $discriminatorValue = isset($referenceMapping['discriminatorMap']) ? array_search($class->getName(), $referenceMapping['discriminatorMap']) : $class->getName();
            $dbRef[$discriminatorField] = $discriminatorValue;
        }

        return $dbRef;
    }

    /**
     * Throws an exception if the DocumentManager is closed or currently not active.
     *
     * @throws MongoDBException If the DocumentManager is closed.
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw MongoDBException::documentManagerClosed();
        }
    }
}
