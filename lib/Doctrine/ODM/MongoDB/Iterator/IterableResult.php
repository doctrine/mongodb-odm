<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use BadMethodCallException;
use IteratorAggregate;

interface IterableResult extends IteratorAggregate
{
    /**
     * Executes the operation and returns a result if available; null otherwise
     */
    public function execute(): mixed;

    /**
     * Executes the operation and returns a result iterator. If the operation
     * did not yield an iterator, this method will throw
     *
     * @throws BadMethodCallException if the operation did not yield an iterator.
     */
    public function getIterator(): Iterator;

    /**
     * Returns the first result only from the operation
     *
     * Note that the behaviour of fetching the first result is dependent on the
     * implementation. A separate operation might be involved.
     */
    public function getSingleResult(): mixed;
}
