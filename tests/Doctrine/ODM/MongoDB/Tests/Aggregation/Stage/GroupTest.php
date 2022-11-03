<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Group;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GroupTest extends BaseTest
{
    use AggregationTestTrait;
    use AggregationOperatorsProviderTrait;

    /**
     * @param Closure(Expr): Expr[]|mixed[] $args
     *
     * @dataProvider provideProxiedExprMethods
     */
    public function testProxiedExprMethods(string $method, $args = []): void
    {
        $args = $this->resolveArgs($args);

        $expr = $this->getMockAggregationExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $stage = new class ($this->getTestAggregationBuilder()) extends Group {
            public function setExpr(Expr $expr): void
            {
                $this->expr = $expr;
            }
        };
        $stage->setExpr($expr);

        self::assertSame($stage, $stage->$method(...$args));
    }

    public function provideProxiedExprMethods(): array
    {
        return [
            'addToSet()' => ['addToSet', ['$field']],
            'avg()' => ['avg', ['$field']],
            'expression()' => [
                'expression',
                static function (Expr $expr) {
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

    public function testGroupStage(): void
    {
        $groupStage = new Group($this->getTestAggregationBuilder());
        $groupStage
            ->field('_id')
            ->expression('$field')
            ->field('count')
            ->sum(1);

        self::assertSame(['$group' => ['_id' => '$field', 'count' => ['$sum' => 1]]], $groupStage->getExpression());
    }

    public function testGroupFromBuilder(): void
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

    public function testGroupWithOperatorInId(): void
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
