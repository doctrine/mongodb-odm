<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Sample;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class SampleTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testSampleStage(): void
    {
        $sampleStage = new Sample($this->getTestAggregationBuilder(), 10);

        self::assertSame(['$sample' => ['size' => 10]], $sampleStage->getExpression());
    }

    public function testSampleFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->sample(10);

        self::assertSame([['$sample' => ['size' => 10]]], $builder->getPipeline());
    }
}
