<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\SetWindowFields\Output;

use function is_array;
use function is_string;
use function strtolower;

/**
 * @phpstan-import-type SortDirectionKeywords from Sort
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type SortDirection int|SortDirectionKeywords
 * @phpstan-type SortShape array<string, SortDirection>
 * @phpstan-type SetWindowFieldsStageExpression array{
 *     '$setWindowFields': object{
 *         partitionBy?: string|OperatorExpression,
 *         sortBy?: SortShape,
 *         output: object,
 *     }
 * }
 */
class SetWindowFields extends Stage
{
    private mixed $partitionBy = null;

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

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string           $fieldName
     * @phpstan-param SortDirection|null         $order
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

    /** @phpstan-return SetWindowFieldsStageExpression */
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

        if ($this->sortBy) {
            $params->sortBy = (object) $this->sortBy;
        }

        return ['$setWindowFields' => $params];
    }
}
