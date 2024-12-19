<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Countable;
use Iterator as SPLIterator;
use IteratorIterator;
use ReturnTypeWillChange;
use RuntimeException;
use Traversable;

use function count;
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
 *
 * @template TValue
 * @template-implements Iterator<TValue>
 */
final class CachingIterator implements Countable, Iterator
{
    /** @var array<mixed, TValue> */
    private array $items = [];

    /** @var SPLIterator<mixed, TValue>|null */
    private ?SPLIterator $iterator;

    private bool $iteratorAdvanced = false;

    /**
     * Initialize the iterator and stores the first item in the cache. This
     * effectively rewinds the Traversable and the wrapping Generator, which
     * will execute up to its first yield statement. Additionally, this mimics
     * behavior of the SPL iterators and allows users to omit an explicit call
     * to rewind() before using the other methods.
     *
     * @param Traversable<mixed, TValue> $iterator
     */
    public function __construct(Traversable $iterator)
    {
        $this->iterator = new IteratorIterator($iterator);
        $this->iterator->rewind();
        $this->storeCurrentItem();
    }

    /** @see https://php.net/countable.count */
    public function count(): int
    {
        $currentKey = key($this->items);
        $this->exhaustIterator();
        for (reset($this->items); key($this->items) !== $currentKey; next($this->items));

        return count($this->items);
    }

    public function __destruct()
    {
        $this->iterator = null;
    }

    public function toArray(): array
    {
        $this->exhaustIterator();

        return $this->items;
    }

    /** @return TValue|false */
    #[ReturnTypeWillChange]
    public function current()
    {
        return current($this->items);
    }

    /** @return mixed */
    #[ReturnTypeWillChange]
    public function key()
    {
        return key($this->items);
    }

    /** @see http://php.net/iterator.next */
    public function next(): void
    {
        if ($this->iterator !== null) {
            $this->iterator->next();
            $this->storeCurrentItem();
            $this->iteratorAdvanced = true;
        }

        next($this->items);
    }

    /** @see http://php.net/iterator.rewind */
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

    /** @see http://php.net/iterator.valid */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Ensures that the inner iterator is fully consumed and cached.
     */
    private function exhaustIterator(): void
    {
        while ($this->iterator !== null) {
            $this->next();
        }
    }

    /** @return SPLIterator<mixed, TValue> */
    private function getIterator(): SPLIterator
    {
        if ($this->iterator === null) {
            throw new RuntimeException('Iterator has already been destroyed');
        }

        return $this->iterator;
    }

    /**
     * Stores the current item in the cache.
     */
    private function storeCurrentItem(): void
    {
        $key = $this->iterator->key();

        if ($key === null) {
            $this->iterator = null;
        } else {
            $this->items[$key] = $this->getIterator()->current();
        }
    }
}
