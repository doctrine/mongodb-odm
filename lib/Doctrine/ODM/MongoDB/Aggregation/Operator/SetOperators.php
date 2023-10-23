<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all set aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface SetOperators
{
    /**
     * Evaluates an array as a set and returns true if no element in the array
     * is false. Otherwise, returns false. An empty array returns true.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/allElementsTrue/
     *
     * @param mixed|Expr $expression
     */
    public function allElementsTrue($expression): static;

    /**
     * Evaluates an array as a set and returns true if any of the elements are
     * true and false otherwise. An empty array returns false.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/anyElementTrue/
     *
     * @param mixed[]|Expr $expression
     */
    public function anyElementTrue($expression): static;

    /**
     * Takes two sets and returns an array containing the elements that only
     * exist in the first set.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setDifference/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setDifference($expression1, $expression2): static;

    /**
     * Compares two or more arrays and returns true if they have the same
     * distinct elements and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setEquals/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setEquals($expression1, $expression2, ...$expressions): static;

    /**
     * Takes two or more arrays and returns an array that contains the elements
     * that appear in every input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIntersection/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setIntersection($expression1, $expression2, ...$expressions): static;

    /**
     * Takes two arrays and returns true when the first array is a subset of the
     * second, including when the first array equals the second array, and false
     * otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIsSubset/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setIsSubset($expression1, $expression2): static;

    /**
     * Takes two or more arrays and returns an array containing the elements
     * that appear in any input array.
     *
     * The arguments can be any valid expression as long as they each resolve to
     * an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setUnion/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setUnion($expression1, $expression2, ...$expressions): static;
}
