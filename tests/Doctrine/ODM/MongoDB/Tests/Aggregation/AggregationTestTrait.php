<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr as AggregationExpr;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;
use Documents\User;
use PHPUnit\Framework\MockObject\MockBuilder;

trait AggregationTestTrait
{
    /**
     * @param string $className
     */
    abstract protected function getMockBuilder($className): MockBuilder;

    /**
     * @return Builder
     */
    protected function getTestAggregationBuilder(string $documentName = User::class)
    {
        return new Builder($this->dm, $documentName);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AggregationExpr
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
