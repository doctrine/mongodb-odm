<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Documents;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class DocumentsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $documentsStage = new Documents(
            $this->getTestAggregationBuilder(),
            [['x' => 10], ['x' => 2], ['x' => 5]],
        );

        self::assertSame(['$documents' => [['x' => 10], ['x' => 2], ['x' => 5]]], $documentsStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->documents([['x' => 10], ['x' => 2], ['x' => 5]]);

        self::assertSame([['$documents' => [['x' => 10], ['x' => 2], ['x' => 5]]]], $builder->getPipeline());
    }
}
