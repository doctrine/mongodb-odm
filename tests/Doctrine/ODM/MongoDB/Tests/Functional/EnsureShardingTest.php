<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Sharded\ShardedByUser;
use Documents\Sharded\ShardedOne;
use Documents\Sharded\ShardedOneWithDifferentKey;

use function iterator_to_array;

/** @group sharding */
class EnsureShardingTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipTestIfNotSharded(ShardedOne::class);
    }

    public function testEnsureShardingForNewCollection(): void
    {
        $class = ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes    = iterator_to_array($collection->listIndexes());
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        self::assertCount(2, $indexes);
        self::assertSame(['k' => 1], $indexes[1]['key']);
        self::assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForNewCollectionWithoutCreatingIndexes(): void
    {
        $class = ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes    = iterator_to_array($collection->listIndexes());
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        self::assertCount(2, $indexes);
        self::assertSame(['k' => 1], $indexes[1]['key']);
        self::assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithDocuments(): void
    {
        $class = ShardedOne::class;

        $document = new ShardedOne();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        self::assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithDocumentsThrowsIndexError(): void
    {
        $class = ShardedOne::class;

        $document = new ShardedOne();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Failed to ensure sharding for document');

        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        self::assertFalse($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithShardingEnabled(): void
    {
        $class = ShardedOneWithDifferentKey::class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $this->dm->getSchemaManager()->ensureDocumentSharding(ShardedOne::class);

        $collection = $this->dm->getDocumentCollection($class);
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];

        self::assertTrue($stats['sharded']);
    }

    public function testEnsureDocumentShardingWithShardByReference(): void
    {
        $class = ShardedByUser::class;

        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $stats      = $this->dm->getDocumentDatabase($class)->command(['collstats' => $collection->getCollectionName()])->toArray()[0];
        $indexes    = iterator_to_array($collection->listIndexes());

        self::assertTrue($stats['sharded']);

        self::assertCount(2, $indexes);
        self::assertSame(['db_user.$id' => 1], $indexes[1]->getKey());
    }
}
