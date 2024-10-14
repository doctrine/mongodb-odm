<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $sample stage to an aggregation pipeline.
 *
 * @phpstan-type SampleStageExpression array{'$sample': array{size: int}}
 */
class Sample extends Stage
{
    public function __construct(Builder $builder, private int $size)
    {
        parent::__construct($builder);
    }

    /** @phpstan-return SampleStageExpression */
    public function getExpression(): array
    {
        return [
            '$sample' => ['size' => $this->size],
        ];
    }
}
