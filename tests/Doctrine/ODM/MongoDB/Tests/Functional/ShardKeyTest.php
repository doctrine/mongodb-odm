<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Sharded\ShardedOne;
use MongoDB\BSON\ObjectId;
use function end;
use function get_class;

/**
 * @group sharding
 */
class ShardKeyTest extends BaseTest
{
    /** @var CommandLogger */
    private $logger;

    public function setUp() : void
    {
        parent::setUp();

        $class = ShardedOne::class;
        $this->skipTestIfNotSharded($class);
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->ensureDocumentSharding($class);

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown() : void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testUpdateAfterSave()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        /** @var ShardedOne $o */
        $o        = $this->dm->find(get_class($o), $o->id);
        $o->title = 'test2';
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertSame('update', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        $this->assertCount(1, $command->updates);
        $this->assertEquals($o->key, $command->updates[0]->q->k);
    }

    public function testUpsert()
    {
        $o     = new ShardedOne();
        $o->id = new ObjectId();
        $this->dm->persist($o);
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertSame('update', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        $this->assertCount(1, $command->updates);
        $this->assertEquals($o->key, $command->updates[0]->q->k);
        $this->assertTrue($command->updates[0]->upsert);
    }

    public function testRemove()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->remove($o);
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertSame('delete', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        $this->assertCount(1, $command->deletes);
        $this->assertEquals($o->key, $command->deletes[0]->q->k);
    }

    public function testRefresh()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->refresh($o);

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        $this->assertSame('find', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        $this->assertSame(1, $command->limit);
        $this->assertEquals($o->key, $command->filter->k);
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
        $this->dm->flush(['upsert' => true]);
    }
}
