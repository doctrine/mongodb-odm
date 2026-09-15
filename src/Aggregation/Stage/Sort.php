<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Utility\SortHelper;
use SortDirection;

use function is_array;

/**
 * Fluent interface for adding a $sort stage to an aggregation pipeline.
 *
 * @phpstan-import-type SortDirectionKeywords from SortHelper
 * @phpstan-import-type SortMeta from SortHelper
 * @phpstan-import-type SortOrder from SortHelper
 * @phpstan-import-type SortShape from SortHelper
 * @phpstan-type SortStageExpression array{
 *     "$sort": array<string, int|SortMeta>
 * }
 */
class Sort extends Stage
{
    /**
     * Sort keywords that are converted to a $meta expression instead of a
     * sort direction.
     *
     * @see https://www.mongodb.com/docs/manual/reference/operator/aggregation/meta/
     */
    public const ALLOWED_META_SORTS = ['textScore', 'indexKey', 'searchScore', 'vectorSearchScore'];

    /** @var array<string, -1|1|SortMeta> */
    private array $sort = [];

    /**
     * @param array<string, int|string|SortDirection|array<string, string>>|string $fieldName Field name or array of field/order pairs
     * @param int|string|SortDirection                                             $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string        $fieldName
     * @phpstan-param SortMeta|SortOrder|null $order
     */
    public function __construct(Builder $builder, $fieldName, $order = null)
    {
        parent::__construct($builder);

        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order ?? 1];

        $this->sort = SortHelper::normalizeSortDirections($fields, self::ALLOWED_META_SORTS);
    }

    /** @phpstan-return SortStageExpression */
    public function getExpression(): array
    {
        return [
            '$sort' => $this->sort,
        ];
    }
}
