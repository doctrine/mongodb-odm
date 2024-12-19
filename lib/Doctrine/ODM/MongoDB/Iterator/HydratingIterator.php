<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Iterator;
use IteratorIterator;
use ReturnTypeWillChange;
use RuntimeException;
use Traversable;

/**
 * Iterator that wraps a traversable and hydrates results into objects
 *
 * @internal
 *
 * @phpstan-import-type Hints from UnitOfWork
 *
 * @template TDocument of object
 * @template-implements Iterator<TDocument>
 */
final class HydratingIterator implements Iterator
{
    /** @var Iterator<mixed, array<string, mixed>>|null */
    private ?Iterator $iterator;

    /**
     * @param Traversable<mixed, array<string, mixed>> $traversable
     * @param ClassMetadata<TDocument>                 $class
     * @phpstan-param Hints $unitOfWorkHints
     */
    public function __construct(Traversable $traversable, private UnitOfWork $unitOfWork, private ClassMetadata $class, private array $unitOfWorkHints = [])
    {
        $this->iterator = new IteratorIterator($traversable);
        $this->iterator->rewind();
    }

    public function __destruct()
    {
        $this->iterator = null;
    }

    /** @return TDocument|null */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->hydrate($this->getIterator()->current());
    }

    /** @return mixed */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->getIterator()->key();
    }

    public function next(): void
    {
        $this->getIterator()->next();
    }

    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /** @return Iterator<mixed, array<string, mixed>> */
    private function getIterator(): Iterator
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
}
