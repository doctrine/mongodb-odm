<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $skip stage to an aggregation pipeline.
 */
class Skip extends Stage
{
    /** @var int */
    private $skip;

    public function __construct(Builder $builder, int $skip)
    {
        parent::__construct($builder);

        $this->skip = $skip;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return [
            '$skip' => $this->skip,
        ];
    }
}
