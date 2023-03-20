<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
use MongoDB\BSON\Javascript;

trait ProvidesGroupAccumulatorOperators
{
    abstract protected function getExpr(): Expr;

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
     * @see Expr::accumulator
     *
     * @param string|Javascript $init
     * @param mixed|Expr        $initArgs
     * @param string|Javascript $accumulate
     * @param mixed|Expr        $accumulateArgs
     * @param string|Javascript $merge
     * @param string|Javascript $finalize
     * @param string            $lang
     */
    public function accumulator($init, $initArgs, $accumulate, $accumulateArgs, $merge, $finalize, $lang = 'js'): self
    {
        $this->getExpr()->accumulator($init, $initArgs, $accumulate, $accumulateArgs, $merge, $finalize, $lang);

        return $this;
    }

    /**
     * Returns an array of all unique values that results from applying an
     * expression to each document in a group of documents that share the same
     * group by key. Order of the elements in the output array is unspecified.
     *
     * AddToSet is an accumulator operation only available in the group stage.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/addToSet/
     * @see Expr::addToSet
     *
     * @param mixed|Expr $expression
     *
     * @return $this
     */
    public function addToSet($expression): self
    {
        $this->getExpr()->addToSet($expression);

        return $this;
    }

    /**
     * Returns the average value of the numeric values that result from applying
     * a specified expression to each document in a group of documents that
     * share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     * @see Expr::avg
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function avg($expression, ...$expressions): self
    {
        $this->getExpr()->avg($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the bottom element within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottom/
     * @see Expr::bottom
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     */
    public function bottom($output, $sortBy): self
    {
        $this->getExpr()->bottom($output, $sortBy);

        return $this;
    }

    /**
     * Returns the n bottom elements within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottomN/
     * @see Expr::bottomN
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     */
    public function bottomN($output, $sortBy, $n): self
    {
        $this->getExpr()->bottomN($output, $sortBy, $n);

        return $this;
    }

    /**
     * Returns the number of documents in a group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     * @see Expr::count
     */
    public function countDocuments(): self
    {
        $this->getExpr()->countDocuments();

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     * @see Expr::first
     *
     * @param mixed|Expr $expression
     */
    public function first($expression): self
    {
        $this->getExpr()->first($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the first n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/firstN/
     * @see Expr::firstN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function firstN($expression, $n): self
    {
        $this->getExpr()->firstN($expression, $n);

        return $this;
    }

    /**
     * Defines a custom aggregation function or expression in JavaScript.
     *
     * You can use the $function operator to define custom functions to
     * implement behavior not supported by the MongoDB Query Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/function/
     * @see Expr::function
     *
     * @param string|Javascript $body
     * @param mixed|Expr        $args
     * @param string            $lang
     */
    public function function($body, $args, $lang = 'js'): self
    {
        $this->getExpr()->function($body, $args, $lang);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     * @see Expr::last
     *
     * @param mixed|Expr $expression
     */
    public function last($expression): self
    {
        $this->getExpr()->last($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the last n
     * documents in a group of documents. Only meaningful when documents are in
     * a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN/
     * @see Expr::lastN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function lastN($expression, $n): self
    {
        $this->getExpr()->lastN($expression, $n);

        return $this;
    }

    /**
     * Returns the highest value that results from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     * @see Expr::max
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function max($expression, ...$expressions): self
    {
        $this->getExpr()->max($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the highest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     * @see Expr::maxN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function maxN($expression, $n): self
    {
        $this->getExpr()->maxN($expression, $n);

        return $this;
    }

    /**
     * Returns a document created by combining the input documents for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     * @see Expr::mergeObjects
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function mergeObjects($expression, ...$expressions): self
    {
        $this->getExpr()->mergeObjects($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the lowest value that results from applying an expression to each
     * document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     * @see Expr::min
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function min($expression, ...$expressions): self
    {
        $this->getExpr()->min($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the lowest n expression values for each group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     * @see Expr::minN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function minN($expression, $n): self
    {
        $this->getExpr()->minN($expression, $n);

        return $this;
    }

    /**
     * Returns an array of all values that result from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     * @see Expr::push
     *
     * @param mixed|Expr $expression
     */
    public function push($expression): self
    {
        $this->getExpr()->push($expression);

        return $this;
    }

    /**
     * Calculates the population standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     * @see Expr::stdDevPop
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevPop($expression, ...$expressions): self
    {
        $this->getExpr()->stdDevPop($expression, ...$expressions);

        return $this;
    }

    /**
     * Calculates the sample standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     * @see Expr::stdDevSamp
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevSamp($expression, ...$expressions): self
    {
        $this->getExpr()->stdDevSamp($expression, ...$expressions);

        return $this;
    }

    /**
     * Calculates and returns the sum of all the numeric values that result from
     * applying a specified expression to each document in a group of documents
     * that share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     * @see Expr::sum
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function sum($expression, ...$expressions): self
    {
        $this->getExpr()->sum($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the top element within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/top/
     * @see Expr::top
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     */
    public function top($output, $sortBy): self
    {
        $this->getExpr()->top($output, $sortBy);

        return $this;
    }

    /**
     * Returns the n top elements within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/topN/
     * @see Expr::topN
     *
     * @param mixed|Expr                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|Expr                $n
     */
    public function topN($output, $sortBy, $n): self
    {
        $this->getExpr()->topN($output, $sortBy, $n);

        return $this;
    }
}
