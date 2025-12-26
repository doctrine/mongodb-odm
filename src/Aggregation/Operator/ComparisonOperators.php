<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

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
     */
    public function cmp(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns whether the are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/eq/
     */
    public function eq(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gt/
     */
    public function gt(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second
     * value.
     * false when the first value is less than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gte/
     */
    public function gte(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lt/
     */
    public function lt(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lte/
     */
    public function lte(mixed $expression1, mixed $expression2): static;

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ne/
     */
    public function ne(mixed $expression1, mixed $expression2): static;
}
