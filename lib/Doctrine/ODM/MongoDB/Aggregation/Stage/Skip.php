<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $skip stage to an aggregation pipeline.
 *
 * @psalm-type SkipStageExpression = array{'$skip': int}
 */
class Skip extends Stage
{
    private int $skip;

    public function __construct(Builder $builder, int $skip)
    {
        parent::__construct($builder);

        $this->skip = $skip;
    }

    /** @return SkipStageExpression */
    public function getExpression(): array
    {
        return [
            '$skip' => $this->skip,
        ];
    }
}
