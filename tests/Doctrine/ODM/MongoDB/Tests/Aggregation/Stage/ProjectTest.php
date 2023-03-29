<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Project;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ProjectTest extends BaseTestCase
{
    use AggregationOperatorsProviderTrait;
    use AggregationTestTrait;

    public function testStage(): void
    {
        $projectStage = new Project($this->getTestAggregationBuilder());
        $projectStage
            ->excludeFields(['_id'])
            ->includeFields(['$field', '$otherField'])
            ->field('product')
            ->multiply('$field', 5);

        self::assertSame(['$project' => ['_id' => false, '$field' => true, '$otherField' => true, 'product' => ['$multiply' => ['$field', 5]]]], $projectStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->project()
            ->excludeFields(['_id'])
            ->includeFields(['$field', '$otherField'])
            ->field('product')
            ->multiply('$field', 5);

        self::assertSame([['$project' => ['_id' => false, '$field' => true, '$otherField' => true, 'product' => ['$multiply' => ['$field', 5]]]]], $builder->getPipeline());
    }

    /** @dataProvider provideAccumulatorExpressionOperators */
    public function testAccumulatorsWithMultipleArguments(array $expected, string $operator, $args): void
    {
        $projectStage = new Project($this->getTestAggregationBuilder());
        $projectStage
            ->field('something')
            ->$operator(...$args);

        self::assertSame(['$project' => ['something' => $expected]], $projectStage->getExpression());
    }
}
