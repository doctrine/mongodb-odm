<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

/**
 * Interface containing all trigonometry aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface TrigonometryOperators
{
    /**
     * Returns the inverse cosine (arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acos/
     */
    public function acos(mixed $expression): static;

    /**
     * Returns the inverse hyperbolic cosine (hyperbolic arc cosine) of a value
     * in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acosh/
     */
    public function acosh(mixed $expression): static;

    /**
     * Returns the inverse sin (arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asin/
     */
    public function asin(mixed $expression): static;

    /**
     * Returns the inverse hyperbolic sine (hyperbolic arc sine) of a value in
     * radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asinh/
     */
    public function asinh(mixed $expression): static;

    /**
     * Returns the inverse tangent (arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan/
     */
    public function atan(mixed $expression): static;

    /**
     * Returns the inverse tangent (arc tangent) of y / x in radians, where y
     * and x are the first and second values passed to the expression respectively.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan2/
     */
    public function atan2(mixed $expression1, mixed $expression2): static;

    /**
     * Returns the inverse hyperbolic tangent (hyperbolic arc tangent) of a
     * value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atanh/
     */
    public function atanh(mixed $expression): static;

    /**
     * Returns the cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cos/
     */
    public function cos(mixed $expression): static;

    /**
     * Returns the hyperbolic cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cosh/
     */
    public function cosh(mixed $expression): static;

    /**
     * Converts a value from degrees to radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/degreesToRadians/
     */
    public function degreesToRadians(mixed $expression): static;

    /**
     * Converts a value from radians to degrees.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/radiansToDegrees/
     */
    public function radiansToDegrees(mixed $expression): static;

    /**
     * Returns the sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sin/
     */
    public function sin(mixed $expression): static;

    /**
     * Returns the hyperbolic sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sinh/
     */
    public function sinh(mixed $expression): static;

    /**
     * Returns the tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tan/
     */
    public function tan(mixed $expression): static;

    /**
     * Returns the hyperbolic tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tanh/
     */
    public function tanh(mixed $expression): static;
}
