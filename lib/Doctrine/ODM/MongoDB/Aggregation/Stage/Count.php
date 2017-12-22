<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $count stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class Count extends Stage
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @param Builder $builder
     * @param string $fieldName
     */
    public function __construct(Builder $builder, $fieldName)
    {
        parent::__construct($builder);

        $this->fieldName = $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$count' => $this->fieldName
        ];
    }
}
