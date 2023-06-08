<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage;

class ShardedDataDistribution extends Stage
{
    public function getExpression(): array
    {
        return ['$shardedDataDistribution' => []];
    }
}
