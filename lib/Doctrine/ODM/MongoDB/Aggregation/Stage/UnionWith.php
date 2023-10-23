<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\MappingException;
use InvalidArgumentException;

/**
 * Fluent interface for adding a $unionWith stage to an aggregation pipeline.
 *
 * @psalm-import-type PipelineExpression from Builder
 * @psalm-type PipelineParamType = array|Builder|Stage|PipelineExpression
 * @psalm-type UnionWithStageExpression = array{
 *     '$unionWith': object{
 *         coll: string,
 *         pipeline?: PipelineExpression,
 *     }
 * }
 */
class UnionWith extends Stage
{
    /**
     * @var array|Builder|null
     * @psalm-var ?PipelineParamType
     */
    private $pipeline = null;

    public function __construct(Builder $builder, private DocumentManager $dm, private string $collection)
    {
        parent::__construct($builder);

        try {
            $class            = $this->dm->getClassMetadata($collection);
            $this->collection = $class->getCollection();
        } catch (MappingException) {
        }
    }

    /**
     * @param array|Builder|Stage $pipeline
     * @psalm-param PipelineParamType $pipeline
     */
    public function pipeline($pipeline): static
    {
        if ($pipeline instanceof Stage) {
            $this->pipeline = $pipeline->builder;
        } else {
            $this->pipeline = $pipeline;
        }

        if ($this->builder === $this->pipeline) {
            throw new InvalidArgumentException('Cannot use the same Builder instance for $unionWith pipeline.');
        }

        return $this;
    }

    /** @psalm-return UnionWithStageExpression */
    public function getExpression(): array
    {
        $params = (object) ['coll' => $this->collection];

        if ($this->pipeline) {
            $params->pipeline = $this->pipeline instanceof Builder
                ? $this->pipeline->getPipeline(false)
                : $this->pipeline;
        }

        return ['$unionWith' => $params];
    }
}
