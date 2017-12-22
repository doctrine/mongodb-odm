<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $skip stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
 */
class Skip extends Stage
{
    /**
     * @var integer
     */
    private $skip;

    /**
     * @param Builder $builder
     * @param integer $skip
     */
    public function __construct(Builder $builder, $skip)
    {
        parent::__construct($builder);

        $this->skip = (integer) $skip;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$skip' => $this->skip
        ];
    }
}
