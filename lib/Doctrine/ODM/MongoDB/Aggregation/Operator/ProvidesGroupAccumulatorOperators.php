<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

use function func_get_args;

trait ProvidesGroupAccumulatorOperators
{
    abstract protected function getExpr(): Expr;

    /** @return static */
    public function accumulator($init, $initArgs, $accumulate, $accumulateArgs, $merge, $finalize = null, $lang = 'js'): self
    {
        $this->getExpr()->accumulator(...func_get_args());

        return $this;
    }

    /** @return static */
    public function addToSet($expression): self
    {
        $this->getExpr()->addToSet(...func_get_args());

        return $this;
    }

    /** @return static */
    public function avg($expression, ...$expressions): self
    {
        $this->getExpr()->avg(...func_get_args());

        return $this;
    }

    /** @return static */
    public function bottom($output, $sortBy): self
    {
        $this->getExpr()->bottom(...func_get_args());

        return $this;
    }

    /** @return static */
    public function bottomN($output, $sortBy, $n): self
    {
        $this->getExpr()->bottomN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function countDocuments(): self
    {
        $this->getExpr()->countDocuments();

        return $this;
    }

    /** @return static */
    public function first($expression): self
    {
        $this->getExpr()->first(...func_get_args());

        return $this;
    }

    /** @return static */
    public function firstN($expression, $n): self
    {
        $this->getExpr()->firstN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function function($body, $args, $lang = 'js'): self
    {
        $this->getExpr()->function(...func_get_args());

        return $this;
    }

    /** @return static */
    public function last($expression): self
    {
        $this->getExpr()->last(...func_get_args());

        return $this;
    }

    /** @return static */
    public function lastN($expression, $n): self
    {
        $this->getExpr()->lastN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function max($expression, ...$expressions): self
    {
        $this->getExpr()->max(...func_get_args());

        return $this;
    }

    /** @return static */
    public function maxN($expression, $n): self
    {
        $this->getExpr()->maxN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function mergeObjects($expression, ...$expressions): self
    {
        $this->getExpr()->mergeObjects(...func_get_args());

        return $this;
    }

    /** @return static */
    public function min($expression, ...$expressions): self
    {
        $this->getExpr()->min(...func_get_args());

        return $this;
    }

    /** @return static */
    public function minN($expression, $n): self
    {
        $this->getExpr()->minN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function push($expression): self
    {
        $this->getExpr()->push(...func_get_args());

        return $this;
    }

    /** @return static */
    public function stdDevPop($expression, ...$expressions): self
    {
        $this->getExpr()->stdDevPop(...func_get_args());

        return $this;
    }

    /** @return static */
    public function stdDevSamp($expression, ...$expressions): self
    {
        $this->getExpr()->stdDevSamp(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sum($expression, ...$expressions): self
    {
        $this->getExpr()->sum(...func_get_args());

        return $this;
    }

    /** @return static */
    public function top($output, $sortBy): self
    {
        $this->getExpr()->top(...func_get_args());

        return $this;
    }

    /** @return static */
    public function topN($output, $sortBy, $n): self
    {
        $this->getExpr()->topN(...func_get_args());

        return $this;
    }
}
