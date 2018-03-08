<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Group;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GroupTest extends BaseTest
{
    use AggregationTestTrait, AggregationOperatorsProviderTrait;

    /**
     * @dataProvider provideProxiedExprMethods
     */
    public function testProxiedExprMethods($method, $args = [])
    {
        $args = $this->resolveArgs($args);

        $expr = $this->getMockAggregationExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $stage = new class($this->getTestAggregationBuilder()) extends Group {
            public function setExpr(Expr $expr)
            {
                $this->expr = $expr;
            }
        };
        $stage->setExpr($expr);

        $this->assertSame($stage, $stage->$method(...$args));
    }

    public function provideProxiedExprMethods()
    {
        return [
            'addToSet()' => ['addToSet', ['$field']],
            'avg()' => ['avg', ['$field']],
            'expression()' => [
        'expression',
        function (Expr $expr) {
                $expr
                    ->field('dayOfMonth')
                    ->dayOfMonth('$dateField')
                    ->field('dayOfWeek')
                    ->dayOfWeek('$dateField');

                return [$expr];
        },
            ],
            'first()' => ['first', ['$field']],
            'last()' => ['last', ['$field']],
            'max()' => ['max', ['$field']],
            'min()' => ['min', ['$field']],
            'push()' => ['push', ['$field']],
            'stdDevPop()' => ['stdDevPop', ['$field']],
            'stdDevSamp()' => ['stdDevSamp', ['$field']],
            'sum()' => ['sum', ['$field']],
        ];
    }

    public function testGroupStage()
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $groupStage
            ->field('_id')
            ->expression('$field')
            ->field('count')
            ->sum(1);

        $this->assertSame(['$group' => ['_id' => '$field', 'count' => ['$sum' => 1]]], $groupStage->getExpression());
    }

    public function testGroupFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->group()
            ->field('_id')
            ->expression('$field')
            ->field('count')
            ->sum(1);

        $this->assertSame([['$group' => ['_id' => '$field', 'count' => ['$sum' => 1]]]], $builder->getPipeline());
    }

    public function testGroupWithOperatorInId()
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $groupStage
            ->field('_id')
            ->year('$dateField')
            ->field('count')
            ->sum(1);

        $this->assertSame(['$group' => ['_id' => ['$year' => '$dateField'], 'count' => ['$sum' => 1]]], $groupStage->getExpression());
    }
}
