<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use SortDirection;

use function in_array;
use function is_string;
use function strtolower;

/**
 * Utility class to normalize sort directions.
 *
 * @internal
 *
 * @phpstan-type SortMetaKeywords "textScore"|"indexKey"
 * @phpstan-type SortDirectionKeywords "asc"|"ASC"|"desc"|"DESC"
 * @phpstan-type SortOrder 1|-1|SortDirectionKeywords|SortDirection
 * @phpstan-type SortMeta array{"$meta": SortMetaKeywords}
 * @phpstan-type SortShape array<string, SortMeta|SortOrder>
 */
final class SortHelper
{
    /**
     * Normalizes the sort direction of each field to a {@see 1|-1} value or a
     * $meta expression, mapping the SortDirection enum and the "asc", "ASC",
     * "desc" and "DESC" keywords to 1 and -1. Strings listed in
     * $allowedMetaSort are wrapped as a $meta expression instead.
     *
     * @param array<string, SortMeta|SortOrder> $fields
     * @param list<string>                      $allowedMetaSort
     *
     * @return array<string, 1|-1|SortMeta>
     */
    public static function normalizeSortDirections(array $fields, array $allowedMetaSort = []): array
    {
        $normalized = [];

        foreach ($fields as $fieldName => $order) {
            $normalized[$fieldName] = match (true) {
                $order instanceof SortDirection => $order === SortDirection::Ascending ? 1 : -1,
                is_string($order) && in_array($order, $allowedMetaSort, true) => ['$meta' => $order],
                is_string($order) => strtolower($order) === 'asc' ? 1 : -1,
                default => $order,
            };
        }

        return $normalized;
    }
}
