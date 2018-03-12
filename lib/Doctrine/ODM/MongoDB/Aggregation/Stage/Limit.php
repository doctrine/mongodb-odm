<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $limit stage to an aggregation pipeline.
 *
 */
class Limit extends Stage
{
    /** @var int */
    private $limit;

    /**
     * @param int $limit
     */
    public function __construct(Builder $builder, $limit)
    {
        parent::__construct($builder);

        $this->limit = (int) $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$limit' => $this->limit,
        ];
    }
}
