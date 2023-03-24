<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
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
     *
     * @param string|Javascript      $init
     * @param string|Javascript      $accumulate
     * @param mixed|Expr             $accumulateArgs
     * @param string|Javascript      $merge
     * @param mixed|Expr|null        $initArgs
     * @param string|Javascript|null $finalize
     * @param string                 $lang
     */
    public function accumulator($init, $accumulate, $accumulateArgs, $merge, $initArgs = null, $finalize = null, $lang = 'js'): static;

    /**
     * Defines a custom aggregation function or expression in JavaScript.
     *
     * You can use the $function operator to define custom functions to
     * implement behavior not supported by the MongoDB Query Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/function/
     *
     * @param string|Javascript $body
     * @param mixed|Expr        $args
     * @param string            $lang
     */
    public function function($body, $args, $lang = 'js'): static;
}
