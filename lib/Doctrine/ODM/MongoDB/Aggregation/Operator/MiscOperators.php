<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing miscellaneous aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface MiscOperators
{
    /**
     * Allows any expression to be used as a field value.
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     *
     * @param mixed|Expr $value
     *
     * @return static
     */
    public function expression($value);

    /**
     * Binds variables for use in the specified expression, and returns the
     * result of the expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/let/
     *
     * @param mixed|Expr $vars Assignment block for the variables accessible in the in expression. To assign a variable, specify a string for the variable name and assign a valid expression for the value.
     * @param mixed|Expr $in   the expression to evaluate
     *
     * @return static
     */
    public function let($vars, $in): self;

    /**
     * Returns a value without parsing. Use for values that the aggregation
     * pipeline may interpret as an expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/literal/
     *
     * @param mixed|Expr $value
     *
     * @return static
     */
    public function literal($value): self;

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/meta/
     *
     * @param mixed|Expr $metaDataKeyword
     *
     * @return static
     */
    public function meta($metaDataKeyword): self;

    /**
     * Returns a random float between 0 and 1 each time it is called.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rand/
     *
     * @return static
     */
    public function rand(): self;

    /**
     * Matches a random selection of input documents. The number of documents
     * selected approximates the sample rate expressed as a percentage of the
     * total number of documents.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sampleRate/
     *
     * @return static
     */
    public function sampleRate(float $rate): self;
}
