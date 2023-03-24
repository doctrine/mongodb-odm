<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Set;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SetTest extends BaseTest
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $setStage = new Set($this->getTestAggregationBuilder());
        $setStage
            ->field('product')
            ->multiply('$field', 5);

        self::assertSame(['$set' => ['product' => ['$multiply' => ['$field', 5]]]], $setStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->set()
                ->field('product')
                ->multiply('$field', 5);

        self::assertSame([['$set' => ['product' => ['$multiply' => ['$field', 5]]]]], $builder->getPipeline());
    }
}
