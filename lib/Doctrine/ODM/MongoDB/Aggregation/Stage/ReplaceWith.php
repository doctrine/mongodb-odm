<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type ReplaceWithStageExpression array{'$replaceWith': OperatorExpression|string}
 */
class ReplaceWith extends AbstractReplace
{
    /** @return ReplaceWithStageExpression */
    public function getExpression(): array
    {
        return ['$replaceWith' => $this->getReplaceExpression()];
    }
}
