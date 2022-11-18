<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr as AggregationExpr;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;
use Documents\User;
use PHPUnit\Framework\MockObject\MockObject;

trait AggregationTestTrait
{
    protected function getTestAggregationBuilder(string $documentName = User::class): Builder
    {
        return new Builder($this->dm, $documentName);
    }

    /** @return MockObject|AggregationExpr */
    protected function getMockAggregationExpr()
    {
        return $this->createMock(AggregationExpr::class);
    }

    /** @return MockObject|QueryExpr */
    protected function getMockQueryExpr()
    {
        return $this->createMock(QueryExpr::class);
    }
}
