<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class QueryExpressionVisitorTest extends BaseTest
{
    private $queryBuilder;
    private $visitor;

    public function setUp()
    {
        parent::setUp();
        $this->queryBuilder = $this->dm->createQueryBuilder('Documents\Bars\Bar');
        $this->visitor = new QueryExpressionVisitor($this->queryBuilder);
    }

    /**
     * @dataProvider provideComparisons
     */
    public function testWalkComparison(Comparison $comparison, array $expectedQuery)
    {
        $expr = $this->visitor->dispatch($comparison);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Query\Expr', $expr);
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    public function provideComparisons()
    {
        $builder = new ExpressionBuilder();

        return [
            [$builder->eq('field', 'value'), ['field' => 'value']],
            [$builder->contains('field', 'value'), ['field' => new \MongoRegex('/value/')]],
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

    /**
     * @expectedException RuntimeException
     */
    public function testWalkComparisonShouldThrowExceptionForUnsupportedOperator()
    {
        $comparison = new Comparison('field', 'invalidOp', new Value('value'));
        $this->visitor->dispatch($comparison);
    }

    public function testWalkCompositeExpression()
    {
        $builder = new ExpressionBuilder();

        $compositeExpr = $builder->andX(
            $builder->eq('a', 1),
            $builder->neq('a', 2),
            $builder->eq('b', 3)
        );

        $expectedQuery = ['$and' => [
            ['a' => 1],
            ['a' => ['$ne' => 2]],
            ['b' => 3],
        ]];

        $expr = $this->visitor->dispatch($compositeExpr);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Query\Expr', $expr);
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWalkCompositeExpressionShouldThrowExceptionForUnsupportedComposite()
    {
        $compositeExpr = new CompositeExpression('invalidComposite', []);
        $expr = $this->visitor->dispatch($compositeExpr);
    }

    public function testWalkValue()
    {
        $this->assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }
}
