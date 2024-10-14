<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\UnitOfWork;
use ReturnTypeWillChange;

use function is_callable;
use function iterator_to_array;

/**
 * @phpstan-import-type Hints from UnitOfWork
 * @template TValue
 * @template TDocument of object
 * @template-implements Iterator<TValue>
 */
final class PrimingIterator implements Iterator
{
    private bool $referencesPrimed = false;

    /**
     * @param \Iterator<mixed, TValue>          $iterator
     * @param ClassMetadata<TDocument>          $class
     * @param array<string, callable|true|null> $primers
     * @phpstan-param Hints $unitOfWorkHints
     */
    public function __construct(private \Iterator $iterator, private ClassMetadata $class, private ReferencePrimer $referencePrimer, private array $primers, private array $unitOfWorkHints = [])
    {
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /** @return TValue|null */
    #[ReturnTypeWillChange]
    public function current()
    {
        $this->primeReferences();

        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    /** @return mixed */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    private function primeReferences(): void
    {
        if ($this->referencesPrimed || empty($this->primers)) {
            return;
        }

        $this->referencesPrimed = true;

        foreach ($this->primers as $fieldName => $primer) {
            $primer = is_callable($primer) ? $primer : null;
            $this->referencePrimer->primeReferences($this->class, $this, $fieldName, $this->unitOfWorkHints, $primer);
        }

        $this->rewind();
    }
}
