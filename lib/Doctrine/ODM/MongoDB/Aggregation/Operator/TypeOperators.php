<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

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
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr      $to
     * @param mixed|Expr|null $onError
     * @param mixed|Expr|null $onNull
     */
    public function convert($input, $to, $onError = null, $onNull = null): static;

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     *
     * @param mixed|Expr $expression
     */
    public function isArray($expression): static;

    /**
     * Returns boolean true if the specified expression resolves to an integer,
     * decimal, double, or long. Returns boolean false if the expression
     * resolves to any other BSON type, null, or a missing field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isNumber/
     *
     * @param mixed|Expr $expression
     */
    public function isNumber($expression): static;

    /**
     * Converts value to a boolean.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toBool/
     *
     * @param mixed|Expr $expression
     */
    public function toBool($expression): static;

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     *
     * @param mixed|Expr $expression
     */
    public function toDate($expression): static;

    /**
     * Converts value to a Decimal128.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDecimal/
     *
     * @param mixed|Expr $expression
     */
    public function toDecimal($expression): static;

    /**
     * Converts value to a double.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDouble/
     *
     * @param mixed|Expr $expression
     */
    public function toDouble($expression): static;

    /**
     * Converts value to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toInt/
     *
     * @param mixed|Expr $expression
     */
    public function toInt($expression): static;

    /**
     * Converts value to a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLong/
     *
     * @param mixed|Expr $expression
     */
    public function toLong($expression): static;

    /**
     * Converts value to an ObjectId.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toObjectId/
     *
     * @param mixed|Expr $expression
     */
    public function toObjectId($expression): static;

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     *
     * @param mixed|Expr $expression
     */
    public function toString($expression): static;

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/type/
     *
     * @param mixed|Expr $expression
     */
    public function type($expression): static;
}
