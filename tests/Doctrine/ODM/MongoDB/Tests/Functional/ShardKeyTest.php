<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Sharded\ShardedOne;
use MongoDB\BSON\ObjectId;

use function assert;
use function end;
use function get_class;

/** @group sharding */
class ShardKeyTest extends BaseTest
{
    private CommandLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $class = ShardedOne::class;
        $this->skipTestIfNotSharded($class);
        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->ensureDocumentSharding($class);

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown(): void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testUpdateAfterSave(): void
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o = $this->dm->find(get_class($o), $o->id);
        assert($o instanceof ShardedOne);
        $o->title = 'test2';
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        self::assertSame('update', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        self::assertCount(1, $command->updates);
        self::assertEquals($o->key, $command->updates[0]->q->k);
    }

    public function testUpsert(): void
    {
        $o     = new ShardedOne();
        $o->id = new ObjectId();
        $this->dm->persist($o);
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        self::assertSame('update', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        self::assertCount(1, $command->updates);
        self::assertEquals($o->key, $command->updates[0]->q->k);
        self::assertTrue($command->updates[0]->upsert);
    }

    public function testRemove(): void
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->remove($o);
        $this->dm->flush();

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        self::assertSame('delete', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        self::assertCount(1, $command->deletes);
        self::assertEquals($o->key, $command->deletes[0]->q->k);
    }

    public function testRefresh(): void
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->refresh($o);

        $queries   = $this->logger->getAll();
        $lastQuery = end($queries);
        self::assertSame('find', $lastQuery->getCommandName());

        $command = $lastQuery->getCommand();
        self::assertSame(1, $command->limit);
        self::assertEquals($o->key, $command->filter->k);
    }

    public function testUpdateWithShardKeyChangeException(): void
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o->key = 'testing2';
        $this->expectException(MongoDBException::class);
        $this->dm->flush();
    }

    public function testUpdateWithUpsertTrue(): void
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o->key = 'testing2';
        $this->expectException(MongoDBException::class);
        $this->dm->flush(['upsert' => true]);
    }
}
