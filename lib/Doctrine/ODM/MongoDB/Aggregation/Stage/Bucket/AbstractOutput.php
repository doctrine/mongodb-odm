<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Abstract class with common functionality for output objects in bucket stages
 *
 * @internal
 */
abstract class AbstractOutput extends Stage
{
    /** @var Stage\AbstractBucket */
    protected $bucket;

    /** @var Expr */
    private $expr;

    public function __construct(Builder $builder, Stage\AbstractBucket $bucket)
    {
        parent::__construct($builder);

        $this->bucket = $bucket;
        $this->expr   = $builder->expr();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return $this->expr->getExpression();
    }

    /**
     * Returns an array of all unique values that results from applying an
     * expression to each document in a group of documents that share the same
     * group by key. Order of the elements in the output array is unspecified.
     *
     * AddToSet is an accumulator operation only available in the group stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/addToSet/
     * @see Expr::addToSet
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function addToSet($expression)
    {
        $this->expr->addToSet($expression);

        return $this;
    }

    /**
     * Returns the average value of the numeric values that result from applying
     * a specified expression to each document in a group of documents that
     * share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     * @see Expr::avg
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function avg($expression)
    {
        $this->expr->avg($expression);

        return $this;
    }

    /**
     * Used to use an expression as field value. Can be any expression.
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * @see Expr::expression
     *
     * @param mixed|Expr $value
     *
     * @return $this
     */
    public function expression($value)
    {
        $this->expr->expression($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field
     *
     * @param string $fieldName
     *
     * @return $this
     */
    public function field($fieldName)
    {
        $this->expr->field($fieldName);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     * @see Expr::first
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function first($expression)
    {
        $this->expr->first($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     * @see Expr::last
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function last($expression)
    {
        $this->expr->last($expression);

        return $this;
    }

    /**
     * Returns the highest value that results from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     * @see Expr::max
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function max($expression)
    {
        $this->expr->max($expression);

        return $this;
    }

    /**
     * Returns the lowest value that results from applying an expression to each
     * document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     * @see Expr::min
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function min($expression)
    {
        $this->expr->min($expression);

        return $this;
    }

    /**
     * Returns an array of all values that result from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     * @see Expr::push
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function push($expression)
    {
        $this->expr->push($expression);

        return $this;
    }

    /**
     * Calculates the population standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     * @see Expr::stdDevPop
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function stdDevPop($expression)
    {
        $this->expr->stdDevPop($expression);

        return $this;
    }

    /**
     * Calculates the sample standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     * @see Expr::stdDevSamp
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function stdDevSamp($expression)
    {
        $this->expr->stdDevSamp($expression);

        return $this;
    }

    /**
     * Calculates and returns the sum of all the numeric values that result from
     * applying a specified expression to each document in a group of documents
     * that share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     * @see Expr::sum
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function sum($expression)
    {
        $this->expr->sum($expression);

        return $this;
    }
}
