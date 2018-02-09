<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Sharded\ShardedOne;
use Documents\Sharded\ShardedOneWithDifferentKey;
use function iterator_to_array;

class EnsureShardingTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->skipTestIfNotSharded(ShardedOne::class);
    }

    public function testEnsureShardingForNewCollection()
    {
        $class = ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = iterator_to_array($collection->listIndexes());
        $stats = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        $this->assertCount(2, $indexes);
        $this->assertSame(['k' => 1], $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithDocuments()
    {
        $this->markTestSkipped('Test does not pass due to https://github.com/mongodb/mongo-php-driver/issues/296');
        $class = ShardedOne::class;
        $collection = $this->dm->getDocumentCollection($class);
        $doc = ['title' => 'hey', 'k' => 'hi'];
        $collection->insertOne($doc);

        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $indexes = iterator_to_array($collection->listIndexes());
        $stats = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        $this->assertCount(2, $indexes);
        $this->assertSame(['k' => 1], $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithShardingEnabled()
    {
        $class = ShardedOneWithDifferentKey::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $this->dm->getSchemaManager()->ensureDocumentSharding(ShardedOne::class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = iterator_to_array($collection->listIndexes());
        $stats = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        $this->assertCount(2, $indexes);
        $this->assertSame(['v' => 1], $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithData()
    {
        $this->markTestSkipped('Test does not pass due to https://github.com/mongodb/mongo-php-driver/issues/296');
        $document = new ShardedOne();
        $this->dm->persist($document);
        $this->dm->flush();

        $class = ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = iterator_to_array($collection->listIndexes());
        $stats = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        $this->assertCount(2, $indexes);
        $this->assertSame(['k' => 1], $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }
}
