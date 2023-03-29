<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Group;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GroupTest extends BaseTestCase
{
    use AggregationTestTrait;
    use AggregationOperatorsProviderTrait;

    /**
     * @param array<string, string>          $expected
     * @param mixed[]|Closure(Expr): mixed[] $args
     *
     * @dataProvider provideGroupAccumulatorExpressionOperators
     */
    public function testGroupAccumulators(array $expected, string $operator, $args): void
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $args       = $this->resolveArgs($args);

        self::assertSame($groupStage, $groupStage->field('foo')->$operator(...$args));
        self::assertSame(['$group' => ['foo' => $expected]], $groupStage->getExpression());
    }

    public function testStage(): void
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $groupStage
            ->field('_id')
            ->expression('$field')
            ->field('count')
            ->sum(1);

        self::assertSame(['$group' => ['_id' => '$field', 'count' => ['$sum' => 1]]], $groupStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->group()
            ->field('_id')
            ->expression('$field')
            ->field('count')
            ->sum(1);

        self::assertSame([['$group' => ['_id' => '$field', 'count' => ['$sum' => 1]]]], $builder->getPipeline());
    }

    public function testWithOperatorInId(): void
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $groupStage
            ->field('_id')
            ->year('$dateField')
            ->field('count')
            ->sum(1);

        self::assertSame(['$group' => ['_id' => ['$year' => '$dateField'], 'count' => ['$sum' => 1]]], $groupStage->getExpression());
    }
}
