<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use InvalidArgumentException;
use SortDirection;

use function get_debug_type;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function sprintf;
use function strtolower;
use function var_export;

/**
 * Utility class to normalize sort directions.
 *
 * @internal
 *
 * @phpstan-type SortMetaKeywords "textScore"|"indexKey"|"searchScore"|"vectorSearchScore"
 * @phpstan-type SortDirectionKeywords "asc"|"ASC"|"desc"|"DESC"
 * @phpstan-type SortOrder 1|-1|SortDirectionKeywords|SortDirection
 * @phpstan-type SortMeta array{"$meta": SortMetaKeywords}
 * @phpstan-type SortShape array<string, SortMeta|SortOrder>
 */
final class SortHelper
{
    /**
     * Normalizes the sort direction of each field to 1 or -1, mapping the
     * SortDirection enum and the "asc" and "desc" keywords (in any case).
     * Strings listed in $allowedMetaSort are wrapped as a $meta expression,
     * and $meta expressions are kept as-is.
     *
     * @param array<string, SortMeta|SortOrder> $fields
     * @param list<string>                      $allowedMetaSort
     *
     * @return array<string, 1|-1|SortMeta>
     *
     * @throws InvalidArgumentException if a sort order is not 1, -1, "asc", "desc", a SortDirection case or a $meta expression.
     */
    public static function normalizeSortDirections(array $fields, array $allowedMetaSort = []): array
    {
        $normalized = [];

        foreach ($fields as $fieldName => $order) {
            $normalized[$fieldName] = match (true) {
                $order === 1, $order === -1 => $order,
                $order instanceof SortDirection => $order === SortDirection::Ascending ? 1 : -1,
                is_array($order) => $order,
                is_string($order) && in_array($order, $allowedMetaSort, true) => ['$meta' => $order],
                is_string($order) && strtolower($order) === 'asc' => 1,
                is_string($order) && strtolower($order) === 'desc' => -1,
                default => throw new InvalidArgumentException(sprintf(
                    'Invalid sort order %s for field "%s". Allowed values are 1, -1, "asc", "desc" and SortDirection cases.',
                    is_scalar($order) ? var_export($order, true) : get_debug_type($order),
                    $fieldName,
                )),
            };
        }

        return $normalized;
    }
}
