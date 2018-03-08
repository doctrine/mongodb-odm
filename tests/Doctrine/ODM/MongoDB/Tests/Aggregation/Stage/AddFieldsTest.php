<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\AddFields;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class AddFieldsTest extends BaseTest
{
    use AggregationTestTrait;

    public function testAddFieldsStage()
    {
        $addFieldsStage = new AddFields($this->getTestAggregationBuilder());
        $addFieldsStage
            ->field('product')
            ->multiply('$field', 5);

        $this->assertSame(['$addFields' => ['product' => ['$multiply' => ['$field', 5]]]], $addFieldsStage->getExpression());
    }

    public function testProjectFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->addFields()
            ->field('product')
            ->multiply('$field', 5);

        $this->assertSame([['$addFields' => ['product' => ['$multiply' => ['$field', 5]]]]], $builder->getPipeline());
    }
}
