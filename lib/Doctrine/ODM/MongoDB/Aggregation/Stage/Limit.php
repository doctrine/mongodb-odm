<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $limit stage to an aggregation pipeline.
 */
class Limit extends Stage
{
    private int $limit;

    public function __construct(Builder $builder, int $limit)
    {
        parent::__construct($builder);

        $this->limit = $limit;
    }

    public function getExpression(): array
    {
        return [
            '$limit' => $this->limit,
        ];
    }
}
