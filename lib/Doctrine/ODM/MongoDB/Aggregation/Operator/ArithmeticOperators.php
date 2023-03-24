<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all arithmetic aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface ArithmeticOperators
{
    /**
     * Returns the absolute value of a number.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/abs/
     *
     * @param mixed|Expr $number
     */
    public function abs($number): static;

    /**
     * Adds numbers together or adds numbers and a date. If one of the arguments
     * is a date, $add treats the other arguments as milliseconds to add to the
     * date.
     *
     * The arguments can be any valid expression as long as they resolve to
     * either all numbers or to numbers and a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/add/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function add($expression1, $expression2, ...$expressions): static;

    /**
     * Returns the smallest integer greater than or equal to the specified
     * number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ceil/
     *
     * @param mixed|Expr $number
     */
    public function ceil($number): static;

    /**
     * Divides one number by another and returns the result. The first argument
     * is divided by the second argument.
     *
     * The arguments can be any valid expression as long as the resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/divide/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function divide($expression1, $expression2): static;

    /**
     * Raises Euler’s number to the specified exponent and returns the result.
     *
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/exp/
     *
     * @param mixed|Expr $exponent
     */
    public function exp($exponent): static;

    /**
     * Returns the largest integer less than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/floor/
     *
     * @param mixed|Expr $number
     */
    public function floor($number): static;

    /**
     * Calculates the natural logarithm ln (i.e loge) of a number and returns
     * the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|Expr $number
     */
    public function ln($number): static;

    /**
     * Calculates the log of a number in the specified base and returns the
     * result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <base> expression can be any valid expression as long as it resolves
     * to a positive number greater than 1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $base
     */
    public function log($number, $base): static;

    /**
     * Calculates the log base 10 of a number and returns the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log10/
     *
     * @param mixed|Expr $number
     */
    public function log10($number): static;

    /**
     * Divides one number by another and returns the remainder. The first
     * argument is divided by the second argument.
     *
     * The arguments can be any valid expression as long as they resolve to
     * numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mod/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function mod($expression1, $expression2): static;

    /**
     * Multiplies numbers together and returns the result.
     *
     * The arguments can be any valid expression as long as they resolve to
     * numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/multiply/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function multiply($expression1, $expression2, ...$expressions): static;

    /**
     * Raises a number to the specified exponent and returns the result.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/pow/
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $exponent
     */
    public function pow($number, $exponent): static;

    /**
     * Rounds a number to a whole integer or to a specified decimal place.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/round/
     *
     * @param mixed|Expr      $number
     * @param mixed|Expr|null $place
     */
    public function round($number, $place = null): static;

    /**
     * Calculates the square root of a positive number and returns the result as
     * a double.
     *
     * The argument can be any valid expression as long as it resolves to a
     * non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sqrt/
     *
     * @param mixed|Expr $expression
     */
    public function sqrt($expression): static;

    /**
     * Subtracts two numbers to return the difference. The second argument is
     * subtracted from the first argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers and/or dates.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/subtract/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function subtract($expression1, $expression2): static;

    /**
     * Truncates a number to its integer.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trunc/
     *
     * @param mixed|Expr $number
     */
    public function trunc($number): static;
}
