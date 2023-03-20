<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

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
     *
     * @param mixed|Expr $expression
     */
    public function acos($expression): self;

    /**
     * Returns the inverse hyperbolic cosine (hyperbolic arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acosh/
     *
     * @param mixed|Expr $expression
     */
    public function acosh($expression): self;

    /**
     * Returns the inverse sin (arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asin/
     *
     * @param mixed|Expr $expression
     */
    public function asin($expression): self;

    /**
     * Returns the inverse hyperbolic sine (hyperbolic arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asinh/
     *
     * @param mixed|Expr $expression
     */
    public function asinh($expression): self;

    /**
     * Returns the inverse tangent (arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan/
     *
     * @param mixed|Expr $expression
     */
    public function atan($expression): self;

    /**
     * Returns the inverse tangent (arc tangent) of y / x in radians, where y and x are the first and second values passed to the expression respectively.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan2/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function atan2($expression1, $expression2): self;

    /**
     * Returns the inverse hyperbolic tangent (hyperbolic arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atanh/
     *
     * @param mixed|Expr $expression
     */
    public function atanh($expression): self;

    /**
     * Returns the cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cos/
     *
     * @param mixed|Expr $expression
     */
    public function cos($expression): self;

    /**
     * Returns the hyperbolic cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cosh/
     *
     * @param mixed|Expr $expression
     */
    public function cosh($expression): self;

    /**
     * Converts a value from degrees to radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/degreesToRadians/
     *
     * @param mixed|Expr $expression
     */
    public function degreesToRadians($expression): self;

    /**
     * Converts a value from radians to degrees.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/radiansToDegrees/
     *
     * @param mixed|Expr $expression
     */
    public function radiansToDegrees($expression): self;

    /**
     * Returns the sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sin/
     *
     * @param mixed|Expr $expression
     */
    public function sin($expression): self;

    /**
     * Returns the hyperbolic sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sinh/
     *
     * @param mixed|Expr $expression
     */
    public function sinh($expression): self;

    /**
     * Returns the tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tan/
     *
     * @param mixed|Expr $expression
     */
    public function tan($expression): self;

    /**
     * Returns the hyperbolic tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tanh/
     *
     * @param mixed|Expr $expression
     */
    public function tanh($expression): self;
}
