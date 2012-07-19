<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    /**
     * @group DDC-268
     */
    public function testLoadMetadataForNonDocumentThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotationDriver = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);

        $this->setExpectedException('Doctrine\ODM\MongoDB\Mapping\MappingException');
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    /**
     * @group DDC-268
     */
    public function testColumnWithMissingTypeDefaultsToString()
    {
        $cm = new ClassMetadata('Doctrine\ODM\MongoDB\Tests\Mapping\ColumnWithoutType');
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotationDriver = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);

        $annotationDriver->loadMetadataForClass('Doctrine\ODM\MongoDB\Tests\Mapping\InvalidColumn', $cm);
        $this->assertEquals('id', $cm->fieldMappings['id']['type']);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesIsIdempotent()
    {
        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $original = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $afterTestReset = $annotationDriver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesIsIdempotentEvenWithDifferentDriverInstances()
    {
        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $original = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $afterTestReset = $annotationDriver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $rightClassName = 'Documents\CmsUser';
        $this->_ensureIsLoaded($rightClassName);

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    /**
     * @group DDC-318
     */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = __NAMESPACE__.'\ColumnWithoutType';
        $this->_ensureIsLoaded($extraneousClassName);

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    protected function _loadDriverForCMSDocuments()
    {
        $annotationDriver = $this->_loadDriver();
        $annotationDriver->addPaths(array(__DIR__ . '/../../../../../Documents'));
        return $annotationDriver;
    }

    protected function _loadDriver()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        return new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);
    }

    protected function _ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }
}

/**
 * @ODM\Document
 */
class ColumnWithoutType
{
    /** @ODM\Id */
    public $id;
}