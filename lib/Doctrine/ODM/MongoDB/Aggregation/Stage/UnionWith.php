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
 * @psalm-type Pipeline = Builder|Stage|list<array<string, mixed>>
 */
class UnionWith extends Stage
{
    private DocumentManager $dm;

    private string $collection;

    /**
     * @var array|Builder|null
     * @psalm-var ?Pipeline
     */
    private $pipeline = null;

    public function __construct(Builder $builder, DocumentManager $documentManager, string $collection)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;

        try {
            $class            = $this->dm->getClassMetadata($collection);
            $this->collection = $class->getCollection();
        } catch (MappingException $e) {
            $this->collection = $collection;
        }
    }

    /**
     * @param array|Builder|Stage $pipeline
     * @psalm-param Pipeline $pipeline
     */
    public function pipeline($pipeline): self
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
