<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $set stage to an aggregation pipeline.
 *
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type SetStageExpression array{'$set': array<string, OperatorExpression|mixed>}
 */
final class Set extends Operator
{
    /** @phpstan-return SetStageExpression */
    public function getExpression(): array
    {
        return ['$set' => $this->expr->getExpression()];
    }
}
