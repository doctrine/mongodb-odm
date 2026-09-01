<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

/**
 * Interface containing all type aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface TypeOperators
{
    /**
     * Converts a value to a specified type.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/convert/
     */
    public function convert(mixed $input, mixed $to, mixed $onError = null, mixed $onNull = null): static;

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     */
    public function isArray(mixed $expression): static;

    /**
     * Returns boolean true if the specified expression resolves to an integer,
     * decimal, double, or long. Returns boolean false if the expression
     * resolves to any other BSON type, null, or a missing field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isNumber/
     */
    public function isNumber(mixed $expression): static;

    /**
     * Converts value to a boolean.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toBool/
     */
    public function toBool(mixed $expression): static;

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     */
    public function toDate(mixed $expression): static;

    /**
     * Converts value to a Decimal128.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDecimal/
     */
    public function toDecimal(mixed $expression): static;

    /**
     * Converts value to a double.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDouble/
     */
    public function toDouble(mixed $expression): static;

    /**
     * Converts value to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toInt/
     */
    public function toInt(mixed $expression): static;

    /**
     * Converts value to a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLong/
     */
    public function toLong(mixed $expression): static;

    /**
     * Converts value to an ObjectId.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toObjectId/
     */
    public function toObjectId(mixed $expression): static;

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     */
    public function toString(mixed $expression): static;

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/type/
     */
    public function type(mixed $expression): static;
}
