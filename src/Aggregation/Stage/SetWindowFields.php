<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\SetWindowFields\Output;
use Doctrine\ODM\MongoDB\Utility\SortHelper;
use SortDirection;

use function is_array;

/**
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-import-type SortOrder from Sort
 * @phpstan-type SortShape array<string, SortOrder>
 * @phpstan-type SetWindowFieldsStageExpression array{
 *     "$setWindowFields": object{
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
     * @param array<string, int|string|SortDirection>|string $fieldName Field name or array of field/order pairs
     * @param int|string|SortDirection                       $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string           $fieldName
     * @phpstan-param SortOrder|null             $order
     */
    public function sortBy($fieldName, $order = null): static
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order ?? 1];

        $this->sortBy = SortHelper::normalizeSortDirections($fields);

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
