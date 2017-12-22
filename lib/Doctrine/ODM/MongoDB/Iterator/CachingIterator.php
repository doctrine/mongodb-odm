<?php declare(strict_types = 1);

namespace Doctrine\ODM\MongoDB\Iterator;

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
    private $items = [];
    private $iterator;
    private $iteratorAdvanced = false;
    private $iteratorExhausted = false;

    /**
     * Constructor.
     *
     * Initialize the iterator and stores the first item in the cache. This
     * effectively rewinds the Traversable and the wrapping Generator, which
     * will execute up to its first yield statement. Additionally, this mimics
     * behavior of the SPL iterators and allows users to omit an explicit call
     * to rewind() before using the other methods.
     *
     * @param Traversable $iterator
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
     * @return void
     */
    public function next()
    {
        if ( ! $this->iteratorExhausted) {
            $this->iterator->next();
            $this->storeCurrentItem();
        }

        next($this->items);
    }

    /**
     * @see http://php.net/iterator.rewind
     * @return void
     */
    public function rewind()
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
     * @return boolean
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * Ensures that the inner iterator is fully consumed and cached.
     */
    private function exhaustIterator()
    {
        while ( ! $this->iteratorExhausted) {
            $this->next();
        }
    }

    /**
     * Stores the current item in the cache.
     */
    private function storeCurrentItem()
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
