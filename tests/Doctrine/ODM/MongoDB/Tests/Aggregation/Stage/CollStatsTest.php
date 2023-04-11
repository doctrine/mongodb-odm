<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\CollStats;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class CollStatsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());

        self::assertSame(['$collStats' => []], $collStatsStage->getExpression());
    }

    public function testStageWithLatencyStats(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showLatencyStats();

        self::assertSame(['$collStats' => ['latencyStats' => ['histograms' => false]]], $collStatsStage->getExpression());
    }

    public function testStageWithLatencyStatsHistograms(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showLatencyStats(true);

        self::assertSame(['$collStats' => ['latencyStats' => ['histograms' => true]]], $collStatsStage->getExpression());
    }

    public function testStageWithStorageStats(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showStorageStats();

        self::assertSame(['$collStats' => ['storageStats' => []]], $collStatsStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->collStats()
            ->showLatencyStats(true)
            ->showStorageStats();

        self::assertSame([
            [
                '$collStats' => [
                    'latencyStats' => ['histograms' => true],
                    'storageStats' => [],
                ],
            ],
        ], $builder->getPipeline());
    }
}
