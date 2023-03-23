<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $set stage to an aggregation pipeline.
 */
final class Set extends Operator
{
    public function getExpression(): array
    {
        return ['$set' => $this->expr->getExpression()];
    }
}
