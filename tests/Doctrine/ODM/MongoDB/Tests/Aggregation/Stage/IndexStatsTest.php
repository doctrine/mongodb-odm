<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\IndexStats;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class IndexStatsTest extends BaseTest
{
    use AggregationTestTrait;

    public function testIndexStatsStage()
    {
        $indexStatsStage = new IndexStats($this->getTestAggregationBuilder());

        $this->assertEquals(['$indexStats' => new \stdClass()], $indexStatsStage->getExpression());
    }

    public function testIndexStatsFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->indexStats();

        $this->assertEquals([['$indexStats' => new \stdClass()]], $builder->getPipeline());
    }
}
