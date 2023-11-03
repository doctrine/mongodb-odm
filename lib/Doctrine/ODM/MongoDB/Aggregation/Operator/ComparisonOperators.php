<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all comparison aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface ComparisonOperators
{
    /**
     * Compares two values and returns:
     * -1 if the first value is less than the second.
     * 1 if the first value is greater than the second.
     * 0 if the two values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cmp/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function cmp($expression1, $expression2): static;

    /**
     * Compares two values and returns whether the are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/eq/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function eq($expression1, $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gt/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gt($expression1, $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second
     * value.
     * false when the first value is less than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gte/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gte($expression1, $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lt/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lt($expression1, $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lte/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lte($expression1, $expression2): static;

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ne/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function ne($expression1, $expression2): static;
}
