<?php

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\Common\Cache\Cache;

class ClassMetadataFactory
{
    private $_dm;
    private $_loadedMetadata;
    private $_driver;
    private $_cacheDriver;

    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
        $this->_driver = $dm->getConfiguration()->getMetadataDriverImpl();
    }

    public function setCacheDriver(Cache $cacheDriver)
    {
        $this->_cacheDriver = $cacheDriver;
    }

    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            
            if ($this->_cacheDriver) {
                if (($cached = $this->_cacheDriver->fetch("$className\$MONGODBODMCLASSMETADATA")) !== false) {
                    $this->_loadedMetadata[$className] = $cached;
                } else {
                    foreach ($this->_loadMetadata($className) as $loadedClassName) {
                        $this->_cacheDriver->save(
                            "$loadedClassName\$MONGODBODMCLASSMETADATA", $this->_loadedMetadata[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->_loadMetadata($className);
            }
        }
        return $this->_loadedMetadata[$className];
    }

    private function _loadMetadata($className)
    {
        $loaded = array();

        $parentClasses = $this->_getParentClasses($className);
        $parentClasses[] = $className;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $visited = array();
        foreach ($parentClasses as $className) {
            if (isset($this->_loadedMetadata[$className])) {
                $parent = $this->_loadedMetadata[$className];
                array_unshift($visited, $className);
                continue;
            }

            $class = $this->_newClassMetadataInstance($className);

            if ($parent) {
                $class->setInheritanceType($parent->inheritanceType);
                $class->setDiscriminatorField($parent->discriminatorField);
                $this->_addInheritedFields($class, $parent);
                $class->setIdentifier($parent->identifier);
                $class->setDiscriminatorMap($parent->discriminatorMap);
            }

            $this->_driver->loadMetadataForClass($className, $class);

            if ($parent && $parent->isInheritanceTypeSingleCollection()) {
                $class->setDB($parent->getDB());
                $class->setCollection($parent->getCollection());
            }

            $class->setParentClasses($visited);

            $this->_loadedMetadata[$className] = $class;

            $parent = $class;

            array_unshift($visited, $className);

            $loaded[] = $className;
        }

        return $loaded;
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     * 
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        return isset($this->_loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     * 
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->_loadedMetadata[$className] = $class;
    }

    protected function _newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    protected function _getParentClasses($name)
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = array();
        foreach (array_reverse(class_parents($name)) as $parentClass) {
            $parentClasses[] = $parentClass;
        }
        return $parentClasses;
    }

    private function _addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited'])) {
                $mapping['inherited'] = $parentClass->name;
            }
            if ( ! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->mapField($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }
}