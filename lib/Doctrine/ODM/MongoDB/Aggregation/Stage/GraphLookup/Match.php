<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Match as BaseMatch;

class Match extends BaseMatch
{
    /**
     * @var GraphLookup
     */
    private $graphLookup;

    /**
     * @param Builder $builder
     * @param GraphLookup $graphLookup
     */
    public function __construct(Builder $builder, GraphLookup $graphLookup)
    {
        parent::__construct($builder);

        $this->graphLookup = $graphLookup;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return $this->query->getQuery() ?: (object) [];
    }

    /**
     * Target collection for the $graphLookup operation to search, recursively
     * matching the connectFromField to the connectToField.
     *
     * The from collection cannot be sharded and must be in the same database as
     * any other collections used in the operation.
     *
     * @param string $from
     *
     * @return GraphLookup
     */
    public function from($from)
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
     * @param string|array|Expr $expression
     *
     * @return GraphLookup
     */
    public function startWith($expression)
    {
        return $this->graphLookup->startWith($expression);
    }

    /**
     * Field name whose value $graphLookup uses to recursively match against the
     * connectToField of other documents in the collection.
     *
     * Optionally, connectFromField may be an array of field names, each of
     * which is individually followed through the traversal process.
     *
     * @param string $connectFromField
     *
     * @return GraphLookup
     */
    public function connectFromField($connectFromField)
    {
        return $this->graphLookup->connectFromField($connectFromField);
    }

    /**
     * Field name in other documents against which to match the value of the
     * field specified by the connectFromField parameter.
     *
     * @param string $connectToField
     *
     * @return GraphLookup
     */
    public function connectToField($connectToField)
    {
        return $this->graphLookup->connectToField($connectToField);
    }

    /**
     * Name of the array field added to each output document.
     *
     * Contains the documents traversed in the $graphLookup stage to reach the
     * document.
     *
     * @param string $alias
     *
     * @return GraphLookup
     */
    public function alias($alias)
    {
        return $this->graphLookup->alias($alias);
    }

    /**
     * Non-negative integral number specifying the maximum recursion depth.
     *
     * @param int $maxDepth
     *
     * @return GraphLookup
     */
    public function maxDepth($maxDepth)
    {
        return $this->graphLookup->maxDepth($maxDepth);
    }

    /**
     * Name of the field to add to each traversed document in the search path.
     *
     * The value of this field is the recursion depth for the document,
     * represented as a NumberLong. Recursion depth value starts at zero, so the
     * first lookup corresponds to zero depth.
     *
     * @param string $depthField
     *
     * @return GraphLookup
     */
    public function depthField($depthField)
    {
        return $this->graphLookup->depthField($depthField);
    }
}
