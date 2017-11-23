<?php declare(strict_types = 1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;

final class PrimingIterator implements Iterator
{
    private $iterator;
    private $class;
    private $referencePrimer;
    private $primers;
    private $unitOfWorkHints;
    private $referencesPrimed = false;

    public function __construct(\Iterator $iterator, ClassMetadata $class, ReferencePrimer $referencePrimer, array $primers, array $unitOfWorkHints = [])
    {
        $this->iterator = $iterator;
        $this->class = $class;
        $this->referencePrimer = $referencePrimer;
        $this->primers = $primers;
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

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }

    private function primeReferences()
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
