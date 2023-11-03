<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

use function func_get_args;

/** @internal */
trait ProvidesWindowOperators
{
    abstract protected function getExpr(): Expr;

    public function addToSet($expression): static
    {
        $this->getExpr()->addToSet(...func_get_args());

        return $this;
    }

    public function avg($expression, ...$expressions): static
    {
        $this->getExpr()->avg(...func_get_args());

        return $this;
    }

    public function bottom($output, $sortBy): static
    {
        $this->getExpr()->bottom(...func_get_args());

        return $this;
    }

    public function bottomN($output, $sortBy, $n): static
    {
        $this->getExpr()->bottomN(...func_get_args());

        return $this;
    }

    public function countDocuments(): static
    {
        $this->getExpr()->countDocuments();

        return $this;
    }

    public function covariancePop($expression1, $expression2): static
    {
        $this->getExpr()->covariancePop(...func_get_args());

        return $this;
    }

    public function covarianceSamp($expression1, $expression2): static
    {
        $this->getExpr()->covarianceSamp(...func_get_args());

        return $this;
    }

    public function denseRank(): static
    {
        $this->getExpr()->denseRank();

        return $this;
    }

    public function derivative($input, string $unit): static
    {
        $this->getExpr()->derivative(...func_get_args());

        return $this;
    }

    public function documentNumber(): static
    {
        $this->getExpr()->documentNumber();

        return $this;
    }

    public function expMovingAvg($input, ?int $n = null, ?float $alpha = null): static
    {
        $this->getExpr()->expMovingAvg(...func_get_args());

        return $this;
    }

    public function first($expression): static
    {
        $this->getExpr()->first(...func_get_args());

        return $this;
    }

    public function firstN($expression, $n): static
    {
        $this->getExpr()->firstN(...func_get_args());

        return $this;
    }

    public function integral($input, string $unit): static
    {
        $this->getExpr()->integral(...func_get_args());

        return $this;
    }

    public function last($expression): static
    {
        $this->getExpr()->last(...func_get_args());

        return $this;
    }

    public function lastN($expression, $n): static
    {
        $this->getExpr()->lastN(...func_get_args());

        return $this;
    }

    public function linearFill($expression): static
    {
        $this->getExpr()->linearFill(...func_get_args());

        return $this;
    }

    public function locf($expression): static
    {
        $this->getExpr()->locf(...func_get_args());

        return $this;
    }

    public function max($expression, ...$expressions): static
    {
        $this->getExpr()->max(...func_get_args());

        return $this;
    }

    public function maxN($expression, $n): static
    {
        $this->getExpr()->maxN(...func_get_args());

        return $this;
    }

    public function min($expression, ...$expressions): static
    {
        $this->getExpr()->min(...func_get_args());

        return $this;
    }

    public function minN($expression, $n): static
    {
        $this->getExpr()->minN(...func_get_args());

        return $this;
    }

    public function push($expression): static
    {
        $this->getExpr()->push(...func_get_args());

        return $this;
    }

    public function rank(): static
    {
        $this->getExpr()->rank();

        return $this;
    }

    public function shift($output, int $by, $default = null): static
    {
        $this->getExpr()->shift(...func_get_args());

        return $this;
    }

    public function stdDevPop($expression, ...$expressions): static
    {
        $this->getExpr()->stdDevPop(...func_get_args());

        return $this;
    }

    public function stdDevSamp($expression, ...$expressions): static
    {
        $this->getExpr()->stdDevSamp(...func_get_args());

        return $this;
    }

    public function sum($expression, ...$expressions): static
    {
        $this->getExpr()->sum(...func_get_args());

        return $this;
    }

    public function top($output, $sortBy): static
    {
        $this->getExpr()->top(...func_get_args());

        return $this;
    }

    public function topN($output, $sortBy, $n): static
    {
        $this->getExpr()->topN(...func_get_args());

        return $this;
    }
}
