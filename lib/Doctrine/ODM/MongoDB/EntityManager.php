<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mongo;

class EntityManager
{
    private $_mongo;
    private $_config;
    private $_metadataFactory;
    private $_unitOfWork;
    private $_hydrator;
    private $_entityDBs = array();
    private $_entityCollections = array();

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

    public function getEntityDB($className)
    {
        if ( ! isset($this->_entityDBs[$className])) {
            if ($db = $this->getClassMetadata($className)->getDB()) {
                $this->_entityDBs[$className] = $this->_mongo->selectDB($db);
            }
        }
        if ( ! isset($this->_entityDBs[$className])) {
            throw MongoDBException::entityNotMappedToDB($className);
        }
        return $this->_entityDBs[$className];
    }

    public function getEntityCollection($className)
    {
        if ( ! isset($this->_entityCollections[$className])) {
            $metadata = $this->getClassMetadata($className);
            if ($collection = $metadata->getCollection()) {
                $this->_entityCollections[$className] = $this->_mongo->selectDB($metadata->getDB())->selectCollection($collection);
            }
        }
        if ( ! isset($this->_entityCollections[$className])) {
            throw MongoDBException::entityNotMappedToCollection($className);
        }
        return $this->_entityCollections[$className];
    }

    public function loadEntityAssociation($entity, $name)
    {
        $className = get_class($entity);
        $class = $this->getClassMetadata($className);
        $mapping = $class->fieldMappings[$name];
        if ($mapping['type'] === 'one') {
            $reference = $class->getFieldValue($entity, $name);
            if ($reference && ! is_object($reference)) {
                $reference = $this->getEntityCollection($mapping['targetEntity'])->getDBRef($reference);
                $targetClass = $this->getClassMetadata($mapping['targetEntity']);
                $reference = $this->_unitOfWork->getOrCreateEntity($mapping['targetEntity'], (array) $reference);
                $class->setFieldValue($entity, $name, $reference);
            }
        } else {
            $referenceArray = $class->getFieldValue($entity, $name);
            foreach ($referenceArray as $key => $reference) {
                if ($reference && ! is_object($reference)) {
                    $reference = $this->getEntityCollection($mapping['targetEntity'])->getDBRef($reference);
                    $targetClass = $this->getClassMetadata($mapping['targetEntity']);
                    $reference = $this->_unitOfWork->getOrCreateEntity($mapping['targetEntity'], (array) $reference);
                    $referenceArray[$key] = $reference;
                }
            }
            $class->setFieldValue($entity, $name, $referenceArray);
        }
    }

    public function loadEntityAssociations($entity)
    {
        $className = get_class($entity);
        $class = $this->getClassMetadata($className);
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['reference'])) {
                $this->loadEntityAssociation($entity, $mapping['fieldName']);
            }
        }
    }

    public function createQuery($className = null)
    {
        return new Query($this, $className);
    }

    public function save($entity)
    {
        $this->_unitOfWork->save($entity);
    }

    public function persist($entity)
    {
        $this->_unitOfWork->persist($entity);
    }

    public function remove($entity)
    {
        $this->_unitOfWork->remove($entity);
    }

    public function detach($entity)
    {
        $this->_unitOfWork->detach($entity);
    }

    public function refresh($entity)
    {
        $this->_unitOfWork->refresh($entity);
    }

    public function flush()
    {
        $this->_unitOfWork->commit();
    }

    public function mapReduce($entityName, $map, $reduce, array $query = array(), array $options = array())
    {
        $class = $this->getClassMetadata($entityName);
        $db = $this->getEntityDB($entityName);
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

    public function findByID($entityName, $id)
    {
        $metadata = $this->getClassMetadata($entityName);
        $collection = $this->getEntityCollection($entityName);
        $result = $collection->findOne(array('_id' => new \MongoId($id)));
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateEntity($entityName, (array) $result);
        } else {
            return false;
        }
    }

    public function find($entityName, array $query = array(), array $fields = array())
    {
        $metadata = $this->getClassMetadata($entityName);
        $collection = $this->getEntityCollection($entityName);
        $cursor = $collection->find($query, $fields);
        return new CursorProxy($this, $this->_hydrator, $metadata, $cursor);
    }

    public function findOne($entityName, array $query = array(), array $fields = array())
    {
        $metadata = $this->getClassMetadata($entityName);
        $collection = $this->getEntityCollection($entityName);
        $result = $collection->findOne($query, $fields);
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateEntity($entityName);
        } else {
            return false;
        }
    }

    public function clear()
    {
        $this->_unitOfWork->clear();
    }
}