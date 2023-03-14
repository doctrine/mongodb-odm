<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

class ReplaceWith extends ReplaceRoot
{
    public function getExpression(): array
    {
        $expression = parent::getExpression();

        return [
            '$replaceWith' => $expression['$replaceRoot']['newRoot'],
        ];
    }
}
