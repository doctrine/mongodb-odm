<?php declare(strict_types = 1);

namespace Doctrine\ODM\MongoDB\Iterator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Iterator that wraps a traversable and hydrates results into objects
 *
 * @internal
 */
final class HydratingIterator implements \Iterator
{
    private $iterator;
    private $unitOfWork;
    private $class;
    private $unitOfWorkHints;

    public function __construct(\Traversable $traversable, UnitOfWork $unitOfWork, ClassMetadata $class, array $unitOfWorkHints = [])
    {
        $this->iterator = $this->wrapTraversable($traversable);
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
        $this->unitOfWorkHints = $unitOfWorkHints;
    }

    /**
     * @see http://php.net/iterator.current
     * @return mixed
     */
    public function current()
    {
        return $this->hydrate($this->iterator->current());
    }

    /**
     * @see http://php.net/iterator.mixed
     * @return mixed
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * @see http://php.net/iterator.next
     * @return void
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * @see http://php.net/iterator.rewind
     * @return void
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * @see http://php.net/iterator.valid
     * @return boolean
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    private function hydrate($document)
    {
        return $document !== null ? $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints) : null;
    }

    private function wrapTraversable(\Traversable $traversable): \Generator
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
