<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all conditional aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface ConditionalOperators
{
    /**
     * Adds a case statement for a branch of the $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression that resolves to a boolean. If the result is not a
     * boolean, it is coerced to a boolean value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/switch/
     *
     * @param mixed|Expr $expression
     */
    public function case($expression): static;

    /**
     * Evaluates a boolean expression to return one of the two specified return
     * expressions.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cond/
     *
     * @param mixed|Expr $if
     * @param mixed|Expr $then
     * @param mixed|Expr $else
     */
    public function cond($if, $then, $else): static;

    /**
     * Adds a default statement for the current $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression.
     *
     * Note: if no default is specified and no branch evaluates to true, the
     * $switch operator throws an error.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/switch/
     *
     * @param mixed|Expr $expression
     */
    public function default($expression): static;

    /**
     * Evaluates an expression and returns the value of the expression if the
     * expression evaluates to a non-null value. If the expression evaluates to
     * a null value, including instances of undefined values or missing fields,
     * returns the value of the replacement expression.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ifNull/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $replacementExpression
     */
    public function ifNull($expression, $replacementExpression): static;

    /**
     * Evaluates a series of case expressions. When it finds an expression which
     * evaluates to true, $switch executes a specified expression and breaks out
     * of the control flow.
     *
     * To add statements, use the {@link case()}, {@link then()} and
     * {@link default()} methods.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/switch/
     */
    public function switch(): static;

    /**
     * Adds a case statement for the current branch of the $switch operator.
     *
     * Requires {@link case()} to be called first. The argument can be any valid
     * expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/switch/
     *
     * @param mixed|Expr $expression
     */
    public function then($expression): static;
}
