<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;

use function is_array;

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
        return $this->createPageBuilder()
            ->limit($this->perPage)
            ->getQuery()
            ->getIterator();
    }

    private function createPageBuilder(): Builder
    {
        $builder = clone $this->query;

        if ($this->after === null) {
            return $builder;
        }

        $builder->field($this->field);

        if ($this->isSortedDescending()) {
            $builder->lt($this->after);
        } else {
            $builder->gt($this->after);
        }

        return $builder;
    }

    private function isSortedDescending(): bool
    {
        $mapping = $this->query->getQuery()->getClass()->getFieldMapping($this->field);
        $sort    = $this->query->getQuery()->getQuery()['sort'] ?? [];

        if (! is_array($sort)) {
            return false;
        }

        return ($sort[$mapping['name']] ?? 1) === -1;
    }
}
