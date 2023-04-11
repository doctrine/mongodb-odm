<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Unwind;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class UnwindTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $unwindStage = new Unwind($this->getTestAggregationBuilder(), 'fieldName');

        self::assertSame(['$unwind' => 'fieldName'], $unwindStage->getExpression());
    }

    public function testStageWithNewFields(): void
    {
        $unwindStage = new Unwind($this->getTestAggregationBuilder(), 'fieldName');
        $unwindStage
            ->preserveNullAndEmptyArrays()
            ->includeArrayIndex('index');

        self::assertSame(['$unwind' => ['path' => 'fieldName', 'includeArrayIndex' => 'index', 'preserveNullAndEmptyArrays' => true]], $unwindStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->unwind('fieldName');

        self::assertSame([['$unwind' => 'fieldName']], $builder->getPipeline());
    }

    public function testSubsequentUnwindStagesArePreserved(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->unwind('fieldName')
            ->unwind('otherField');

        self::assertSame([['$unwind' => 'fieldName'], ['$unwind' => 'otherField']], $builder->getPipeline());
    }
}
