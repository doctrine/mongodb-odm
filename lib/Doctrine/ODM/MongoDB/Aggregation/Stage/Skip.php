<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $skip stage to an aggregation pipeline.
 *
 */
class Skip extends Stage
{
    /** @var int */
    private $skip;

    /**
     * @param int $skip
     */
    public function __construct(Builder $builder, $skip)
    {
        parent::__construct($builder);

        $this->skip = (int) $skip;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$skip' => $this->skip,
        ];
    }
}
