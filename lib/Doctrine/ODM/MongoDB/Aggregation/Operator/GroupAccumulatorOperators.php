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
     *
     * @return static
     */
    public function addToSet($expression): self;

    /**
     * Returns the average value of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function avg($expression): self;

    /**
     * Returns the bottom element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottom/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     *
     * @return static
     */
    public function bottom($output, $sortBy): self;

    /**
     * Returns the n bottom elements within a group according to the specified
     * sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottomN/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     *
     * @return static
     */
    public function bottomN($output, $sortBy, $n): self;

    /**
     * Returns the number of documents in a group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     *
     * @return static
     */
    public function countDocuments(): self;

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function first($expression): self;

    /**
     * Returns the value that results from applying an expression to the first n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/firstN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     *
     * @return static
     */
    public function firstN($expression, $n): self;

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function last($expression): self;

    /**
     * Returns the value that results from applying an expression to the last n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     *
     * @return static
     */
    public function lastN($expression, $n): self;

    /**
     * Returns the highest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function max($expression): self;

    /**
     * Returns the highest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     *
     * @return static
     */
    public function maxN($expression, $n): self;

    /**
     * Returns a document created by combining the input documents for each
     * group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function mergeObjects($expression): self;

    /**
     * Returns the lowest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function min($expression): self;

    /**
     * Returns the lowest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     *
     * @return static
     */
    public function minN($expression, $n): self;

    /**
     * Returns an array of expression values for documents in each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function push($expression): self;

    /**
     * Returns the population standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function stdDevPop($expression): self;

    /**
     * Returns the sample standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function stdDevSamp($expression): self;

    /**
     * Calculates the collective sum of numeric values. Ignores non-numeric
     * values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     *
     * @param mixed|Expr $expression
     *
     * @return static
     */
    public function sum($expression): self;

    /**
     * Returns the top element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/top/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     *
     * @return static
     */
    public function top($output, $sortBy): self;

    /**
     * Returns the n top elements within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/topN/
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     *
     * @return static
     */
    public function topN($output, $sortBy, $n): self;
}
