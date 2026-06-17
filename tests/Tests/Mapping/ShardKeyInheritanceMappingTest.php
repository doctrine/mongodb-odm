<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ShardKeyInheritanceMappingTest extends BaseTestCase
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
}


#[ODM\MappedSuperclass]
#[ODM\ShardKey(keys: ['_id' => 'asc'])]
class ShardedSuperclass
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $name;
}

#[ODM\Document]
class ShardedSubclass extends ShardedSuperclass
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}

#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\ShardKey(keys: ['_id' => 'asc'])]
class ShardedSingleCollInheritance1
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}

#[ODM\Document]
class ShardedSingleCollInheritance2 extends ShardedSingleCollInheritance1
{
}

#[ODM\Document]
#[ODM\ShardKey(keys: ['_id' => 'hashed'])]
class ShardedSingleCollInheritance3 extends ShardedSingleCollInheritance1
{
}
