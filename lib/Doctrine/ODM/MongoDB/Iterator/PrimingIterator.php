<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\UnitOfWork;

use function is_callable;
use function iterator_to_array;

/**
 * @psalm-import-type Hints from UnitOfWork
 * @template TKey
 * @template TValue
 * @template TDocument of object
 * @template-implements Iterator<TKey, TValue>
 */
final class PrimingIterator implements Iterator
{
    /** @var \Iterator<TKey, TValue> */
    private $iterator;

    /** @var ClassMetadata<TDocument> */
    private $class;

    /** @var ReferencePrimer */
    private $referencePrimer;

    /** @var array<string, callable|null> */
    private $primers;

    /**
     * @var array<int, mixed>
     * @psalm-var Hints
     */
    private $unitOfWorkHints;

    /** @var bool */
    private $referencesPrimed = false;

    /**
     * @param \Iterator<TKey, TValue>      $iterator
     * @param ClassMetadata<TDocument>     $class
     * @param array<string, callable|null> $primers
     * @psalm-param Hints $unitOfWorkHints
     */
    public function __construct(\Iterator $iterator, ClassMetadata $class, ReferencePrimer $referencePrimer, array $primers, array $unitOfWorkHints = [])
    {
        $this->iterator        = $iterator;
        $this->class           = $class;
        $this->referencePrimer = $referencePrimer;
        $this->primers         = $primers;
        $this->unitOfWorkHints = $unitOfWorkHints;
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function current()
    {
        $this->primeReferences();

        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

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
