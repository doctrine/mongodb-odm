<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Countable;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;

use function ceil;

/**
 * @template-covariant TValue
 * @template-extends Paginator<TValue>
 */
final class QueryOffsetPaginator extends Paginator implements Countable
{
    private int $pageCount;

    /** @psalm-param positive-int $page */
    public function __construct(
        private readonly Builder $query,
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
        $builder = clone $this->query;

        return (int) ceil($builder
            ->hydrate(false)
            ->count()
            ->getQuery()
            ->execute() / $this->perPage);
    }

    /** @return Iterator<TValue> */
    protected function getResultsForCurrentPage(): Iterator
    {
        $builder = clone $this->query;
        $builder
            ->skip(($this->page - 1) * $this->perPage)
            ->limit($this->perPage);

        return $builder->getQuery()->getIterator();
    }
}
