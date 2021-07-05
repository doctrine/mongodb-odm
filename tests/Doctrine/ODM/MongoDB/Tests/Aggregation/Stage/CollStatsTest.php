<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\CollStats;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class CollStatsTest extends BaseTest
{
    use AggregationTestTrait;

    public function testCollStatsStage(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());

        $this->assertSame(['$collStats' => []], $collStatsStage->getExpression());
    }

    public function testCollStatsStageWithLatencyStats(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showLatencyStats();

        $this->assertSame(['$collStats' => ['latencyStats' => ['histograms' => false]]], $collStatsStage->getExpression());
    }

    public function testCollStatsStageWithLatencyStatsHistograms(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showLatencyStats(true);

        $this->assertSame(['$collStats' => ['latencyStats' => ['histograms' => true]]], $collStatsStage->getExpression());
    }

    public function testCollStatsStageWithStorageStats(): void
    {
        $collStatsStage = new CollStats($this->getTestAggregationBuilder());
        $collStatsStage->showStorageStats();

        $this->assertSame(['$collStats' => ['storageStats' => []]], $collStatsStage->getExpression());
    }

    public function testCollStatsFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->collStats()
            ->showLatencyStats(true)
            ->showStorageStats();

        $this->assertSame([
            [
                '$collStats' => [
                    'latencyStats' => ['histograms' => true],
                    'storageStats' => [],
                ],
            ],
        ], $builder->getPipeline());
    }
}
