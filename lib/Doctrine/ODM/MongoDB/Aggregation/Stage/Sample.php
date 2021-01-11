<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $sample stage to an aggregation pipeline.
 */
class Sample extends Stage
{
    /** @var int */
    private $size;

    public function __construct(Builder $builder, int $size)
    {
        parent::__construct($builder);

        $this->size = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return [
            '$sample' => ['size' => $this->size],
        ];
    }
}
