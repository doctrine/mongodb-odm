<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage;
use stdClass;

/**
 * Fluent interface for adding a $indexStats stage to an aggregation pipeline.
 *
 * @psalm-type IndexStatsStageExpression = array{'$indexStats': object}
 */
class IndexStats extends Stage
{
    /** @psalm-return IndexStatsStageExpression */
    public function getExpression(): array
    {
        return [
            '$indexStats' => new stdClass(),
        ];
    }
}
