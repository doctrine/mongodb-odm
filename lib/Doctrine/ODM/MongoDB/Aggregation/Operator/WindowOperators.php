<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

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
     * Returns the population covariance of two numeric expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/covariancePop/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function covariancePop($expression1, $expression2): static;

    /**
     * Returns the sample covariance of two numeric expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/covarianceSamp/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function covarianceSamp($expression1, $expression2): static;

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
     *
     * @param mixed|Expr $input
     */
    public function derivative($input, string $unit): static;

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
     * @param mixed|Expr $input
     * @param int|null   $n     An integer that specifies the number of historical documents that have a significant mathematical weight in the exponential moving average calculation, with the most recent documents contributing the most weight.
     * @param float|null $alpha A double that specifies the exponential decay value to use in the exponential moving average calculation. A higher alpha value assigns a lower mathematical significance to previous results from the calculation.
     */
    public function expMovingAvg($input, ?int $n = null, ?float $alpha = null): static;

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
     * Returns the approximation of the area under a curve.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/integral/
     *
     * @param mixed|Expr $input
     */
    public function integral($input, string $unit): static;

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
     * Fills null and missing fields in a window using linear interpolation
     * based on surrounding field values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/linearFill/
     *
     * @param mixed|Expr $expression
     */
    public function linearFill($expression): static;

    /**
     * Last observation carried forward. Sets values for null and missing fields
     * in a window to the last non-null value for the field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/locf/
     *
     * @param mixed|Expr $expression
     */
    public function locf($expression): static;

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
     *
     * @param mixed|Expr      $output
     * @param mixed|Expr|null $default
     */
    public function shift($output, int $by, $default = null): static;

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
