<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $facet stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class Facet extends Stage
{
    /**
     * @var Builder[]
     */
    private $pipelines = [];

    /**
     * @var string
     */
    private $field;

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$facet' => array_map(function (Builder $builder) { return $builder->getPipeline(); }, $this->pipelines),
        ];
    }

    /**
     * Set the current field for building the pipeline stage.
     *
     * @param string $field
     *
     * @return $this
     */
    public function field($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Use the given pipeline for the current field.
     *
     * @param Builder|Stage $builder
     * @return $this
     */
    public function pipeline($builder)
    {
        if (! $this->field) {
            throw new \LogicException(__METHOD__ . ' requires you set a current field using field().');
        }

        if ($builder instanceof Stage) {
            $builder = $builder->builder;
        }

        if (! $builder instanceof Builder) {
            throw new \InvalidArgumentException(__METHOD__ . ' expects either an aggregation builder or an aggregation stage.');
        }

        $this->pipelines[$this->field] = $builder;
        return $this;
    }
}
