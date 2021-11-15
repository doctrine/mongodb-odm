<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Generator;
use Iterator;
use ReturnTypeWillChange;
use RuntimeException;
use Traversable;

/**
 * Iterator that wraps a traversable and hydrates results into objects
 *
 * @internal
 *
 * @psalm-import-type Hints from UnitOfWork
 *
 * @template TValue
 * @template TDocument of object
 * @template-implements Iterator<TValue>
 */
final class HydratingIterator implements Iterator
{
    /** @var Generator<mixed, TValue>|null */
    private $iterator;

    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var ClassMetadata<TDocument> */
    private $class;

    /**
     * @var array<int, mixed>
     * @psalm-var Hints
     */
    private $unitOfWorkHints;

    /**
     * @param Traversable<mixed, TValue> $traversable
     * @param ClassMetadata<TDocument>   $class
     * @psalm-param Hints $unitOfWorkHints
     */
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
     * @return TDocument|null
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->hydrate($this->getIterator()->current());
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
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

    /**
     * @return Generator<mixed, TValue>
     */
    private function getIterator(): Generator
    {
        if ($this->iterator === null) {
            throw new RuntimeException('Iterator has already been destroyed');
        }

        return $this->iterator;
    }

    /**
     * @param array<string, mixed>|null $document
     *
     * @return TDocument|null
     */
    private function hydrate(?array $document): ?object
    {
        return $document !== null ? $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints) : null;
    }

    /**
     * @param Traversable<mixed, TValue> $traversable
     *
     * @return Generator<mixed, TValue>
     */
    private function wrapTraversable(Traversable $traversable): Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
