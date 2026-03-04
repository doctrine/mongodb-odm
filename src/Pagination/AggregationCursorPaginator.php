<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Iterator\Iterator;

/**
 * @template-covariant TValue
 * @template-extends Paginator<TValue>
 */
final class AggregationCursorPaginator extends Paginator
{
    public function __construct(
        private readonly Builder $aggregation,
        public readonly mixed $after = null,
        public readonly int $perPage = 24,
        public readonly string $field = 'id',
    ) {
    }

    /** @return Iterator<TValue> */
    protected function getResultsForCurrentPage(): Iterator
    {
        $builder = clone $this->aggregation;

        if ($this->after) {
            $builder
                ->match()
                    ->field($this->field)->gt($this->after);
        }

        return $builder
            ->limit($this->perPage)
            ->getAggregation()
            ->getIterator();
    }
}
