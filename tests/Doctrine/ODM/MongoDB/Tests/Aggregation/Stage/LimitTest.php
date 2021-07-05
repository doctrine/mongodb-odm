<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Limit;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class LimitTest extends BaseTest
{
    use AggregationTestTrait;

    public function testLimitStage(): void
    {
        $limitStage = new Limit($this->getTestAggregationBuilder(), 10);

        $this->assertSame(['$limit' => 10], $limitStage->getExpression());
    }

    public function testLimitFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->limit(10);

        $this->assertSame([['$limit' => 10]], $builder->getPipeline());
    }
}
