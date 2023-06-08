<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\PlanCacheStats;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class PlanCacheStatsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $planCacheStatsStage = new PlanCacheStats($this->getTestAggregationBuilder());

        self::assertSame(['$planCacheStats' => []], $planCacheStatsStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->planCacheStats();

        self::assertSame([['$planCacheStats' => []]], $builder->getPipeline());
    }
}
