<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use function current;
use function key;
use function next;
use function reset;

/**
 * Iterator for wrapping a Traversable and caching its results.
 *
 * By caching results, this iterators allows a Traversable to be counted and
 * rewound multiple times, even if the wrapped object does not natively support
 * those operations (e.g. MongoDB\Driver\Cursor).
 *
 * @internal
 */
final class CachingIterator implements Iterator
{
    /** @var array */
    private $items = [];

    /** @var \Generator */
    private $iterator;

    /** @var bool */
    private $iteratorAdvanced = false;

    /** @var bool */
    private $iteratorExhausted = false;

    /**
     *
     *
     * Initialize the iterator and stores the first item in the cache. This
     * effectively rewinds the Traversable and the wrapping Generator, which
     * will execute up to its first yield statement. Additionally, this mimics
     * behavior of the SPL iterators and allows users to omit an explicit call
     * to rewind() before using the other methods.
     *
     */
    public function __construct(\Traversable $iterator)
    {
        $this->iterator = $this->wrapTraversable($iterator);
        $this->storeCurrentItem();
    }

    public function toArray(): array
    {
        $this->exhaustIterator();

        return $this->items;
    }

    /**
     * @see http://php.net/iterator.current
     * @return mixed
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * @see http://php.net/iterator.mixed
     * @return mixed
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * @see http://php.net/iterator.next
     */
    public function next(): void
    {
        if (! $this->iteratorExhausted) {
            $this->iterator->next();
            $this->storeCurrentItem();
        }

        next($this->items);
    }

    /**
     * @see http://php.net/iterator.rewind
     */
    public function rewind(): void
    {
        /* If the iterator has advanced, exhaust it now so that future iteration
         * can rely on the cache.
         */
        if ($this->iteratorAdvanced) {
            $this->exhaustIterator();
        }

        reset($this->items);
    }

    /**
     *
     * @see http://php.net/iterator.valid
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Ensures that the inner iterator is fully consumed and cached.
     */
    private function exhaustIterator(): void
    {
        while (! $this->iteratorExhausted) {
            $this->next();
        }
    }

    /**
     * Stores the current item in the cache.
     */
    private function storeCurrentItem(): void
    {
        $key = $this->iterator->key();

        if ($key === null) {
            return;
        }

        $this->items[$key] = $this->iterator->current();
    }

    private function wrapTraversable(\Traversable $traversable): \Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
            $this->iteratorAdvanced = true;
        }

        $this->iteratorExhausted = true;
    }
}
