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

        return array(
            array($builder->eq('field', 'value'), array('field' => 'value')),
            array($builder->contains('field', 'value'), array('field' => new \MongoRegex('/value/'))),
            array($builder->gt('field', 'value'), array('field' => array('$gt' => 'value'))),
            array($builder->gte('field', 'value'), array('field' => array('$gte' => 'value'))),
            array($builder->in('field', array(1, 2)), array('field' => array('$in' => array(1, 2)))),
            array($builder->isNull('field'), array('field' => null)),
            array($builder->lt('field', 'value'), array('field' => array('$lt' => 'value'))),
            array($builder->lte('field', 'value'), array('field' => array('$lte' => 'value'))),
            array($builder->neq('field', 'value'), array('field' => array('$ne' => 'value'))),
            array($builder->notIn('field', array(1, 2)), array('field' => array('$nin' => array(1, 2)))),
        );
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

        $expectedQuery = array('$and' => array(
            array('a' => 1),
            array('a' => array('$ne' => 2)),
            array('b' => 3),
        ));

        $expr = $this->visitor->dispatch($compositeExpr);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Query\Expr', $expr);
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWalkCompositeExpressionShouldThrowExceptionForUnsupportedComposite()
    {
        $compositeExpr = new CompositeExpression('invalidComposite', array());
        $expr = $this->visitor->dispatch($compositeExpr);
    }

    public function testWalkValue()
    {
        $this->assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }
}
