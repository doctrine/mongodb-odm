<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tests\Functional\MappedSuperclassRelated1;

use function serialize;
use function unserialize;

class BasicInheritanceMappingTest extends BaseTest
{
    private ClassMetadataFactory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new ClassMetadataFactory();
        $this->factory->setDocumentManager($this->dm);
        $this->factory->setConfiguration($this->dm->getConfiguration());
    }

    public function testGetMetadataForTransientClassThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->factory->getMetadataFor(TransientBaseClass::class);
    }

    public function testGetMetadataForSubclassWithTransientBaseClass(): void
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass::class);

        self::assertEmpty($class->subClasses);
        self::assertEmpty($class->parentClasses);
        self::assertTrue(isset($class->fieldMappings['id']));
        self::assertTrue(isset($class->fieldMappings['name']));
    }

    public function testGetMetadataForSubclassWithMappedSuperclass(): void
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        self::assertEmpty($class->subClasses);
        self::assertEmpty($class->parentClasses);

        self::assertTrue(isset($class->fieldMappings['mapped1']));
        self::assertTrue(isset($class->fieldMappings['mapped2']));
        self::assertTrue(isset($class->fieldMappings['id']));
        self::assertTrue(isset($class->fieldMappings['name']));

        self::assertFalse(isset($class->fieldMappings['mapped1']['inherited']));
        self::assertFalse(isset($class->fieldMappings['mapped2']['inherited']));
        self::assertFalse(isset($class->fieldMappings['transient']));

        self::assertTrue(isset($class->fieldMappings['mappedRelated1']));
    }

    /** @group DDC-388 */
    public function testSerializationWithPrivateFieldsFromMappedSuperclass(): void
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        $class2 = unserialize(serialize($class));

        self::assertTrue(isset($class2->reflFields['mapped1']));
        self::assertTrue(isset($class2->reflFields['mapped2']));
        self::assertTrue(isset($class2->reflFields['mappedRelated1']));
    }

    public function testReadPreferenceIsInherited(): void
    {
        $class = $this->factory->getMetadataFor(DocumentSubClass2::class);

        self::assertSame('secondary', $class->readPreference);
        self::assertEquals([['dc' => 'east']], $class->readPreferenceTags);
    }

    public function testGridFSOptionsAreInherited(): void
    {
        $class = $this->factory->getMetadataFor(GridFSChildClass::class);

        self::assertTrue($class->isFile);
        self::assertSame(112, $class->getChunkSizeBytes());
        self::assertSame('myFile', $class->getBucketName());
    }
}

class TransientBaseClass
{
    /** @var mixed */
    private $transient1;

    /** @var mixed */
    private $transient2;
}

/** @ODM\Document */
class DocumentSubClass extends TransientBaseClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;
}

/**
 * @ODM\MappedSuperclass
 * @ODM\ReadPreference("secondary", tags={ { "dc"="east" } })
 */
class MappedSuperclassBase
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $mapped1;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $mapped2;

    /**
     * @ODM\ReferenceOne(targetDocument=MappedSuperclassRelated1::class)
     *
     * @var MappedSuperclassRelated1|null
     */
    private $mappedRelated1;

    /** @var mixed */
    private $transient;
}

/** @ODM\Document */
class DocumentSubClass2 extends MappedSuperclassBase
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;
}

/**
 * @ODM\File(bucketName="myFile", chunkSizeBytes=112)
 * @ODM\DiscriminatorField("type")
 */
class GridFSParentClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}

/** @ODM\File */
class GridFSChildClass extends GridFSParentClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}
