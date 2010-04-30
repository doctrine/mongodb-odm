<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\Common\Annotations\AnnotationReader;

require __DIR__ . '/DoctrineAnnotations.php';

class AnnotationDriver implements Driver
{
    private $_reader;
    private $_paths = array();

    public function __construct(AnnotationReader $reader, $paths = null)
    {
        $this->_reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
    }

    public function getPaths()
    {
        return $this->_paths;
    }

    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $reflClass = $class->getReflectionClass();

        $classAnnotations = $this->_reader->getClassAnnotations($reflClass);
        
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Driver\Document'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Driver\Document'];
            if ($documentAnnot->db) {
                $class->setDB($documentAnnot->db);
            }
            if ($documentAnnot->collection) {
                $class->setCollection($documentAnnot->collection);
            }
        }
        
        foreach ($reflClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();
            
            if ($fieldAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Driver\Field')) {
                $mapping = array_merge($mapping, (array) $fieldAnnot);
            }
            $class->mapField($mapping);
        }
    }
}