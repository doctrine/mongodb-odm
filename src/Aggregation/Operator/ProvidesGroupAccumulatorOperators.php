<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
use MongoDB\BSON\Javascript;

use function func_get_args;

/** @internal */
trait ProvidesGroupAccumulatorOperators
{
    abstract protected function getExpr(): Expr;

    public function accumulator(string|Javascript $init, string|Javascript $accumulate, mixed $accumulateArgs, string|Javascript $merge, mixed $initArgs = null, string|Javascript|null $finalize = null, string $lang = 'js'): static
    {
        $this->getExpr()->accumulator(...func_get_args());

        return $this;
    }

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

    public function function(string|Javascript $body, mixed $args, string $lang = 'js'): static
    {
        $this->getExpr()->function(...func_get_args());

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

    public function mergeObjects(mixed $expression, mixed ...$expressions): static
    {
        $this->getExpr()->mergeObjects(...func_get_args());

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
