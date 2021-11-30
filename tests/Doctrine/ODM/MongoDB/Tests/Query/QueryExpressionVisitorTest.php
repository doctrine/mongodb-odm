<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Bars\Bar;
use MongoDB\BSON\Regex;
use RuntimeException;

class QueryExpressionVisitorTest extends BaseTest
{
    /** @var Builder */
    private $queryBuilder;

    /** @var QueryExpressionVisitor */
    private $visitor;

    public function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = $this->dm->createQueryBuilder(Bar::class);
        $this->visitor      = new QueryExpressionVisitor($this->queryBuilder);
    }

    /**
     * @dataProvider provideComparisons
     */
    public function testWalkComparison(Comparison $comparison, array $expectedQuery): void
    {
        $expr = $this->visitor->dispatch($comparison);

        $this->assertInstanceOf(Expr::class, $expr);
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    public function provideComparisons(): array
    {
        $builder = new ExpressionBuilder();

        return [
            [$builder->eq('field', 'value'), ['field' => 'value']],
            [$builder->contains('field', 'value'), ['field' => new Regex('value', '')]],
            [$builder->gt('field', 'value'), ['field' => ['$gt' => 'value']]],
            [$builder->gte('field', 'value'), ['field' => ['$gte' => 'value']]],
            [$builder->in('field', [1, 2]), ['field' => ['$in' => [1, 2]]]],
            [$builder->isNull('field'), ['field' => null]],
            [$builder->lt('field', 'value'), ['field' => ['$lt' => 'value']]],
            [$builder->lte('field', 'value'), ['field' => ['$lte' => 'value']]],
            [$builder->neq('field', 'value'), ['field' => ['$ne' => 'value']]],
            [$builder->notIn('field', [1, 2]), ['field' => ['$nin' => [1, 2]]]],
        ];
    }

    public function testWalkComparisonShouldThrowExceptionForUnsupportedOperator(): void
    {
        $comparison = new Comparison('field', 'invalidOp', new Value('value'));
        $this->expectException(RuntimeException::class);
        $this->visitor->dispatch($comparison);
    }

    public function testWalkCompositeExpression(): void
    {
        $builder = new ExpressionBuilder();

        $compositeExpr = $builder->andX(
            $builder->eq('a', 1),
            $builder->neq('a', 2),
            $builder->eq('b', 3)
        );

        $expectedQuery = [
            '$and' => [
                ['a' => 1],
                ['a' => ['$ne' => 2]],
                ['b' => 3],
            ],
        ];

        $expr = $this->visitor->dispatch($compositeExpr);

        $this->assertInstanceOf(Expr::class, $expr);
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    public function testWalkCompositeExpressionShouldThrowExceptionForUnsupportedComposite(): void
    {
        $compositeExpr = new CompositeExpression('invalidComposite', []);
        $this->expectException(RuntimeException::class);
        $this->visitor->dispatch($compositeExpr);
    }

    public function testWalkValue(): void
    {
        $this->assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }
}
