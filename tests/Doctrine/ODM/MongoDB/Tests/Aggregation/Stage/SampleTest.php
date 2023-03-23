<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Sample;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SampleTest extends BaseTest
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $sampleStage = new Sample($this->getTestAggregationBuilder(), 10);

        self::assertSame(['$sample' => ['size' => 10]], $sampleStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->sample(10);

        self::assertSame([['$sample' => ['size' => 10]]], $builder->getPipeline());
    }
}
