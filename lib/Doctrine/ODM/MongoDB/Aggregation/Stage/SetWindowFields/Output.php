<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\SetWindowFields;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ProvidesWindowOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\WindowOperators;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Operator;
use Doctrine\ODM\MongoDB\Aggregation\Stage\SetWindowFields;
use LogicException;

use function array_filter;
use function array_map;
use function array_merge_recursive;
use function func_get_args;
use function sprintf;

/**
 * Fluent builder for output param of $setWindowFields stage
 *
 * @phpstan-import-type SortShape from SetWindowFields
 * @phpstan-type WindowBound 'current'|'unbounded'|int
 * @phpstan-type WindowBounds array{0: WindowBound, 1: WindowBound}
 * @phpstan-type WindowUnit 'year'|'quarter'|'month'|'week'|'day'|'hour'|'minute'|'second'|'millisecond'
 * @phpstan-type Window object{
 *     document?: WindowBounds,
 *     range?: WindowBounds,
 *     unit?: WindowUnit,
 * }
 */
class Output extends Operator implements WindowOperators
{
    use ProvidesWindowOperators;

    private string $currentField = '';

    /** @phpstan-var array<string, Window> */
    private array $windows = [];

    public function __construct(Builder $builder, private SetWindowFields $setWindowFields)
    {
        parent::__construct($builder);
    }

    /** @param mixed|Expr $expression */
    public function partitionBy($expression): SetWindowFields
    {
        return $this->setWindowFields->partitionBy($expression);
    }

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string           $fieldName
     */
    public function sortBy($fieldName, $order = null): SetWindowFields
    {
        return $this->setWindowFields->sortBy(...func_get_args());
    }

    /**
     * Set the current field for building the expression.
     */
    public function field(string $fieldName): static
    {
        $this->currentField = $fieldName;
        $this->expr->field($fieldName);

        return $this;
    }

    /**
     * Specifies the window boundaries and parameters.
     *
     * @phpstan-param WindowBounds|null $documents
     * @phpstan-param WindowBounds|null $range
     * @phpstan-param WindowUnit|null $unit
     */
    public function window(?array $documents = null, ?array $range = null, ?string $unit = null): static
    {
        $this->requiresCurrentField(__METHOD__);

        $this->windows[$this->currentField] = (object) array_filter(
            [
                'documents' => $documents,
                'range' => $range,
                'unit' => $unit,
            ],
            static fn ($value): bool => $value !== null,
        );

        return $this;
    }

    public function getExpression(): array
    {
        // Combine field expressions (handled by Expr) and window objects
        // (stored in $this->windows) into a single array. This avoids us having
        // to create a separate Expr class that is capable of handling windows.
        return array_merge_recursive(
            $this->expr->getExpression(),
            $this->getWindowOptionsForFields(),
        );
    }

    protected function getExpr(): Expr
    {
        return $this->expr;
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

    /**
     * Generates window options for all fields that have a window set.
     *
     * Windows are stored directly associated with a field name, but are needed
     * to be wrapped inside a "window" object for the output expression. This
     * method prepares the window options appropriately for use in
     * getExpression().
     *
     * @return array<string, Window>
     */
    private function getWindowOptionsForFields(): array
    {
        return array_map(
            static fn ($window): object => (object) ['window' => $window],
            $this->windows,
        );
    }
}
