<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use IteratorAggregate;

/**
 * @template-covariant TValue
 * @template-implements IteratorAggregate<mixed, TValue>
 */
abstract class Paginator implements IteratorAggregate
{
    /** @return Iterator<TValue> */
    abstract protected function getResultsForCurrentPage(): Iterator;

    /** @return Iterator<TValue> */
    final public function getIterator(): Iterator
    {
        return $this->getResultsForCurrentPage();
    }
}
