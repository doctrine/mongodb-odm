<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $sample stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.3
 */
class Sample extends Stage
{
    /**
     * @var integer
     */
    private $size;

    /**
     * @param Builder $builder
     * @param integer $size
     */
    public function __construct(Builder $builder, $size)
    {
        parent::__construct($builder);

        $this->size = (integer) $size;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$sample' => ['size' => $this->size]
        ];
    }
}
