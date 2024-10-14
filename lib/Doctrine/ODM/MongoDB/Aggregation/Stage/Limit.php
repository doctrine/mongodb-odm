<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $limit stage to an aggregation pipeline.
 *
 * @phpstan-type LimitStageExpression array{'$limit': int}
 */
class Limit extends Stage
{
    public function __construct(Builder $builder, private int $limit)
    {
        parent::__construct($builder);
    }

    /** @phpstan-return LimitStageExpression */
    public function getExpression(): array
    {
        return [
            '$limit' => $this->limit,
        ];
    }
}
