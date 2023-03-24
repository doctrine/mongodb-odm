<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * @psalm-import-type OperatorExpression from Expr
 * @psalm-type ReplaceRootStageExpression = array{
 *     '$replaceRoot': array{
 *         newRoot: OperatorExpression|string,
 *     }
 * }
 */
class ReplaceRoot extends AbstractReplace
{
    /** @return ReplaceRootStageExpression */
    public function getExpression(): array
    {
        return [
            '$replaceRoot' => [
                'newRoot' => $this->getReplaceExpression(),
            ],
        ];
    }
}
