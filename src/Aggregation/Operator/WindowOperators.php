<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

/**
 * Interface containing all window aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface WindowOperators
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
     * Returns the population covariance of two numeric expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/covariancePop/
     */
    public function covariancePop(mixed $expression1, mixed $expression2): static;

    /**
     * Returns the sample covariance of two numeric expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/covarianceSamp/
     */
    public function covarianceSamp(mixed $expression1, mixed $expression2): static;

    /**
     * Returns the document position (rank) relative to other documents in the
     * current partition. There are no gaps in the ranks. Ties receive the same
     * rank.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/denseRank/
     */
    public function denseRank(): static;

    /**
     * Returns the average rate of change within the specified window.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/derivative/
     */
    public function derivative(mixed $input, string $unit): static;

    /**
     * Returns the position of a document in the current partition. Ties result
     * in different adjacent document numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/documentNumber/
     */
    public function documentNumber(): static;

    /**
     * Returns the exponential moving average for the numeric expression.
     *
     * You must provide either n or alpha. You cannot provide both.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/expMovingAvg/
     *
     * @param int|null   $n     An integer that specifies the number of historical documents that have a significant mathematical weight in the exponential moving average calculation, with the most recent documents contributing the most weight.
     * @param float|null $alpha A double that specifies the exponential decay value to use in the exponential moving average calculation. A higher alpha value assigns a lower mathematical significance to previous results from the calculation.
     */
    public function expMovingAvg(mixed $input, ?int $n = null, ?float $alpha = null): static;

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
     * Returns the approximation of the area under a curve.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/integral/
     */
    public function integral(mixed $input, string $unit): static;

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
     * Fills null and missing fields in a window using linear interpolation
     * based on surrounding field values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/linearFill/
     */
    public function linearFill(mixed $expression): static;

    /**
     * Last observation carried forward. Sets values for null and missing fields
     * in a window to the last non-null value for the field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/locf/
     */
    public function locf(mixed $expression): static;

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
     * Returns the document position (rank) relative to other documents in the
     * current partition. If multiple documents occupy the same rank, $rank
     * places the document with the subsequent value at a rank with a gap.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rank/
     */
    public function rank(): static;

    /**
     * Returns the value from an expression applied to a document in a specified
     * position relative to the current document in the current partition.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/shift/
     */
    public function shift(mixed $output, int $by, mixed $default = null): static;

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
