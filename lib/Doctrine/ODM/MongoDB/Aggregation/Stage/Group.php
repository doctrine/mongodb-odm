<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $group stage to an aggregation pipeline.
 */
class Group extends Operator
{
    /** @var Expr */
    protected $expr;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->expr = $builder->expr();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return [
            '$group' => $this->expr->getExpression(),
        ];
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
    public function addToSet($expression): self
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
     */
    public function avg($expression): self
    {
        $this->expr->avg($expression);

        return $this;
    }

    /**
     * Used to use an expression as field value. Can be any expression
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * @see Expr::expression
     *
     * @param mixed|Expr $value
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
     */
    public function field(string $fieldName)
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
     */
    public function first($expression): self
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
     */
    public function last($expression): self
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
     */
    public function max($expression): self
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
     */
    public function min($expression): self
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
     */
    public function push($expression): self
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
     */
    public function stdDevPop($expression): self
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
     */
    public function stdDevSamp($expression): self
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
     */
    public function sum($expression): self
    {
        $this->expr->sum($expression);

        return $this;
    }
}
