<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $addFields stage to an aggregation pipeline.
 *
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type AddFieldsStageExpression array{'$addFields': array<string, OperatorExpression|mixed>}
 */
final class AddFields extends Operator
{
    /** @return AddFieldsStageExpression */
    public function getExpression(): array
    {
        return ['$addFields' => $this->expr->getExpression()];
    }
}
