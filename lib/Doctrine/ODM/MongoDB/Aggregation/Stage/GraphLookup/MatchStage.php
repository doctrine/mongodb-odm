<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage as BaseMatchStage;

class MatchStage extends BaseMatchStage
{
    public function __construct(Builder $builder, private GraphLookup $graphLookup)
    {
        parent::__construct($builder);
    }

    public function getExpression(): array
    {
        return $this->query->getQuery();
    }

    /**
     * Target collection for the $graphLookup operation to search, recursively
     * matching the connectFromField to the connectToField.
     *
     * The from collection cannot be sharded and must be in the same database as
     * any other collections used in the operation.
     */
    public function from(string $from): GraphLookup
    {
        return $this->graphLookup->from($from);
    }

    /**
     * Expression that specifies the value of the connectFromField with which to
     * start the recursive search.
     *
     * Optionally, startWith may be array of values, each of which is
     * individually followed through the traversal process.
     *
     * @param string|mixed[]|Expr $expression
     */
    public function startWith($expression): GraphLookup
    {
        return $this->graphLookup->startWith($expression);
    }

    /**
     * Field name whose value $graphLookup uses to recursively match against the
     * connectToField of other documents in the collection.
     *
     * Optionally, connectFromField may be an array of field names, each of
     * which is individually followed through the traversal process.
     */
    public function connectFromField(string $connectFromField): GraphLookup
    {
        return $this->graphLookup->connectFromField($connectFromField);
    }

    /**
     * Field name in other documents against which to match the value of the
     * field specified by the connectFromField parameter.
     */
    public function connectToField(string $connectToField): GraphLookup
    {
        return $this->graphLookup->connectToField($connectToField);
    }

    /**
     * Name of the array field added to each output document.
     *
     * Contains the documents traversed in the $graphLookup stage to reach the
     * document.
     */
    public function alias(string $alias): GraphLookup
    {
        return $this->graphLookup->alias($alias);
    }

    /**
     * Non-negative integral number specifying the maximum recursion depth.
     */
    public function maxDepth(int $maxDepth): GraphLookup
    {
        return $this->graphLookup->maxDepth($maxDepth);
    }

    /**
     * Name of the field to add to each traversed document in the search path.
     *
     * The value of this field is the recursion depth for the document,
     * represented as a NumberLong. Recursion depth value starts at zero, so the
     * first lookup corresponds to zero depth.
     */
    public function depthField(string $depthField): GraphLookup
    {
        return $this->graphLookup->depthField($depthField);
    }
}
