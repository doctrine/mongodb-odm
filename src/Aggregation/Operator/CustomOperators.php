<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use MongoDB\BSON\Javascript;

/**
 * Interface containing all aggregation pipeline operators to define custom
 * operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface CustomOperators
{
    /**
     * Defines a custom accumulator operator.
     *
     * Accumulators are operators that maintain their state (e.g. totals,
     * maximums, minimums, and related data) as documents progress through the
     * pipeline. Use the $accumulator operator to execute your own JavaScript
     * functions to implement behavior not supported by the MongoDB Query
     * Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/accumulator/
     */
    public function accumulator(string|Javascript $init, string|Javascript $accumulate, mixed $accumulateArgs, string|Javascript $merge, mixed $initArgs = null, string|Javascript|null $finalize = null, string $lang = 'js'): static;

    /**
     * Defines a custom aggregation function or expression in JavaScript.
     *
     * You can use the $function operator to define custom functions to
     * implement behavior not supported by the MongoDB Query Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/function/
     */
    public function function(string|Javascript $body, mixed $args, string $lang = 'js'): static;
}
