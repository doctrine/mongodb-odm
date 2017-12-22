<?php

namespace Doctrine\ODM\MongoDB\Query;

/**
 * Utility class for merging query criteria.
 *
 * This is mainly used to incorporate filter and ReferenceMany mapping criteria
 * into a query. Each criteria array will be joined with "$and" to avoid cases
 * where criteria might be inadvertently overridden with array_merge().
 */
class CriteriaMerger
{
    /**
     * Combines any number of criteria arrays as clauses of an "$and" query.
     *
     * @param array $criteria,... Any number of query criteria arrays
     * @return array
     */
    public function merge(/* array($field => $value), ... */)
    {
        $merged = array();

        foreach (func_get_args() as $criteria) {
            if (empty($criteria)) {
                continue;
            }

            $merged['$and'][] = $criteria;
        }

        return (isset($merged['$and']) && count($merged['$and']) === 1)
            ? $merged['$and'][0]
            : $merged;
    }
}
