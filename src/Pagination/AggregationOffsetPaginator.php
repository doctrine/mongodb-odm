<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Countable;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Iterator\Iterator;

use function ceil;
use function iterator_to_array;

/**
 * @template-covariant TValue
 * @template-extends Paginator<TValue>
 */
final class AggregationOffsetPaginator extends Paginator implements Countable
{
    private int $pageCount;

    /** @psalm-param positive-int $page */
    public function __construct(
        private readonly Builder $aggregation,
        public readonly int $page,
        public readonly int $perPage = 24,
    ) {
    }

    public function count(): int
    {
        return $this->pageCount ??= $this->getNumberOfPages();
    }

    private function getNumberOfPages(): int
    {
        $builder    = clone $this->aggregation;
        $results    = $builder
            ->hydrate(null)
            ->count('numDocuments')
            ->getAggregation()
            ->getIterator();
        $numResults = iterator_to_array($results)[0]['numDocuments'] ?? 0;

        return (int) ceil($numResults / $this->perPage);
    }

    /** @return Iterator<TValue> */
    protected function getResultsForCurrentPage(): Iterator
    {
        $builder = clone $this->aggregation;
        $builder
            ->skip(($this->page - 1) * $this->perPage)
            ->limit($this->perPage);

        return $builder->getAggregation()->getIterator();
    }
}
