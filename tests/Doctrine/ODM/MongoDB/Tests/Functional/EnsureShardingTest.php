<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class EnsureShardingTest extends BaseTest
{
    /**
     * @group sharding
     */
    public function testEnsureShardingForNewCollection()
    {
        $class = 'Documents\Sharded\ShardedOne';
        $this->dm->getSchemaManager()->ensureDocumentSharding($class);

        $collection = $this->dm->getDocumentCollection($class);
        $indexes = $collection->getIndexInfo();
        $stats = $this->dm->getDocumentDatabase($class)->command(array('collstats' => $collection->getName()));

        $this->assertCount(2, $indexes);
        $this->assertSame(array('k' => 1), $indexes[1]['key']);
        $this->assertTrue($stats['sharded']);
    }

    /**
     * @group sharding
     */
    public function testEnsureShardingForCollectionWithDocuments()
    {
        $class = 'Documents\Sharded\ShardedOne';
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
}
