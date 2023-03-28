<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all accumulator aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface AccumulatorOperators
{
    /**
     * Returns the average value of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function avg($expression, ...$expressions): static;

    /**
     * Returns the maximum value of numeric values.
     *
     * $max compares both value and type, using the BSON comparison order for
     * values of different types.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function max($expression, ...$expressions): static;

    /**
     * Returns the minimum value of numeric values.
     *
     * $min compares both value and type, using the BSON comparison order for
     * values of different types.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function min($expression, ...$expressions): static;

    /**
     * Calculates the population standard deviation of the input values. Use if
     * the values encompass the entire population of data you want to represent
     * and do not wish to generalize about a larger population. $stdDevPop
     * ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevPop($expression, ...$expressions): static;

    /**
     * Calculates the sample standard deviation of the input values. Use if the
     * values encompass a sample of a population of data from which to
     * generalize about the population. $stdDevSamp ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevSamp($expression, ...$expressions): static;

    /**
     * Calculates the collective sum of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function sum($expression, ...$expressions): static;
}
