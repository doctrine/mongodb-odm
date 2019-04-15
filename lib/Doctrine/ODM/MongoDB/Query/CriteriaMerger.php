<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use const E_USER_DEPRECATED;
use function array_filter;
use function array_values;
use function count;
use function sprintf;
use function trigger_error;

/**
 * Utility class for merging query criteria.
 *
 * This is mainly used to incorporate filter and ReferenceMany mapping criteria
 * into a query. Each criteria array will be joined with "$and" to avoid cases
 * where criteria might be inadvertently overridden with array_merge().
 *
 * @final
 */
class CriteriaMerger
{
    public function __construct()
    {
        if (self::class === static::class) {
            return;
        }

        @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
    }

    /**
     * Combines any number of criteria arrays as clauses of an "$and" query.
     *
     * @param array ...$criterias Any number of query criteria arrays
     */
    public function merge(...$criterias) : array
    {
        $nonEmptyCriterias = array_values(array_filter($criterias, static function (array $criteria) {
            return ! empty($criteria);
        }));

        switch (count($nonEmptyCriterias)) {
            case 0:
                return [];

            case 1:
                return $nonEmptyCriterias[0];

            default:
                return ['$and' => $nonEmptyCriterias];
        }
    }
}
