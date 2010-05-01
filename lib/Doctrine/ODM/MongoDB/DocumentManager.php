<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\PersistentCollection,
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
        $this->_unitOfWork = new UnitOfWork($this);
        $this->_metadataFactory = new ClassMetadataFactory($this);
        if ($cacheDriver = $this->_config->getMetadataCacheImpl()) {
            $this->_metadataFactory->setCacheDriver($cacheDriver);
        }
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
        if ( ! isset($this->_documentDBs[$className])) {
            if ($db = $this->_metadataFactory->getMetadataFor($className)->getDB()) {
                $this->_documentDBs[$className] = $this->_mongo->selectDB($db);
            }
        }
        if ( ! isset($this->_documentDBs[$className])) {
            throw MongoDBException::documentNotMappedToDB($className);
        }
        return $this->_documentDBs[$className];
    }

    public function getDocumentCollection($className)
    {
        if ( ! isset($this->_documentCollections[$className])) {
            $metadata = $this->_metadataFactory->getMetadataFor($className);
            if ($collection = $metadata->getCollection()) {
                $this->_documentCollections[$className] = $this->_mongo->selectDB($metadata->getDB())->selectCollection($collection);
            }
        }
        if ( ! isset($this->_documentCollections[$className])) {
            throw MongoDBException::documentNotMappedToCollection($className);
        }
        return $this->_documentCollections[$className];
    }

    public function loadDocumentAssociation($document, $name)
    {
        $className = get_class($document);
        $class = $this->getClassMetadata($className);
        $mapping = $class->fieldMappings[$name];
        $targetClass = $this->getClassMetadata($mapping['targetDocument']);

        if ($mapping['type'] === 'one') {
            $reference = $class->getFieldValue($document, $name);
            if ($reference && ! is_object($reference)) {
                $reference = $this->getDocumentCollection($mapping['targetDocument'])->getDBRef($reference);
                $reference = $this->_unitOfWork->getOrCreateDocument($mapping['targetDocument'], (array) $reference);
                $class->setFieldValue($document, $name, $reference);
            }
        } else {
            $referenceArray = $class->getFieldValue($document, $name);
            $collection = new PersistentCollection($this, $targetClass, new ArrayCollection());
            foreach ($referenceArray as $key => $reference) {
                if ($reference && ! is_object($reference)) {
                    $reference = $this->getDocumentCollection($mapping['targetDocument'])->getDBRef($reference);
                    $reference = $this->_unitOfWork->getOrCreateDocument($mapping['targetDocument'], (array) $reference);
                }
                $collection->add($reference);
            }
            $class->setFieldValue($document, $name, $collection);
        }
    }

    public function loadDocumentAssociations($document)
    {
        $className = get_class($document);
        $class = $this->getClassMetadata($className);
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['reference'])) {
                $this->loadDocumentAssociation($document, $mapping['fieldName']);
            }
        }
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
        $metadata = $this->getClassMetadata($documentName);
        $collection = $this->getDocumentCollection($documentName);
        $result = $collection->findOne(array('_id' => new \MongoId($id)));
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateDocument($documentName, (array) $result);
        } else {
            return null;
        }
    }

    public function find($documentName, array $query = array(), array $select = array())
    {
        $metadata = $this->getClassMetadata($documentName);
        $query = $this->_prepareFieldNames($metadata, $query);
        $select = $this->_prepareFieldNames($metadata, $select);
        $collection = $this->getDocumentCollection($documentName);
        $cursor = $collection->find($query, $select);
        return new CursorProxy($this, $this->_hydrator, $metadata, $cursor);
    }

    public function findOne($documentName, array $query = array(), array $select = array())
    {
        $metadata = $this->getClassMetadata($documentName);
        $query = $this->_prepareFieldNames($metadata, $query);
        $select = $this->_prepareFieldNames($metadata, $select);
        $collection = $this->getDocumentCollection($documentName);
        $result = $collection->findOne($query, $select);
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateDocument($documentName, (array) $result);
        } else {
            return null;
        }
    }

    public function clear()
    {
        $this->_unitOfWork->clear();
    }

    private function _prepareFieldNames(ClassMetadata $class, $query)
    {
        $prepared = array();
        foreach ($query as $key => $value) {
            if (isset($class->fieldMappings[$key])) {
                $name = $class->fieldMappings[$key]['name'];
                $prepared[$name] = $value;
            } else {
                $prepared[$key] = $value;
            }
        }
        return $prepared;
    }
}