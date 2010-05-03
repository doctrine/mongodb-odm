<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Proxy\ProxyFactory,
    Doctrine\Common\Collections\ArrayCollection;

class DocumentManager
{
    private $_mongo;
    private $_config;
    private $_metadataFactory;
    private $_unitOfWork;
    private $_hydrator;
    private $_documentDBs = array();
    private $_documentCollections = array();

    protected function __construct(Mongo $mongo, Configuration $config = null)
    {
        $this->_mongo = $mongo;
        $this->_config = $config ? $config : new Configuration();
        $this->_hydrator = new Hydrator($this);
        $this->_metadataFactory = new ClassMetadataFactory($this);
        if ($cacheDriver = $this->_config->getMetadataCacheImpl()) {
            $this->_metadataFactory->setCacheDriver($cacheDriver);
        }
        $this->_unitOfWork = new UnitOfWork($this);
        $this->_proxyFactory = new ProxyFactory($this,
                $config->getProxyDir(),
                $config->getProxyNamespace(),
                $config->getAutoGenerateProxyClasses());
    }

    public static function create(Mongo $mongo, Configuration $config = null)
    {
        return new self($mongo, $config);
    }

    public function getConfiguration()
    {
        return $this->_config;
    }

    public function getMongo()
    {
        return $this->_mongo;
    }

    public function getMetadataFactory()
    {
        return $this->_metadataFactory;
    }

    public function getUnitOfWork()
    {
        return $this->_unitOfWork;
    }

    public function getHydrator()
    {
        return $this->_hydrator;
    }
 
    public function getClassMetadata($className)
    {
        return $this->_metadataFactory->getMetadataFor($className);
    }

    public function getDocumentDB($className)
    {
        $db = $this->_metadataFactory->getMetadataFor($className)->getDB();
        if ($db && ! isset($this->_documentDBs[$db])) {
            $database = $this->_mongo->selectDB($db);
            $this->_documentDBs[$db] = new MongoDB($database);
        }
        if ( ! isset($this->_documentDBs[$db])) {
            throw MongoDBException::documentNotMappedToDB($className);
        }
        return $this->_documentDBs[$db];
    }

    public function getDocumentCollection($className)
    {
        $metadata = $this->_metadataFactory->getMetadataFor($className);
        $collection = $metadata->getCollection();
        $db = $metadata->getDB();
        $key = $db . '.' . $collection;
        if ($collection && ! isset($this->_documentCollections[$key])) {
            if ($metadata->isFile()) {
                $collection = $this->_mongo->selectDB($metadata->getDB())->getGridFS($collection);
            } else {
                $collection = $this->_mongo->selectDB($metadata->getDB())->selectCollection($collection);
            }
            $mongoCollection = new MongoCollection($collection, $metadata);
            $this->_documentCollections[$key] = $mongoCollection;
        }
        if ( ! isset($this->_documentCollections[$key])) {
            throw MongoDBException::documentNotMappedToCollection($className);
        }
        return $this->_documentCollections[$key];
    }

    public function createQuery($className = null)
    {
        return new Query($this, $className);
    }

    public function persist($document)
    {
        $this->_unitOfWork->persist($document);
    }

    public function remove($document)
    {
        $this->_unitOfWork->remove($document);
    }

    public function detach($document)
    {
        $this->_unitOfWork->detach($document);
    }

    public function refresh($document)
    {
        $this->_unitOfWork->refresh($document);
    }

    public function loadByID($documentName, $id)
    {
        $collection = $this->getDocumentCollection($documentName);
        $result = $collection->findOne(array('_id' => new \MongoId($id)));
        return $this->load($documentName, $id, $result);
    }

    public function load($documentName, $id, $data)
    {
        if ($data !== null) {
            $hints = array(Query::HINT_REFRESH => Query::HINT_REFRESH);
            return $this->_unitOfWork->getOrCreateDocument($documentName, $data, $hints);
        }
        return false;
    }

    public function flush()
    {
        $this->_unitOfWork->commit();
    }

    public function ensureDocumentIndexes($class)
    {
        if ($indexes = $class->getIndexes()) {
            $collection = $this->getDocumentCollection($class->name);
            foreach ($indexes as $index) {
                $collection->ensureIndex($index['keys'], $index['options']);
            }
        }
    }

    public function deleteDocumentIndexes($documentName)
    {
        return $this->getDocumentCollection($documentName)->deleteIndexes();
    }

    public function mapReduce($documentName, $map, $reduce, array $query = array(), array $options = array())
    {
        $class = $this->getClassMetadata($documentName);
        $db = $this->getDocumentDB($documentName);
        if (is_string($map)) {
            $map = new \MongoCode($map);
        }
        if (is_string($reduce)) {
            $reduce = new \MongoCode($reduce);
        }
        $command = array(
            'mapreduce' => $class->getCollection(),
            'map' => $map,
            'reduce' => $reduce,
            'query' => $query
        );
        $command = array_merge($command, $options);
        $result = $db->command($command);
        return $db->selectCollection($result['result'])->find();
    }

    public function findByID($documentName, $id)
    {
        $collection = $this->getDocumentCollection($documentName);
        $result = $collection->findOne(array('_id' => new \MongoId($id)));
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateDocument($documentName, $result);
        }
        return null;
    }

    public function find($documentName, array $query = array(), array $select = array())
    {
        $metadata = $this->getClassMetadata($documentName);
        $collection = $this->getDocumentCollection($documentName);
        $cursor = $collection->find($query, $select);
        return new MongoCursor($this, $this->_hydrator, $metadata, $cursor);
    }

    /**
     * Gets a reference to the document identified by the given type and identifier
     * without actually loading it.
     *
     * If partial objects are allowed, this method will return a partial object that only
     * has its identifier populated. Otherwise a proxy is returned that automatically
     * loads itself on first access.
     *
     * @return object The document reference.
     */
    public function getReference($documentName, $identifier)
    {
        $class = $this->_metadataFactory->getMetadataFor($documentName);

        // Check identity map first, if its already in there just return it.
        if ($document = $this->_unitOfWork->tryGetById($identifier, $class->rootDocumentName)) {
            return $document;
        }
        $document = $this->_proxyFactory->getProxy($class->name, $identifier);
        $this->_unitOfWork->registerManaged($document, $identifier, array());

        return $document;
    }

    public function findOne($documentName, array $query = array(), array $select = array())
    {
        $metadata = $this->getClassMetadata($documentName);
        $collection = $this->getDocumentCollection($documentName);
        $result = $collection->findOne($query, $select);
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateDocument($documentName, $result);
        }
        return null;
    }

    public function clear()
    {
        $this->_unitOfWork->clear();
    }
}