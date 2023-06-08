<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\ShardedDataDistribution;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ShardedDataDistributionTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $shardedDataDistributionStage = new ShardedDataDistribution($this->getTestAggregationBuilder());

        self::assertSame(['$shardedDataDistribution' => []], $shardedDataDistributionStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->shardedDataDistribution();

        self::assertSame([['$shardedDataDistribution' => []]], $builder->getPipeline());
    }
}
