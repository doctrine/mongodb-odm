<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Unwind;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class UnwindTest extends BaseTest
{
    use AggregationTestTrait;

    public function testUnwindStage()
    {
        $unwindStage = new Unwind($this->getTestAggregationBuilder(), 'fieldName');

        $this->assertSame(['$unwind' => 'fieldName'], $unwindStage->getExpression());
    }

    public function testUnwindStageWithNewFields()
    {
        $unwindStage = new Unwind($this->getTestAggregationBuilder(), 'fieldName');
        $unwindStage
            ->preserveNullAndEmptyArrays()
            ->includeArrayIndex('index');

        $this->assertSame(['$unwind' => ['path' => 'fieldName', 'includeArrayIndex' => 'index', 'preserveNullAndEmptyArrays' => true]], $unwindStage->getExpression());
    }

    public function testUnwindFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->unwind('fieldName');

        $this->assertSame([['$unwind' => 'fieldName']], $builder->getPipeline());
    }

    public function testSubsequentUnwindStagesArePreserved()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->unwind('fieldName')
            ->unwind('otherField');

        $this->assertSame([['$unwind' => 'fieldName'], ['$unwind' => 'otherField']], $builder->getPipeline());
    }
}
