<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Fill;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Fill;
use LogicException;

use function func_get_args;
use function sprintf;

/**
 * Fluent builder for output param of $fill stage
 *
 * @phpstan-import-type SortShape from Fill
 */
class Output extends Stage
{
    private string $currentField = '';

    /** @var array<string, array<string, mixed>> */
    private array $output = [];

    public function __construct(Builder $builder, private Fill $fill)
    {
        parent::__construct($builder);
    }

    /** @param mixed|Expr $expression */
    public function partitionBy($expression): Fill
    {
        return $this->fill->partitionBy($expression);
    }

    public function partitionByFields(string ...$fields): Fill
    {
        return $this->fill->partitionByFields(...$fields);
    }

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string           $fieldName
     */
    public function sortBy($fieldName, $order = null): Fill
    {
        return $this->fill->sortBy(...func_get_args());
    }

    /**
     * Set the current field for building the expression.
     */
    public function field(string $fieldName): static
    {
        $this->currentField = $fieldName;

        return $this;
    }

    public function linear(): static
    {
        $this->requiresCurrentField(__METHOD__);
        $this->output[$this->currentField] = ['method' => 'linear'];

        return $this;
    }

    public function locf(): static
    {
        $this->requiresCurrentField(__METHOD__);
        $this->output[$this->currentField] = ['method' => 'locf'];

        return $this;
    }

    /** @param mixed|Expr $expression */
    public function value($expression): static
    {
        $this->requiresCurrentField(__METHOD__);
        $this->output[$this->currentField] = [
            'value' => $expression instanceof Expr ? $expression->getExpression() : $expression,
        ];

        return $this;
    }

    public function getExpression(): array
    {
        return $this->output;
    }

    /**
     * Ensure that a current field has been set.
     *
     * @throws LogicException if a current field has not been set.
     */
    private function requiresCurrentField(string $method): void
    {
        if (! $this->currentField) {
            throw new LogicException(sprintf('%s requires setting a current field using field().', $method));
        }
    }
}
