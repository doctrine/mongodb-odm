<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Fill\Output;

use function array_values;
use function is_array;
use function is_string;
use function strtolower;

/**
 * Fluent interface for adding a $fill stage to an aggregation pipeline.
 *
 * @psalm-import-type SortDirectionKeywords from Sort
 * @psalm-import-type OperatorExpression from Expr
 * @psalm-type SortDirection = int|SortDirectionKeywords
 * @psalm-type SortShape = array<string, SortDirection>
 * @psalm-type FillStageExpression = array{
 *     '$fill': array{
 *         partitionBy?: string|OperatorExpression,
 *         partitionByFields?: list<string>,
 *         sortBy?: SortShape,
 *         output: array,
 *     }
 * }
 */
class Fill extends Stage
{
    /** @var mixed|Expr|null */
    private $partitionBy = null;

    /** @var array<string> */
    private array $partitionByFields = [];

    /** @var array<string, int> */
    private array $sortBy = [];

    private Output $output;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->output = new Output($this->builder, $this);
    }

    /** @param mixed|Expr $expression */
    public function partitionBy($expression): static
    {
        $this->partitionBy = $expression;

        return $this;
    }

    public function partitionByFields(string ...$fields): static
    {
        $this->partitionByFields = array_values($fields);

        return $this;
    }

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @psalm-param SortShape|string           $fieldName
     * @psalm-param SortDirection|null         $order
     */
    public function sortBy($fieldName, $order = null): static
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order ?? 1];

        foreach ($fields as $fieldName => $order) {
            if (is_string($order)) {
                $order = strtolower($order) === 'asc' ? 1 : -1;
            }

            $this->sortBy[$fieldName] = $order;
        }

        return $this;
    }

    public function output(): Output
    {
        return $this->output;
    }

    public function getExpression(): array
    {
        $params = (object) [
            'output' => (object) $this->output->getExpression(),
        ];

        if ($this->partitionBy) {
            $params->partitionBy = $this->partitionBy instanceof Expr
                ? $this->partitionBy->getExpression()
                : $this->partitionBy;
        }

        if ($this->partitionByFields) {
            $params->partitionByFields = $this->partitionByFields;
        }

        if ($this->sortBy) {
            $params->sortBy = (object) $this->sortBy;
        }

        return ['$fill' => $params];
    }
}
