<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use OutOfRangeException;

use function is_array;
use function reset;

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
        return $this->createPageBuilder()
            ->limit($this->perPage)
            ->getAggregation()
            ->getIterator();
    }

    private function createPageBuilder(): Builder
    {
        $builder = clone $this->aggregation;

        if ($this->after === null) {
            return $builder;
        }

        $match = $builder->match()->field($this->field);

        if ($this->isSortedDescending()) {
            $match->lt($this->after);
        } else {
            $match->gt($this->after);
        }

        return $builder;
    }

    private function isSortedDescending(): bool
    {
        try {
            $pipeline = $this->aggregation->getPipeline(false);
        } catch (OutOfRangeException) {
            return false;
        }

        $sort = [];
        foreach ($pipeline as $stage) {
            if (is_array($stage['$sort'] ?? null)) {
                $sort = $stage['$sort'];
            }
        }

        if ($sort === []) {
            return false;
        }

        return reset($sort) === -1;
    }
}
