<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Skip;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SkipTest extends BaseTest
{
    use AggregationTestTrait;

    public function testSkipStage()
    {
        $skipStage = new Skip($this->getTestAggregationBuilder(), 10);

        $this->assertSame(['$skip' => 10], $skipStage->getExpression());
    }

    public function testSkipFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->skip(10);

        $this->assertSame([['$skip' => 10]], $builder->getPipeline());
    }
}
