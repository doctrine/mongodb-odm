<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tests\CommandLogger;
use Documents\Sharded\ShardedOne;
use MongoDB\BSON\ObjectId;
use function array_keys;
use function end;
use function get_class;

class ShardKeyTest extends BaseTest
{
    /** @var CommandLogger */
    private $logger;

    public function setUp()
    {
        parent::setUp();

        $class = ShardedOne::class;
        $this->skipTestIfNotSharded($class);
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->ensureDocumentSharding($class);

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown()
    {
        $this->logger->unregister();

        return parent::tearDown();
    }

    public function testUpdateAfterSave()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        /** @var ShardedOne $o */
        $o = $this->dm->find(get_class($o), $o->id);
        $o->title = 'test2';
        $this->dm->flush();

        $queries = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['update']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    public function testUpsert()
    {
        $o = new ShardedOne();
        $o->id = new ObjectId();
        $this->dm->persist($o);
        $this->dm->flush();

        $queries = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['update']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    public function testRemove()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->remove($o);
        $this->dm->flush();

        $queries = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['remove']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    public function testRefresh()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->refresh($o);

        $queries = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['findOne']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testUpdateWithShardKeyChangeException()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o->key = 'testing2';
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testUpdateWithUpsertTrue()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o->key = 'testing2';
        $this->dm->flush(null, ['upsert' => true]);
    }
}
