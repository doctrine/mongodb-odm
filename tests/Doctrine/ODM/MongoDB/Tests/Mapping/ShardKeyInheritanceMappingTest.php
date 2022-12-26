<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ShardKeyInheritanceMappingTest extends BaseTest
{
    private ClassMetadataFactory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new ClassMetadataFactory();
        $this->factory->setDocumentManager($this->dm);
        $this->factory->setConfiguration($this->dm->getConfiguration());
    }

    public function testShardKeyFromMappedSuperclass(): void
    {
        $class = $this->factory->getMetadataFor(ShardedSubclass::class);

        self::assertTrue($class->isSharded());
        self::assertEquals(['keys' => ['_id' => 1], 'options' => []], $class->getShardKey());
    }

    public function testShardKeySingleCollectionInheritance(): void
    {
        $class = $this->factory->getMetadataFor(ShardedSingleCollInheritance2::class);

        self::assertTrue($class->isSharded());
        self::assertEquals(['keys' => ['_id' => 1], 'options' => []], $class->getShardKey());
    }

    public function testShardKeySingleCollectionInheritanceOverriding(): void
    {
        $this->expectException(MappingException::class);
        $this->factory->getMetadataFor(ShardedSingleCollInheritance3::class);
    }

    public function testShardKeyCollectionPerClassInheritance(): void
    {
        $class = $this->factory->getMetadataFor(ShardedCollectionPerClass2::class);

        self::assertTrue($class->isSharded());
        self::assertEquals(['keys' => ['_id' => 1], 'options' => []], $class->getShardKey());
    }

    public function testShardKeyCollectionPerClassInheritanceOverriding(): void
    {
        $class = $this->factory->getMetadataFor(ShardedCollectionPerClass3::class);

        self::assertTrue($class->isSharded());
        self::assertEquals(['keys' => ['_id' => 'hashed'], 'options' => []], $class->getShardKey());
    }
}


/**
 * @ODM\MappedSuperclass
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedSuperclass
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;
}

/** @ODM\Document */
class ShardedSubclass extends ShardedSuperclass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedSingleCollInheritance1
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}

/** @ODM\Document */
class ShardedSingleCollInheritance2 extends ShardedSingleCollInheritance1
{
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"_id"="hashed"})
 */
class ShardedSingleCollInheritance3 extends ShardedSingleCollInheritance1
{
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedCollectionPerClass1
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}

/** @ODM\Document */
class ShardedCollectionPerClass2 extends ShardedCollectionPerClass1
{
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"_id"="hashed"})
 */
class ShardedCollectionPerClass3 extends ShardedCollectionPerClass1
{
}
