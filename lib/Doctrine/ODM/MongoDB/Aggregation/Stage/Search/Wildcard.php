<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/** @internal */
class Wildcard extends Regex
{
    public function getOperatorName(): string
    {
        return 'wildcard';
    }
}
