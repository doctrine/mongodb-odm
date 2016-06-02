<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    // @TODO: This can be a generic test for all drivers
    public function testFieldInheritance()
    {
        $super = $this->dm->getClassMetadata(AnnotationDriverTestSuper::class);
        $parent = $this->dm->getClassMetadata(AnnotationDriverTestParent::class);
        $child = $this->dm->getClassMetadata(AnnotationDriverTestChild::class);

        $this->assertFalse($super->hasField('id'), 'MappedSuperclass does not register its own public field');
        $this->assertFalse($super->hasField('protected'), 'MappedSuperclass does not register its own protected field');
        $this->assertTrue($super->hasField('private'), 'MappedSuperclass does register its own private field');
        $this->assertFalse($super->isInheritedField('private'), 'MappedSuperclass does not inherit its own field');
        $this->assertFalse($super->hasField('foo'), 'MappedSuperclass does not have field declared in child Document');
        $this->assertFalse($super->hasField('bar'), 'MappedSuperclass does not have field declared in grandchild Document');

        $this->assertArrayNotHasKey('declared', $super->fieldMappings['private'], 'MappedSuperclass does not track "declared" for non-inherited field');

        $this->assertTrue($parent->hasField('id'), 'Document does have public field from MappedSuperclass parent');
        $this->assertFalse($parent->isInheritedField('id'), 'Document does not inherit public field from MappedSuperclass parent');
        $this->assertTrue($parent->hasField('protected'), 'Document does have protected field from MappedSuperclass parent');
        $this->assertFalse($parent->isInheritedField('protected'), 'Document does not inherit protected field from MappedSuperclass parent');
        $this->assertTrue($parent->hasField('private'), 'Document does have private field from MappedSuperclass parent');
        /* MappedSuperclass fields are never considered "inherited", but the
         * field is still considered "declared" in the MappedSuperclass, since
         * we need its ReflectionProperty to access it. This is a bit weird.
         */
        $this->assertFalse($parent->isInheritedField('private'), 'Document does not inherit private field from MappedSuperclass parent');
        $this->assertTrue($parent->hasField('foo'), 'Document does register its own public field');
        $this->assertFalse($parent->isInheritedField('foo'), 'Document does not inherit its own field');
        $this->assertFalse($parent->hasField('bar'), 'Document does not have field declared in child Document');

        $this->assertArrayNotHasKey('declared', $parent->fieldMappings['id'], 'Document does not track "declared" for non-inherited public field from MappedSuperclass parent');
        $this->assertArrayNotHasKey('declared', $parent->fieldMappings['protected'], 'Document does not track "declared" for non-inherited protected field from MappedSuperclass parent');
        $this->assertEquals(AnnotationDriverTestSuper::class, $parent->fieldMappings['private']['declared'], 'Non-inherited private field from MappedSuperclass parent is declared in MappedSuperclass parent');
        $this->assertArrayNotHasKey('declared', $parent->fieldMappings['foo'], 'Document does not track "declared" for its own public field');

        $this->assertTrue($child->hasField('id'), 'Document does have public field from MappedSuperclass grandparent');
        $this->assertTrue($child->isInheritedField('id'), 'Document does inherit public field from MappedSuperclass grandparent');
        $this->assertTrue($child->hasField('protected'), 'Document does have protected field from MappedSuperclass grandparent');
        $this->assertTrue($child->isInheritedField('protected'), 'Document does inherit protected field from MappedSuperclass grandparent');
        $this->assertTrue($child->hasField('private'), 'Document does have private field from MappedSuperclass grandparent');
        $this->assertTrue($child->isInheritedField('private'), 'Document does inherit private field from MappedSuperclass grandparent');
        $this->assertTrue($child->hasField('foo'), 'Document does have public field from Document parent');
        $this->assertTrue($child->isInheritedField('foo'), 'Document field declared in Document parent is inherited');
        $this->assertTrue($child->hasField('bar'), 'Document does register its own public field');
        $this->assertFalse($child->isInheritedField('bar'), 'Document does not inherit its own field');

        $this->assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['id']['declared'], 'Inherited public field from MappedSuperclass grandparent is declared in Document parent');
        $this->assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['protected']['declared'], 'Inherited protected field from MappedSuperclass grandparent is declared in Document parent');
        $this->assertEquals(AnnotationDriverTestSuper::class, $child->fieldMappings['private']['declared'], 'Inherited private field from MappedSuperclass grandparent is declared in MappedSuperclass grandparent');
        $this->assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['foo']['declared'], 'Inherited public field from Document parent is declared in Document parent');
    }

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

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    /**
     * @group DDC-318
     */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = ColumnWithoutType::class;

        $annotationDriver = $this->_loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Embedded document can't have shard key
     */
    public function testEmbeddedClassCantHaveShardKey()
    {
        $this->dm->getClassMetadata(AnnotationDriverEmbeddedWithShardKey::class);
    }

    public function testDocumentAnnotationCanSpecifyWriteConcern()
    {
        $cm = $this->dm->getClassMetadata(AnnotationDriverTestWriteConcernMajority::class);
        $this->assertEquals('majority', $cm->writeConcern);

        $cm = $this->dm->getClassMetadata(AnnotationDriverTestWriteConcernUnacknowledged::class);
        $this->assertSame(0, $cm->writeConcern);

        $cm = $this->dm->getClassMetadata(ColumnWithoutType::class);
        $this->assertNull($cm->writeConcern);
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
}

/** @ODM\Document */
class ColumnWithoutType
{
     /** @ODM\Id */
     public $id;
}

/** @ODM\MappedSuperclass */
class AnnotationDriverTestSuper
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    protected $protected;

    /** @ODM\Field(type="string") */
    private $private;
}

/** @ODM\Document */
class AnnotationDriverTestParent extends AnnotationDriverTestSuper
{
    /** @ODM\Field(type="string") */
    public $foo;
}

/** @ODM\Document */
class AnnotationDriverTestChild extends AnnotationDriverTestParent
{
    /** @ODM\Field(type="string") */
    public $bar;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\ShardKey(keys={"foo"="asc"})
 */
class AnnotationDriverEmbeddedWithShardKey
{
    /** @ODM\Field(type="string") */
    public $foo;
}

/**
 * @ODM\Document(writeConcern="majority")
 */
class AnnotationDriverTestWriteConcernMajority
{
    /** @ODM\Id */
    public $id;
}

/**
 * @ODM\Document(writeConcern=0)
 */
class AnnotationDriverTestWriteConcernUnacknowledged
{
    /** @ODM\Id */
    public $id;
}
