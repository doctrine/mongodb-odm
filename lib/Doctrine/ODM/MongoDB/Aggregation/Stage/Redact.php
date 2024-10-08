<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $redact stage to an aggregation pipeline.
 *
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type SetStageExpression array{'$redact': array<string, OperatorExpression|mixed>}
 */
class Redact extends Operator
{
    public function getExpression(): array
    {
        return [
            '$redact' => $this->expr->getExpression(),
        ];
    }
}
