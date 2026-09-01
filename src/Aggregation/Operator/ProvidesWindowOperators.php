<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

use function func_get_args;

/** @internal */
trait ProvidesWindowOperators
{
    abstract protected function getExpr(): Expr;

    public function addToSet(mixed $expression): static
    {
        $this->getExpr()->addToSet(...func_get_args());

        return $this;
    }

    public function avg(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->avg(...func_get_args());

        return $this;
    }

    public function bottom(mixed $output, array $sortBy): static
    {
        $this->getExpr()->bottom(...func_get_args());

        return $this;
    }

    public function bottomN(mixed $output, array $sortBy, mixed $n): static
    {
        $this->getExpr()->bottomN(...func_get_args());

        return $this;
    }

    public function countDocuments(): static
    {
        $this->getExpr()->countDocuments();

        return $this;
    }

    public function covariancePop(mixed $expression1, mixed $expression2): static
    {
        $this->getExpr()->covariancePop(...func_get_args());

        return $this;
    }

    public function covarianceSamp(mixed $expression1, mixed $expression2): static
    {
        $this->getExpr()->covarianceSamp(...func_get_args());

        return $this;
    }

    public function denseRank(): static
    {
        $this->getExpr()->denseRank();

        return $this;
    }

    public function derivative(mixed $input, string $unit): static
    {
        $this->getExpr()->derivative(...func_get_args());

        return $this;
    }

    public function documentNumber(): static
    {
        $this->getExpr()->documentNumber();

        return $this;
    }

    public function expMovingAvg(mixed $input, ?int $n = null, ?float $alpha = null): static
    {
        $this->getExpr()->expMovingAvg(...func_get_args());

        return $this;
    }

    public function first(mixed $expression): static
    {
        $this->getExpr()->first(...func_get_args());

        return $this;
    }

    public function firstN(mixed $expression, mixed $n): static
    {
        $this->getExpr()->firstN(...func_get_args());

        return $this;
    }

    public function integral(mixed $input, string $unit): static
    {
        $this->getExpr()->integral(...func_get_args());

        return $this;
    }

    public function last(mixed $expression): static
    {
        $this->getExpr()->last(...func_get_args());

        return $this;
    }

    public function lastN(mixed $expression, mixed $n): static
    {
        $this->getExpr()->lastN(...func_get_args());

        return $this;
    }

    public function linearFill(mixed $expression): static
    {
        $this->getExpr()->linearFill(...func_get_args());

        return $this;
    }

    public function locf(mixed $expression): static
    {
        $this->getExpr()->locf(...func_get_args());

        return $this;
    }

    public function max(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->max(...func_get_args());

        return $this;
    }

    public function maxN(mixed $expression, mixed $n): static
    {
        $this->getExpr()->maxN(...func_get_args());

        return $this;
    }

    public function min(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->min(...func_get_args());

        return $this;
    }

    public function minN(mixed $expression, mixed $n): static
    {
        $this->getExpr()->minN(...func_get_args());

        return $this;
    }

    public function push(mixed $expression): static
    {
        $this->getExpr()->push(...func_get_args());

        return $this;
    }

    public function rank(): static
    {
        $this->getExpr()->rank();

        return $this;
    }

    public function shift(mixed $output, int $by, mixed $default = null): static
    {
        $this->getExpr()->shift(...func_get_args());

        return $this;
    }

    public function stdDevPop(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->stdDevPop(...func_get_args());

        return $this;
    }

    public function stdDevSamp(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->stdDevSamp(...func_get_args());

        return $this;
    }

    public function sum(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->sum(...func_get_args());

        return $this;
    }

    public function top(mixed $output, array $sortBy): static
    {
        $this->getExpr()->top(...func_get_args());

        return $this;
    }

    public function topN(mixed $output, array $sortBy, mixed $n): static
    {
        $this->getExpr()->topN(...func_get_args());

        return $this;
    }
}
