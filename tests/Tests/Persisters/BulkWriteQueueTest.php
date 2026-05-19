<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Persisters\BulkWriteQueue;
use MongoDB\BSON\ObjectId;
use MongoDB\BulkWriteResult;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class BulkWriteQueueTest extends TestCase
{
    public function testQueueStartsEmpty(): void
    {
        $queue = new BulkWriteQueue();

        self::assertFalse($queue->hasPending());
    }

    public function testFlushIsNoOpWhenEmpty(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::never())->method('bulkWrite');

        $queue->flush();

        self::assertFalse($queue->hasPending());
    }

    public function testOpsArePreservedInInsertionOrder(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();

        $queue->addInsertOne($collection, ['_id' => 1, 'name' => 'first'], $document);
        $queue->addUpdateOne($collection, ['_id' => 1], ['$set' => ['name' => 'second']], [], $document);
        $queue->addDeleteOne($collection, ['_id' => 1], [], $document);

        $expectedOps = [
            ['insertOne' => [['_id' => 1, 'name' => 'first']]],
            ['updateOne' => [['_id' => 1], ['$set' => ['name' => 'second']]]],
            ['deleteOne' => [['_id' => 1]]],
        ];

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->with($expectedOps, ['ordered' => true])
            ->willReturn($this->createWriteResultMock());

        $queue->flush();
    }

    public function testUpdateOptionsAreForwarded(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();

        $queue->addUpdateOne($collection, ['_id' => 1], ['$set' => ['x' => 1]], ['upsert' => true], $document);

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->with(
                [['updateOne' => [['_id' => 1], ['$set' => ['x' => 1]], ['upsert' => true]]]],
                ['ordered' => true],
            )
            ->willReturn($this->createWriteResultMock());

        $queue->flush();
    }

    public function testInsertCallbackReceivesInsertedId(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();
        $captured   = null;

        $queue->addInsertOne($collection, ['_id' => 'fixed'], $document, static function ($insertedId) use (&$captured): void {
            $captured = $insertedId;
        });

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->willReturn($this->createWriteResultMock(insertedIds: [0 => 'fixed']));

        $queue->flush();

        self::assertSame('fixed', $captured);
    }

    public function testUpdateCallbackReceivesAggregateCounts(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();
        $captured   = null;

        $queue->addUpdateOne(
            $collection,
            ['_id' => 1],
            ['$set' => ['x' => 1]],
            [],
            $document,
            static function (int $matched, int $modified, ?ObjectId $upsertedId) use (&$captured): void {
                $captured = [$matched, $modified, $upsertedId];
            },
        );

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->willReturn($this->createWriteResultMock(matched: 1, modified: 1));

        $queue->flush();

        self::assertSame([1, 1, null], $captured);
    }

    public function testDeleteCallbackReceivesAggregateCount(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();
        $captured   = null;

        $queue->addDeleteOne(
            $collection,
            ['_id' => 1],
            [],
            $document,
            static function (int $deletedCount) use (&$captured): void {
                $captured = $deletedCount;
            },
        );

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->willReturn($this->createWriteResultMock(deleted: 1));

        $queue->flush();

        self::assertSame(1, $captured);
    }

    public function testFlushIssuesOneBulkPerCollection(): void
    {
        $queue       = new BulkWriteQueue();
        $collectionA = $this->createMock(Collection::class);
        $collectionB = $this->createMock(Collection::class);
        $document    = new stdClass();

        $queue->addInsertOne($collectionA, ['_id' => 1], $document);
        $queue->addInsertOne($collectionB, ['_id' => 2], $document);
        $queue->addUpdateOne($collectionA, ['_id' => 1], ['$set' => ['x' => 1]], [], $document);

        $collectionA->expects(self::once())
            ->method('bulkWrite')
            ->with(
                [
                    ['insertOne' => [['_id' => 1]]],
                    ['updateOne' => [['_id' => 1], ['$set' => ['x' => 1]]]],
                ],
                ['ordered' => true],
            )
            ->willReturn($this->createWriteResultMock());

        $collectionB->expects(self::once())
            ->method('bulkWrite')
            ->with([['insertOne' => [['_id' => 2]]]], ['ordered' => true])
            ->willReturn($this->createWriteResultMock());

        $queue->flush();

        self::assertFalse($queue->hasPending());
    }

    public function testFlushForwardsTopLevelOptions(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();

        $queue->addInsertOne($collection, ['_id' => 1], $document);

        $collection->expects(self::once())
            ->method('bulkWrite')
            ->with(self::anything(), ['ordered' => true, 'session' => 'session-token'])
            ->willReturn($this->createWriteResultMock());

        $queue->flush(['session' => 'session-token']);
    }

    public function testFlushClearsQueueOnBulkWriteException(): void
    {
        $queue       = new BulkWriteQueue();
        $collectionA = $this->createMock(Collection::class);
        $collectionB = $this->createMock(Collection::class);
        $document    = new stdClass();

        $queue->addInsertOne($collectionA, ['_id' => 1], $document);
        $queue->addInsertOne($collectionB, ['_id' => 2], $document);

        $collectionA->expects(self::once())
            ->method('bulkWrite')
            ->willThrowException(new BulkWriteException('boom'));

        // Once collection A fails the queue must not continue with collection B.
        $collectionB->expects(self::never())->method('bulkWrite');

        $this->expectException(BulkWriteException::class);

        try {
            $queue->flush();
        } finally {
            self::assertFalse($queue->hasPending());
        }
    }

    public function testClearDropsPendingOps(): void
    {
        $queue      = new BulkWriteQueue();
        $collection = $this->createMock(Collection::class);
        $document   = new stdClass();

        $queue->addInsertOne($collection, ['_id' => 1], $document);
        self::assertTrue($queue->hasPending());

        $queue->clear();

        self::assertFalse($queue->hasPending());
    }

    /**
     * @param array<int, mixed> $insertedIds
     * @param array<int, mixed> $upsertedIds
     */
    private function createWriteResultMock(
        int $matched = 0,
        int $modified = 0,
        int $deleted = 0,
        array $insertedIds = [],
        array $upsertedIds = [],
    ): BulkWriteResult {
        $result = $this->createMock(BulkWriteResult::class);
        $result->method('getMatchedCount')->willReturn($matched);
        $result->method('getModifiedCount')->willReturn($modified);
        $result->method('getDeletedCount')->willReturn($deleted);
        $result->method('getInsertedIds')->willReturn($insertedIds);
        $result->method('getUpsertedIds')->willReturn($upsertedIds);

        return $result;
    }
}
