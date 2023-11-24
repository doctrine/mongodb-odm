<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all accumulator aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface GroupAccumulatorOperators extends CustomOperators
{
    /**
     * Returns an array of unique expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/addToSet/
     *
     * @param mixed|Expr $expression
     */
    public function addToSet($expression): static;

    /**
     * Returns the average value of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     *
     * @param mixed|Expr $expression
     */
    public function avg($expression): static;

    /**
     * Returns the bottom element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottom/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     */
    public function bottom($output, $sortBy): static;

    /**
     * Returns the n bottom elements within a group according to the specified
     * sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottomN/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     */
    public function bottomN($output, $sortBy, $n): static;

    /**
     * Returns the number of documents in a group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     */
    public function countDocuments(): static;

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     *
     * @param mixed|Expr $expression
     */
    public function first($expression): static;

    /**
     * Returns the value that results from applying an expression to the first n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/firstN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function firstN($expression, $n): static;

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     *
     * @param mixed|Expr $expression
     */
    public function last($expression): static;

    /**
     * Returns the value that results from applying an expression to the last n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function lastN($expression, $n): static;

    /**
     * Returns the highest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     *
     * @param mixed|Expr $expression
     */
    public function max($expression): static;

    /**
     * Returns the highest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function maxN($expression, $n): static;

    /**
     * Returns a document created by combining the input documents for each
     * group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     *
     * @param mixed|Expr $expression
     */
    public function mergeObjects($expression): static;

    /**
     * Returns the lowest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     *
     * @param mixed|Expr $expression
     */
    public function min($expression): static;

    /**
     * Returns the lowest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function minN($expression, $n): static;

    /**
     * Returns an array of expression values for documents in each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     *
     * @param mixed|Expr $expression
     */
    public function push($expression): static;

    /**
     * Returns the population standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     *
     * @param mixed|Expr $expression
     */
    public function stdDevPop($expression): static;

    /**
     * Returns the sample standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     *
     * @param mixed|Expr $expression
     */
    public function stdDevSamp($expression): static;

    /**
     * Calculates the collective sum of numeric values. Ignores non-numeric
     * values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     *
     * @param mixed|Expr $expression
     */
    public function sum($expression): static;

    /**
     * Returns the top element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/top/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     */
    public function top($output, $sortBy): static;

    /**
     * Returns the n top elements within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/topN/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     */
    public function topN($output, $sortBy, $n): static;
}
