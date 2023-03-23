<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Count;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class CountTest extends BaseTest
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $countStage = new Count($this->getTestAggregationBuilder(), 'document_count');

        self::assertSame(['$count' => 'document_count'], $countStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->count('document_count');

        self::assertSame([['$count' => 'document_count']], $builder->getPipeline());
    }
}
