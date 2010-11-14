<?php

namespace Doctrine\ODM\MongoDB;

class MongoArrayIterator implements MongoIterator
{
    private $elements;

    public function __construct(array $elements = array())
    {
        $this->elements = $elements;
    }

    public function first()
    {
        return reset($this->elements);
    }

    public function last()
    {
        return end($this->elements);
    }

    public function key()
    {
        return key($this->elements);
    }

    public function next()
    {
        next($this->elements);
    }

    public function current()
    {
        return current($this->elements);
    }

    public function count()
    {
        return count($this->elements);
    }

    public function rewind()
    {
        reset($this->elements);
    }

    public function valid()
    {
        return current($this->elements) !== false;
    }

    public function toArray()
    {
        return $this->elements;
    }
}