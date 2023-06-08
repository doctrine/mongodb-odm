<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\CurrentOp;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class CurrentOpTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());

        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => false,
                'idleCursors' => false,
                'idleSessions' => true,
                'localOps' => false,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithAllUsers(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->reportAllUsers();
        self::assertSame([
            '$currentOp' => [
                'allUsers' => true,
                'idleConnections' => false,
                'idleCursors' => false,
                'idleSessions' => true,
                'localOps' => false,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithIdleConnections(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->reportIdleConnections();
        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => true,
                'idleCursors' => false,
                'idleSessions' => true,
                'localOps' => false,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithIdleCursors(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->reportIdleCursors();
        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => false,
                'idleCursors' => true,
                'idleSessions' => true,
                'localOps' => false,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithIdleSessions(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->reportIdleSessions(false);
        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => false,
                'idleCursors' => false,
                'idleSessions' => false,
                'localOps' => false,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithLocalOps(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->setLocalOps();
        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => false,
                'idleCursors' => false,
                'idleSessions' => true,
                'localOps' => true,
                'backtrace' => false,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testStageWithBacktrace(): void
    {
        $currentOpStage = new CurrentOp($this->getTestAggregationBuilder());
        $currentOpStage->setBacktrace();
        self::assertSame([
            '$currentOp' => [
                'allUsers' => false,
                'idleConnections' => false,
                'idleCursors' => false,
                'idleSessions' => true,
                'localOps' => false,
                'backtrace' => true,
            ],
        ], $currentOpStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->currentOp();

        self::assertSame([
            [
                '$currentOp' => [
                    'allUsers' => false,
                    'idleConnections' => false,
                    'idleCursors' => false,
                    'idleSessions' => true,
                    'localOps' => false,
                    'backtrace' => false,
                ],
            ],
        ], $builder->getPipeline());
    }
}
