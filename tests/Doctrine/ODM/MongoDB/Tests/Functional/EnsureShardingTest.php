<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class EnsureShardingTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->skipTestIfNotSharded(\Documents\Sharded\ShardedOne::class);
    }

    public function testEnsureShardingForNewCollection()
    {
        $class = \Documents\Sharded\ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = $collection->getIndexInfo();
        $stats = $this->dm->getDocumentDatabase($class)->command(array('collstats' => $collection->getName()));

        $this->assertCount(2, $indexes);
        $this->assertSame(array('k' => 1), $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithDocuments()
    {
        $class = \Documents\Sharded\ShardedOne::class;
        $collection = $this->dm->getDocumentCollection($class);
        $doc = array('title' => 'hey', 'k' => 'hi');
        $collection->insert($doc);

        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $indexes = $collection->getIndexInfo();
        $stats = $this->dm->getDocumentDatabase($class)->command(array('collstats' => $collection->getName()));

        $this->assertCount(2, $indexes);
        $this->assertSame(array('k' => 1), $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithShardingEnabled()
    {
        $class = \Documents\Sharded\ShardedOneWithDifferentKey::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $this->dm->getSchemaManager()->ensureDocumentSharding(\Documents\Sharded\ShardedOne::class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = $collection->getIndexInfo();
        $stats = $this->dm->getDocumentDatabase($class)->command(array('collstats' => $collection->getName()));

        $this->assertCount(2, $indexes);
        $this->assertSame(array('v' => 1), $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    public function testEnsureShardingForCollectionWithData()
    {
        $document = new \Documents\Sharded\ShardedOne();
        $this->dm->persist($document);
        $this->dm->flush();

        $class = \Documents\Sharded\ShardedOne::class;
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = $collection->getIndexInfo();
        $stats = $this->dm->getDocumentDatabase($class)->command(array('collstats' => $collection->getName()));

        $this->assertCount(2, $indexes);
        $this->assertSame(array('k' => 1), $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }
}
