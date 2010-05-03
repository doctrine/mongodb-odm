<?php

namespace Doctrine\ODM\MongoDB\Mapping;

class ClassMetadata
{
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    const INHERITANCE_TYPE_NONE = 1;

    /**
     * SINGLE_COLLECTION means the class will be persisted according to the rules of
     * <tt>Single Collection Inheritance</tt>.
     */
    const INHERITANCE_TYPE_SINGLE_COLLECTION = 2;

    /**
     * COLLECTION_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Collection Inheritance</tt>.
     */
    const INHERITANCE_TYPE_COLLECTION_PER_CLASS = 3;

    public $name;
    public $namespace;
    public $rootDocumentName;
    public $reflClass;
    public $reflFields = array();
    public $db;
    public $collection;
    public $prototype;
    public $fieldMappings = array();
    public $identifier;
    public $file;
    public $indexes = array();
    public $inheritanceType = self::INHERITANCE_TYPE_NONE;
    public $discriminatorField;
    public $discriminatorValue;
    public $discriminatorMap = array();
    public $parentClasses = array();
    public $subClasses = array();

    public function __construct($name)
    {
        $this->name = $name;
        $this->rootDocumentName = $name;
        $this->reflClass = new \ReflectionClass($name);
        $this->namespace = $this->reflClass->getNamespaceName();

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

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s),
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifier === $fieldName ? true : false;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function setInheritanceType($type)
    {
        $this->inheritanceType = $type;
    }

    public function setDiscriminatorField($discriminatorField)
    {
        if ( ! isset($discriminatorField['name']) && isset($discriminatorField['fieldName'])) {
            $discriminatorField['name'] = $discriminatorField['fieldName'];
        }
        $this->discriminatorField = $discriminatorField;
    }

    public function setDiscriminatorValue($discriminatorValue)
    {
        $this->discriminatorValue = $discriminatorValue;
    }

    public function setDiscriminatorMap(array $map)
    {
        foreach ($map as $value => $className) {
            if (strpos($className, '\\') === false && strlen($this->namespace)) {
                $className = $this->namespace . '\\' . $className;
            }
            $this->discriminatorMap[$value] = $className;
            if ($this->name == $className) {
                $this->discriminatorValue = $value;
            } else {
                if (is_subclass_of($className, $this->name)) {
                    $this->subClasses[] = $className;
                }
            }
        }
    }

    public function setExtends($extends)
    {
        $this->extends = $extends;
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

    /**
     * Gets the ReflectionPropertys of the mapped class.
     *
     * @return array An array of ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
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
        if (isset($this->fieldMappings[$mapping['fieldName']])) {
            $mapping = array_merge($mapping, $this->fieldMappings[$mapping['fieldName']]);
        }

        if (isset($mapping['targetDocument']) && strpos($mapping['targetDocument'], '\\') === false && strlen($this->namespace)) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
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

    public function mapOneReference(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'one';
        $this->mapField($mapping);
    }

    public function mapManyReference(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'many';
        $this->mapField($mapping);
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['reference']);
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['reference']) &&
                $this->fieldMappings[$fieldName]['type'] === 'one';
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is collection-valued, FALSE otherwise.
     */
    public function isCollectionValuedReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['reference']) &&
                $this->fieldMappings[$fieldName]['type'] === 'many';
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
            throw new \InvalidArgumentException($field.' test');
        }
        if ($field == 1) {
            throw new \InvalidArgumentException(';fucl');
        }
        $this->reflFields[$field]->setValue($document, $value);
    }

    public function getFieldValue($document, $field)
    {
        if ( ! isset($this->reflFields[$field])) {
            throw new \Exception('test');
        }
        return $this->reflFields[$field]->getValue($document);
    }

    public function isInheritanceTypeNone()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_NONE;
    }

    public function isInheritanceTypeSingleCollection()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_SINGLE_COLLECTION;
    }

    public function isInheritanceTypeCollectionPerClass()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
    }

    public function setParentClasses(array $classNames)
    {
        $this->parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->rootDocumentName = array_pop($classNames);
        }
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