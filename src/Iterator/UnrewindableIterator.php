<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Iterator as SPLIterator;
use IteratorIterator;
use LogicException;
use RuntimeException;
use Traversable;

use function iterator_to_array;
use function sprintf;

/**
 * Iterator for wrapping a Traversable/Cursor.
 *
 * @internal
 *
 * @template TValue
 * @template-implements Iterator<TValue>
 */
final class UnrewindableIterator implements Iterator
{
    /** @var SPLIterator<mixed, TValue>|null */
    private ?SPLIterator $iterator;

    private bool $iteratorAdvanced = false;

    /**
     * Initialize the iterator. This effectively rewinds the Traversable.
     * This mimics behavior of the SPL iterators and allows users to omit an
     * explicit call to rewind() before using the other methods.
     *
     * @param Traversable<mixed, TValue> $iterator
     */
    public function __construct(Traversable $iterator)
    {
        $this->iterator = new IteratorIterator($iterator);
        $this->iterator->rewind();
    }

    public function toArray(): array
    {
        $this->preventRewinding(__METHOD__);

        try {
            return iterator_to_array($this->getIterator());
        } finally {
            $this->iteratorAdvanced = true;
            $this->iterator         = null;
        }
    }

    /** @return TValue|null */
    public function current(): mixed
    {
        return $this->getIterator()->current();
    }

    public function key(): mixed
    {
        return $this->iterator?->key();
    }

    public function next(): void
    {
        if (! $this->iterator) {
            return;
        }

        $this->iterator->next();
        $this->iteratorAdvanced = true;

        if ($this->iterator->valid()) {
            return;
        }

        $this->iterator = null;
    }

    public function rewind(): void
    {
        $this->preventRewinding(__METHOD__);
    }

    public function valid(): bool
    {
        return $this->key() !== null;
    }

    private function preventRewinding(string $method): void
    {
        if ($this->iteratorAdvanced) {
            throw new LogicException(sprintf(
                'Cannot call %s for iterator that already yielded results',
                $method,
            ));
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
}
