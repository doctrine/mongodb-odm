<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr as AggregationExpr;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;
use Documents\User;

trait AggregationTestTrait
{
    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    abstract protected function getMockBuilder($className);

    /**
     * @return Builder
     */
    protected function getTestAggregationBuilder(string $documentName = User::class)
    {
        return new Builder($this->dm, $documentName);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ODM\MongoDB\Aggregation\Expr
     */
    protected function getMockAggregationExpr()
    {
        return $this->getMockBuilder(AggregationExpr::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueryExpr
     */
    protected function getMockQueryExpr()
    {
        return $this->getMockBuilder(QueryExpr::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
