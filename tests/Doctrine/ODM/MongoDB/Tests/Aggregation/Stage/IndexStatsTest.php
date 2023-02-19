<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\IndexStats;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use stdClass;

class IndexStatsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testIndexStatsStage(): void
    {
        $indexStatsStage = new IndexStats($this->getTestAggregationBuilder());

        self::assertEquals(['$indexStats' => new stdClass()], $indexStatsStage->getExpression());
    }

    public function testIndexStatsFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->indexStats();

        self::assertEquals([['$indexStats' => new stdClass()]], $builder->getPipeline());
    }
}
