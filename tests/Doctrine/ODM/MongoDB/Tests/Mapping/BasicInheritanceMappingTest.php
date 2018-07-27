<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use function serialize;
use function unserialize;

class BasicInheritanceMappingTest extends BaseTest
{
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new ClassMetadataFactory();
        $this->factory->setDocumentManager($this->dm);
        $this->factory->setConfiguration($this->dm->getConfiguration());
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function testGetMetadataForTransientClassThrowsException()
    {
        $this->factory->getMetadataFor(TransientBaseClass::class);
    }

    public function testGetMetadataForSubclassWithTransientBaseClass()
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass::class);

        $this->assertEmpty($class->subClasses);
        $this->assertEmpty($class->parentClasses);
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
    }

    public function testGetMetadataForSubclassWithMappedSuperclass()
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        $this->assertEmpty($class->subClasses);
        $this->assertEmpty($class->parentClasses);

        $this->assertTrue(isset($class->fieldMappings['mapped1']));
        $this->assertTrue(isset($class->fieldMappings['mapped2']));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));

        $this->assertFalse(isset($class->fieldMappings['mapped1']['inherited']));
        $this->assertFalse(isset($class->fieldMappings['mapped2']['inherited']));
        $this->assertFalse(isset($class->fieldMappings['transient']));

        $this->assertTrue(isset($class->fieldMappings['mappedRelated1']));
    }

    /**
     * @group DDC-388
     */
    public function testSerializationWithPrivateFieldsFromMappedSuperclass()
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        $class2 = unserialize(serialize($class));

        $this->assertTrue(isset($class2->reflFields['mapped1']));
        $this->assertTrue(isset($class2->reflFields['mapped2']));
        $this->assertTrue(isset($class2->reflFields['mappedRelated1']));
    }

    public function testReadPreferenceIsInherited()
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        $this->assertSame('secondary', $class->readPreference);
        $this->assertEquals([ ['dc' => 'east'] ], $class->readPreferenceTags);
    }
}

class TransientBaseClass
{
    private $transient1;
    private $transient2;
}

/** @ODM\Document */
class DocumentSubClass extends TransientBaseClass
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;
}

/**
 * @ODM\MappedSuperclass
 * @ODM\ReadPreference("secondary", tags={ { "dc"="east" } })
 */
class MappedSuperclassBase
{
    /** @ODM\Field(type="string") */
    private $mapped1;
    /** @ODM\Field(type="string") */
    private $mapped2;

    /** @ODM\ReferenceOne(targetDocument=MappedSuperclassRelated1::class) */
    private $mappedRelated1;

    private $transient;
}

/** @ODM\Document */
class DocumentSubClass2 extends MappedSuperclassBase
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;
}
