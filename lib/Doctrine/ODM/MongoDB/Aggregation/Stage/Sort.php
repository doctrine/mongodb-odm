<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

use function in_array;
use function is_array;
use function is_string;
use function strtolower;

/**
 * Fluent interface for adding a $sort stage to an aggregation pipeline.
 *
 * @psalm-type SortMetaKeywords = 'textScore'|'indexKey'
 * @psalm-type SortDirectionKeywords = 'asc'|'desc'
 * @psalm-type SortMeta = array{'$meta': SortMetaKeywords}
 * @psalm-type SortShape = array<string, int|SortMeta|SortDirectionKeywords>
 * @psalm-type SortStageExpression = array{
 *     '$sort': array<string, int|SortMeta>
 * }
 */
class Sort extends Stage
{
    /** @var array<string, -1|1|SortMeta> */
    private array $sort = [];

    /**
     * @param array<string, int|string|array<string, string>>|string $fieldName Field name or array of field/order pairs
     * @param int|string                                             $order     Field order (if one field is specified)
     * @psalm-param SortShape|string                        $fieldName
     * @psalm-param int|SortMeta|SortDirectionKeywords|null $order
     */
    public function __construct(Builder $builder, $fieldName, $order = null)
    {
        parent::__construct($builder);

        $allowedMetaSort = ['textScore'];

        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        foreach ($fields as $fieldName => $order) {
            if (is_string($order)) {
                if (in_array($order, $allowedMetaSort, true)) {
                    $order = ['$meta' => $order];
                } else {
                    $order = strtolower($order) === 'asc' ? 1 : -1;
                }
            }

            $this->sort[$fieldName] = $order;
        }
    }

    /** @psalm-return SortStageExpression */
    public function getExpression(): array
    {
        return [
            '$sort' => $this->sort,
        ];
    }
}
