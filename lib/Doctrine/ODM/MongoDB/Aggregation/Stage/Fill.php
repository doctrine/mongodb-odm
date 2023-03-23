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
 * @psalm-type SortShape = array<string, int|string>
 */
class Fill extends Stage
{
    /** @var mixed|Expr|null */
    private $partitionBy = null;

    /** @var array<string> */
    private array $partitionByFields = [];

    /** @var array<string, int> */
    private array $sortBy = [];

    private ?Output $output = null;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    /** @param mixed|Expr $expression */
    public function partitionBy($expression): self
    {
        $this->partitionBy = $expression;

        return $this;
    }

    public function partitionByFields(string ...$fields): self
    {
        $this->partitionByFields = array_values($fields);

        return $this;
    }

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @psalm-param SortShape|string           $fieldName
     */
    public function sortBy($fieldName, $order = null): self
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
        if (! $this->output) {
            $this->output = new Output($this->builder, $this);
        }

        return $this->output;
    }

    public function getExpression(): array
    {
        $params = (object) [];

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

        if ($this->output) {
            $params->output = (object) $this->output->getExpression();
        }

        return ['$fill' => $params];
    }
}
