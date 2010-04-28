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

    public function mapField(array $mapping)
    {
        $this->fieldMappings[$mapping['fieldName']] = $mapping;

        $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $reflProp;

        if (isset($mapping['id']) && $mapping['id'] === true) {
            $this->identifier = $mapping['fieldName'];
        }
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

    public function setIdentifierValue($entity, $id)
    {
        $this->reflFields[$this->identifier]->setValue($entity, $id);
    }

    public function getIdentifierValue($entity)
    {
        return (string) $this->reflFields[$this->identifier]->getValue($entity);
    }

    public function getIdentifierObject($entity)
    {
        return new \MongoId($this->getIdentifierValue($entity));
    }

    public function setFieldValue($entity, $field, $value)
    {
        if (!$field) {
            throw new \InvalidArgumentException('test');
        }
        $this->reflFields[$field]->setValue($entity, $value);
    }

    public function getFieldValue($entity, $field)
    {
        return $this->reflFields[$field]->getValue($entity);
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