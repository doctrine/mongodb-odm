<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/wildcard/
 */
class Wildcard extends Regex
{
    public function getOperatorName(): string
    {
        return 'wildcard';
    }
}
