<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ShardKeyInheritanceMappingTest extends BaseTest
{
    /** @var ClassMetadataFactory */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new ClassMetadataFactory();
        $this->factory->setDocumentManager($this->dm);
        $this->factory->setConfiguration($this->dm->getConfiguration());
    }


    public function testShardKeyFromMappedSuperclass()
    {
        $class = $this->factory->getMetadataFor(ShardedSubclass::class);

        $this->assertTrue($class->isSharded());
        $this->assertEquals(array('keys' => array('_id' => 1), 'options' => array()), $class->getShardKey());
    }

    public function testShardKeySingleCollectionInheritance()
    {
        $class = $this->factory->getMetadataFor(ShardedSingleCollInheritance2::class);

        $this->assertTrue($class->isSharded());
        $this->assertEquals(array('keys' => array('_id' => 1), 'options' => array()), $class->getShardKey());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function testShardKeySingleCollectionInheritanceOverriding()
    {
        $this->factory->getMetadataFor(ShardedSingleCollInheritance3::class);
    }

    public function testShardKeyCollectionPerClassInheritance()
    {
        $class = $this->factory->getMetadataFor(ShardedCollectionPerClass2::class);

        $this->assertTrue($class->isSharded());
        $this->assertEquals(array('keys' => array('_id' => 1), 'options' => array()), $class->getShardKey());
    }

    public function testShardKeyCollectionPerClassInheritanceOverriding()
    {
        $class = $this->factory->getMetadataFor(ShardedCollectionPerClass3::class);

        $this->assertTrue($class->isSharded());
        $this->assertEquals(array('keys' => array('_id' => 'hashed'), 'options' => array()), $class->getShardKey());
    }
}


/**
 * @ODM\MappedSuperclass
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedSuperclass
{
    /** @ODM\Field(type="string") */
    private $name;
}

/** @ODM\Document */
class ShardedSubclass extends ShardedSuperclass
{
    /** @ODM\Id */
    private $id;
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedSingleCollInheritance1
{
    /** @ODM\Id */
    private $id;
}

/**
 * @ODM\Document
 */
class ShardedSingleCollInheritance2 extends ShardedSingleCollInheritance1
{}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"_id"="hashed"})
 */
class ShardedSingleCollInheritance3 extends ShardedSingleCollInheritance1
{}

/**
 * @ODM\Document
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedCollectionPerClass1
{
    /** @ODM\Id */
    private $id;
}

/**
 * @ODM\Document
 */
class ShardedCollectionPerClass2 extends ShardedCollectionPerClass1
{}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"_id"="hashed"})
 */
class ShardedCollectionPerClass3 extends ShardedCollectionPerClass1
{}
