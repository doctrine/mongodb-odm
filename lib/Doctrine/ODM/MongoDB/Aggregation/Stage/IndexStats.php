<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage;
use stdClass;

/**
 * Fluent interface for adding a $indexStats stage to an aggregation pipeline.
 *
 * @phpstan-type IndexStatsStageExpression array{'$indexStats': object}
 */
class IndexStats extends Stage
{
    /** @phpstan-return IndexStatsStageExpression */
    public function getExpression(): array
    {
        return [
            '$indexStats' => new stdClass(),
        ];
    }
}
