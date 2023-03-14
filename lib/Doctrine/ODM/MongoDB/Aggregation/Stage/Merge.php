<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\MappingException;
use InvalidArgumentException;

use function array_values;
use function count;
use function is_array;

/**
 * @psalm-type OutputCollection = string|array{db: string, coll: string}
 * @psalm-type WhenMatchedParam = Builder|Stage|string|list<array<string, mixed>>
 */
class Merge extends Stage
{
    private DocumentManager $dm;

    /**
     * @var string|array
     * @psalm-var OutputCollection
     */
    private $into;

    /** @var list<string> */
    private array $on = [];

    /** @var array<string, string> */
    private array $let = [];

    /**
     * @var string|array|Builder
     * @psalm-var WhenMatchedParam
     */
    private $whenMatched;

    private ?string $whenNotMatched = null;

    public function __construct(Builder $builder, DocumentManager $documentManager)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
    }

    public function getExpression(): array
    {
        $params = (object) [
            'into' => $this->into,
        ];

        if ($this->on) {
            $params->on = count($this->on) === 1 ? $this->on[0] : $this->on;
        }

        if ($this->let) {
            $params->let = $this->let;
        }

        if ($this->whenMatched) {
            $params->whenMatched = $this->whenMatched instanceof Builder
                ? $this->whenMatched->getPipeline(false)
                : $this->whenMatched;
        }

        if ($this->whenNotMatched) {
            $params->whenNotMatched = $this->whenNotMatched;
        }

        return ['$merge' => $params];
    }

    /**
     * @param string|array $collection
     * @psalm-param OutputCollection $collection
     */
    public function into($collection): self
    {
        if (is_array($collection)) {
            $this->into = $collection;

            return $this;
        }

        try {
            $class      = $this->dm->getClassMetadata($collection);
            $this->into = $class->getCollection();
        } catch (MappingException $e) {
            $this->into = $collection;
        }

        return $this;
    }

    /**
     * Optional. Specifies variables to use in the pipeline stages.
     *
     * Use the variable expressions to access the fields from
     * the joined collection's documents that are input to the pipeline.
     *
     * @param array<string, string> $let
     */
    public function let(array $let): self
    {
        $this->let = $let;

        return $this;
    }

    public function on(string ...$fields): self
    {
        $this->on = array_values($fields);

        return $this;
    }

    /**
     * @param string|array|Builder|Stage $whenMatched
     * @psalm-param WhenMatchedParam $whenMatched
     */
    public function whenMatched($whenMatched): self
    {
        if ($whenMatched instanceof Stage) {
            $this->whenMatched = $whenMatched->builder;
        } else {
            $this->whenMatched = $whenMatched;
        }

        if ($this->builder === $this->whenMatched) {
            throw new InvalidArgumentException('Cannot use the same Builder instance for $merge whenMatched pipeline.');
        }

        return $this;
    }

    public function whenNotMatched(string $whenNotMatched): self
    {
        $this->whenNotMatched = $whenNotMatched;

        return $this;
    }
}
