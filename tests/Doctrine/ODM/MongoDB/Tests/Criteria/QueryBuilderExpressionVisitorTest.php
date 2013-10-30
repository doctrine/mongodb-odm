<?php

namespace Doctrine\ODM\MongoDB\Tests\Criteria;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ODM\MongoDB\Criteria\QueryBuilderExpressionVisitor;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class QueryBuilderExpressionVisitorTest extends BaseTest
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var QueryBuilderExpressionVisitor
     */
    protected $visitor;

    public function setUp()
    {
        parent::setUp();
        $this->queryBuilder = $this->dm->createQueryBuilder('Documents\Bars\Bar');
        $this->visitor      = new QueryBuilderExpressionVisitor($this->queryBuilder);
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkValue
     */
    public function testWalkValueReturnValueDirectly()
    {
        $valueExpr = new Value('bar');
        $result    = $this->visitor->dispatch($valueExpr);

        $this->assertEquals('bar', $result);
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkComparison
     */
    public function testThrowExceptionWithInvalidOperator()
    {
        $this->setExpectedException('Doctrine\ODM\MongoDB\Criteria\RuntimeException');

        $comparison = new Comparison('name', 'lolzExpr', 'baz');
        $this->visitor->dispatch($comparison);
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkComparison
     */
    public function testDispatchWithSimpleEqualsComparison()
    {
        $comparison = new Comparison('name', '=', 'baz');
        $result     = $this->visitor->dispatch($comparison);

        $this->assertEquals(array('name' => 'baz'), $result->getQuery());
    }

    /**
     * @return array
     */
    public function comparisonProvider()
    {
        return array(
            array('operator' => '<>', 'command' => '$ne'),
            array('operator' => '<', 'command' => '$lt'),
            array('operator' => '<=', 'command' => '$lte'),
            array('operator' => '>', 'command' => '$gt'),
            array('operator' => '>=', 'command' => '$gte'),
            array('operator' => 'IN', 'command' => '$in'),
            array('operator' => 'NIN', 'command' => '$nin')
        );
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkComparison
     * @dataProvider comparisonProvider
     *
     * @param string $operator
     * @param string $command
     * @param bool $isValueArray
     */
    public function testDispatchWithSimpleComparison($operator, $command, $isValueArray = false)
    {
        $comparison = new Comparison('name', $operator, 'baz');
        $result     = $this->visitor->dispatch($comparison);

        if ($isValueArray) {
            $expected   = array(
                'name' => array(
                    $command => array('baz')
                )
            );
        } else {
            $expected   = array(
                'name' => array(
                    $command => 'baz'
                )
            );
        }

        $this->assertEquals($expected, $result->getQuery());
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkCompositeExpression
     */
    public function testDispatchWithOrCompositeExpression()
    {
        $expr1 = new Comparison('name', '=', 'baz');
        $expr2 = new Comparison('other', '<>', 'barBaz');

        $criteria = new Criteria($expr1);
        $criteria->andWhere($expr2);

        $result   = $this->visitor->dispatch($criteria->getWhereExpression());
        $expected = array(
            '$and' => array(
                array('name' => 'baz'),
                array('other' => array(
                    '$ne' => 'barBaz'
                ))
            )
        );

        $this->assertEquals($expected, $result->getQuery());
    }

    /**
     * @covers QueryBuilderExpressionVisitor::walkCompositeExpression
     */
    public function testDispatchWithAndCompositeExpression()
    {
        $expr1 = new Comparison('name', '=', 'baz');
        $expr2 = new Comparison('other', '<>', 'barBaz');

        $criteria = new Criteria($expr1);
        $criteria->orWhere($expr2);

        $result   = $this->visitor->dispatch($criteria->getWhereExpression());
        $expected = array(
            '$or' => array(
                array('name' => 'baz'),
                array('other' => array(
                    '$ne' => 'barBaz'
                ))
            )
        );

        $this->assertEquals($expected, $result->getQuery());
    }
}