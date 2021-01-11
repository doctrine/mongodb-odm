<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Generator;
use LogicException;
use RuntimeException;
use Traversable;

use function iterator_to_array;
use function sprintf;

/**
 * Iterator for wrapping a Traversable/Cursor.
 *
 * @internal
 */
final class UnrewindableIterator implements Iterator
{
    /** @var Generator|null */
    private $iterator;

    /** @var bool */
    private $iteratorAdvanced = false;

    /**
     * Initialize the iterator. This effectively rewinds the Traversable and
     * the wrapping Generator, which will execute up to its first yield statement.
     * Additionally, this mimics behavior of the SPL iterators and allows users
     * to omit an explicit call to rewind() before using the other methods.
     */
    public function __construct(Traversable $iterator)
    {
        $this->iterator = $this->wrapTraversable($iterator);
        $this->iterator->key();
    }

    public function toArray(): array
    {
        $this->preventRewinding(__METHOD__);

        $toArray = function () {
            if (! $this->valid()) {
                return;
            }

            yield $this->key() => $this->current();
            yield from $this->getIterator();
        };

        return iterator_to_array($toArray());
    }

    /**
     * @see http://php.net/iterator.current
     *
     * @return mixed
     */
    public function current()
    {
        return $this->getIterator()->current();
    }

    /**
     * @see http://php.net/iterator.mixed
     *
     * @return mixed
     */
    public function key()
    {
        if ($this->iterator) {
            return $this->iterator->key();
        }

        return null;
    }

    /**
     * @see http://php.net/iterator.next
     */
    public function next(): void
    {
        if (! $this->iterator) {
            return;
        }

        $this->iterator->next();
    }

    /**
     * @see http://php.net/iterator.rewind
     */
    public function rewind(): void
    {
        $this->preventRewinding(__METHOD__);
    }

    /**
     * @see http://php.net/iterator.valid
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    private function preventRewinding(string $method): void
    {
        if ($this->iteratorAdvanced) {
            throw new LogicException(sprintf(
                'Cannot call %s for iterator that already yielded results',
                $method
            ));
        }
    }

    private function getIterator(): Generator
    {
        if ($this->iterator === null) {
            throw new RuntimeException('Iterator has already been destroyed');
        }

        return $this->iterator;
    }

    private function wrapTraversable(Traversable $traversable): Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;

            $this->iteratorAdvanced = true;
        }

        $this->iterator = null;
    }
}
