<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

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
     */
    public function addToSet(mixed $expression): static;

    /**
     * Returns the average value of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     */
    public function avg(mixed $expression): static;

    /**
     * Returns the bottom element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottom/
     *
     * @param array<string, int|string> $sortBy
     */
    public function bottom(mixed $output, array $sortBy): static;

    /**
     * Returns the n bottom elements within a group according to the specified
     * sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottomN/
     *
     * @param array<string, int|string> $sortBy
     */
    public function bottomN(mixed $output, array $sortBy, mixed $n): static;

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
     */
    public function first(mixed $expression): static;

    /**
     * Returns the value that results from applying an expression to the first n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/firstN/
     */
    public function firstN(mixed $expression, mixed $n): static;

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     */
    public function last(mixed $expression): static;

    /**
     * Returns the value that results from applying an expression to the last n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN/
     */
    public function lastN(mixed $expression, mixed $n): static;

    /**
     * Returns the highest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     */
    public function max(mixed $expression): static;

    /**
     * Returns the highest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     */
    public function maxN(mixed $expression, mixed $n): static;

    /**
     * Returns a document created by combining the input documents for each
     * group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     */
    public function mergeObjects(mixed $expression): static;

    /**
     * Returns the lowest expression value for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     */
    public function min(mixed $expression): static;

    /**
     * Returns the lowest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     */
    public function minN(mixed $expression, mixed $n): static;

    /**
     * Returns an array of expression values for documents in each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     */
    public function push(mixed $expression): static;

    /**
     * Returns the population standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     */
    public function stdDevPop(mixed $expression): static;

    /**
     * Returns the sample standard deviation of the input values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     */
    public function stdDevSamp(mixed $expression): static;

    /**
     * Calculates the collective sum of numeric values. Ignores non-numeric
     * values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     */
    public function sum(mixed $expression): static;

    /**
     * Returns the top element within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/top/
     *
     * @param array<string, int|string> $sortBy
     */
    public function top(mixed $output, array $sortBy): static;

    /**
     * Returns the n top elements within a group according to the specified sort
     * order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/topN/
     *
     * @param array<string, int|string> $sortBy
     */
    public function topN(mixed $output, array $sortBy, mixed $n): static;
}
