<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\MappingException;
use InvalidArgumentException;

use function array_values;
use function count;
use function is_array;

/**
 * @psalm-import-type PipelineExpression from Builder
 * @psalm-type OutputCollection = string|array{db: string, coll: string}
 * @psalm-type WhenMatchedType = 'replace'|'keepExisting'|'merge'|'fail'|PipelineExpression
 * @psalm-type WhenMatchedParamType = Builder|Stage|WhenMatchedType
 * @psalm-type WhenNotMatchedType = 'insert'|'discard'|'fail'
 * @psalm-type MergeStageExpression = array{
 *     '$merge': object{
 *         into: OutputCollection,
 *         on?: string|list<string>,
 *         let?: array<string, mixed|Expr>,
 *         whenMatched?: WhenMatchedType,
 *         whenNotMatched?: WhenNotMatchedType,
 *     }
 * }
 */
class Merge extends Stage
{
    /**
     * @var string|array
     * @psalm-var OutputCollection
     */
    private $into;

    /** @var list<string> */
    private array $on = [];

    /** @var array<string, mixed|Expr> */
    private array $let = [];

    /**
     * @var string|array|Builder|Stage
     * @psalm-var WhenMatchedParamType
     */
    private $whenMatched;

    private ?string $whenNotMatched = null;

    public function __construct(Builder $builder, private DocumentManager $dm)
    {
        parent::__construct($builder);
    }

    /** @psalm-return MergeStageExpression */
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
    public function into($collection): static
    {
        if (is_array($collection)) {
            $this->into = $collection;

            return $this;
        }

        try {
            $class      = $this->dm->getClassMetadata($collection);
            $this->into = $class->getCollection();
        } catch (MappingException) {
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
     * @param array<string, mixed|Expr> $let
     */
    public function let(array $let): static
    {
        $this->let = $let;

        return $this;
    }

    public function on(string ...$fields): static
    {
        $this->on = array_values($fields);

        return $this;
    }

    /**
     * @param string|array|Builder|Stage $whenMatched
     * @psalm-param WhenMatchedParamType $whenMatched
     */
    public function whenMatched($whenMatched): static
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

    public function whenNotMatched(string $whenNotMatched): static
    {
        $this->whenNotMatched = $whenNotMatched;

        return $this;
    }
}
