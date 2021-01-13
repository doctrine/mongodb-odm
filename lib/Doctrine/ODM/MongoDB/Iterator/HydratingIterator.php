<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Generator;
use Iterator;
use RuntimeException;
use Traversable;

/**
 * Iterator that wraps a traversable and hydrates results into objects
 *
 * @internal
 */
final class HydratingIterator implements Iterator
{
    /** @var Generator|null */
    private $iterator;

    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var ClassMetadata */
    private $class;

    /** @var array */
    private $unitOfWorkHints;

    public function __construct(Traversable $traversable, UnitOfWork $unitOfWork, ClassMetadata $class, array $unitOfWorkHints = [])
    {
        $this->iterator        = $this->wrapTraversable($traversable);
        $this->unitOfWork      = $unitOfWork;
        $this->class           = $class;
        $this->unitOfWorkHints = $unitOfWorkHints;
    }

    public function __destruct()
    {
        $this->iterator = null;
    }

    /**
     * @see http://php.net/iterator.current
     *
     * @return mixed
     */
    public function current()
    {
        return $this->hydrate($this->getIterator()->current());
    }

    /**
     * @see http://php.net/iterator.mixed
     *
     * @return mixed
     */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /**
     * @see http://php.net/iterator.next
     */
    public function next(): void
    {
        $this->getIterator()->next();
    }

    /**
     * @see http://php.net/iterator.rewind
     */
    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    /**
     * @see http://php.net/iterator.valid
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    private function getIterator(): Generator
    {
        if ($this->iterator === null) {
            throw new RuntimeException('Iterator has already been destroyed');
        }

        return $this->iterator;
    }

    private function hydrate($document)
    {
        return $document !== null ? $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints) : null;
    }

    private function wrapTraversable(Traversable $traversable): Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
