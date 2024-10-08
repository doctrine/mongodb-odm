<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $skip stage to an aggregation pipeline.
 *
 * @phpstan-type SkipStageExpression array{'$skip': int}
 */
class Skip extends Stage
{
    public function __construct(Builder $builder, private int $skip)
    {
        parent::__construct($builder);
    }

    /** @return SkipStageExpression */
    public function getExpression(): array
    {
        return [
            '$skip' => $this->skip,
        ];
    }
}
