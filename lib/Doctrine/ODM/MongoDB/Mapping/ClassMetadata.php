<?php

namespace Doctrine\ODM\MongoDB\Mapping;

class ClassMetadata
{
    public $name;
    public $reflClass;
    public $reflFields;
    public $db;
    public $collection;
    public $prototype;
    public $fieldMappings;
    public $identifier;
    public $file;
    public $indexes = array();

    public function __construct($name)
    {
        $this->name = $name;
        $this->reflClass = new \ReflectionClass($name);
        $properties = $this->reflClass->getProperties();
        foreach ($properties as $property) {
            $fieldName = $property->getName();
            $mapping = array('fieldName' => $fieldName);
            if ($fieldName === 'id') {
                $mapping['id'] = true;
            }
            $this->mapField($mapping);
        }
        $e = explode('\\', $name);
        if (count($e) > 1) {
            $e = array_map(function($value) {
                return strtolower($value);
            }, $e);

            $collection = array_pop($e);
            $database = implode('_', $e);
        } else {
            $database = 'doctrine';
            $collection = strtolower($name);
        }

        $this->setDB($database);
        $this->setCollection($collection);
    }

    public function addIndex($keys, $options)
    {
        $this->indexes[] = array(
            'keys' => array_map(function($value) {
                return strtolower($value) == 'asc' ? 1 : -1;
            }, $keys),
            'options' => $options
        );
    }

    public function getIndexes()
    {
        return $this->indexes;
    }

    public function getReflectionClass()
    {
        return $this->reflClass;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDB()
    {
        return $this->db;
    }

    public function setDB($db)
    {
        $this->db = $db;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function isMappedToCollection()
    {
        return $this->collection ? true : false;
    }

    public function isFile()
    {
        return $this->file ? true :false;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function mapField(array $mapping)
    {
        if ( ! isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }
        $this->fieldMappings[$mapping['fieldName']] = $mapping;

        $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $reflProp;

        if (isset($mapping['file']) && $mapping['file'] === true) {
            $this->file = $mapping['fieldName'];
        }
        if (isset($mapping['id']) && $mapping['id'] === true) {
            $this->identifier = $mapping['fieldName'];
        }
    }

    public function mapFile(array $mapping)
    {
        $mapping['file'] = true;
        $this->mapField($mapping);
    }

    public function mapManyEmbedded(array $mapping)
    {
        $mapping['embedded'] = true;
        $mapping['type'] = 'many';
        $this->mapField($mapping);
    }

    public function mapOneEmbedded(array $mapping)
    {
        $mapping['embedded'] = true;
        $mapping['type'] = 'one';
        $this->mapField($mapping);
    }

    public function mapOneAssociation(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'one';
        $this->mapField($mapping);
    }

    public function mapManyAssociation(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'many';
        $this->mapField($mapping);
    }

    public function setIdentifierValue($document, $id)
    {
        $this->reflFields[$this->identifier]->setValue($document, $id);
    }

    public function getIdentifierValue($document)
    {
        return (string) $this->reflFields[$this->identifier]->getValue($document);
    }

    public function getIdentifierObject($document)
    {
        if ($id = $this->getIdentifierValue($document)) {
            return new \MongoId($id);
        }
    }

    public function setFieldValue($document, $field, $value)
    {
        if (!$field) {
            throw new \InvalidArgumentException('test');
        }
        $this->reflFields[$field]->setValue($document, $value);
    }

    public function getFieldValue($document, $field)
    {
        return $this->reflFields[$field]->getValue($document);
    }

    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }
        return clone $this->prototype;
    }

    public function __sleep()
    {
        $serialized = array(
            'name',
            'db',
            'collection',
            'fieldMappings',
            'fieldMappings',
            'identifier'
        );

        return $serialized;
    }

    public function __wakeup()
    {
        $this->reflClass = new \ReflectionClass($this->name);

        foreach ($this->fieldMappings as $field => $mapping) {
            $reflField = $this->reflClass->getProperty($field);
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
    }
}