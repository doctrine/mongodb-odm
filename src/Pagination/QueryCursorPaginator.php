<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * @template-covariant TValue
 * @template-extends Paginator<TValue>
 */
final class QueryCursorPaginator extends Paginator
{
    public function __construct(
        private readonly Builder $query,
        public readonly mixed $after = null,
        public readonly int $perPage = 24,
        public readonly string $field = 'id',
    ) {
    }

    /** @return Iterator<TValue> */
    protected function getResultsForCurrentPage(): Iterator
    {
        $builder = clone $this->query;

        if ($this->after) {
            $builder
                ->field($this->field)->gt($this->after);
        }

        return $builder
            ->limit($this->perPage)
            ->getQuery()
            ->getIterator();
    }
}
