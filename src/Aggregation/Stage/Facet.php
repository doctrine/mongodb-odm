<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use LogicException;

use function array_map;

/**
 * Fluent interface for adding a $facet stage to an aggregation pipeline.
 *
 * @phpstan-import-type PipelineExpression from Builder
 * @phpstan-type FacetStageExpression array{"$facet": array<string, PipelineExpression>}
 */
class Facet extends Stage
{
    /** @var Builder[] */
    private array $pipelines = [];

    private string $field;

    public function getExpression(): array
    {
        return [
            '$facet' => array_map(static fn (Builder $builder) => $builder->getPipeline(false), $this->pipelines),
        ];
    }

    /**
     * Set the current field for building the pipeline stage.
     */
    public function field(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Use the given pipeline for the current field.
     */
    public function pipeline(Builder|Stage $builder): static
    {
        if (! isset($this->field)) {
            throw new LogicException(__METHOD__ . ' requires setting a current field using field().');
        }

        if ($builder instanceof Stage) {
            $builder = $builder->builder;
        }

        $this->pipelines[$this->field] = $builder;

        return $this;
    }
}
