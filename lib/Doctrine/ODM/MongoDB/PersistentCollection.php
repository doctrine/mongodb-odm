<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Closure;

final class PersistentCollection implements Collection
{
    public function __construct(DocumentManager $dm, ClassMetadata $class, Collection $coll)
    {
        $this->_coll = $coll;
        $this->_dm = $dm;
        $this->_typeClass = $class;
    }

    public function first()
    {
        return $this->_coll->first();
    }

    public function last()
    {
        return $this->_coll->last();
    }

    public function remove($key)
    {
        return $this->_coll->remove($key);
    }

    public function removeElement($element)
    {
        return $this->_coll->removeElement($element);
    }

    public function containsKey($key)
    {
        return $this->_coll->containsKey($key);
    }

    public function contains($element)
    {
        return $this->_coll->contains($element);
    }

    public function exists(Closure $p)
    {
        return $this->_coll->exists($p);
    }

    public function indexOf($element)
    {

        return $this->_coll->indexOf($element);
    }

    public function get($key)
    {
        return $this->_coll->get($key);
    }

    public function getKeys()
    {
        return $this->_coll->getKeys();
    }

    public function getValues()
    {
        return $this->_coll->getValues();
    }

    public function count()
    {
        return $this->_coll->count();
    }

    public function set($key, $value)
    {
        $this->_coll->set($key, $value);
    }

    public function add($value)
    {
        $this->_coll->add($value);
        return true;
    }

    public function isEmpty()
    {
        return $this->_coll->isEmpty();
    }

    public function getIterator()
    {
        return $this->_coll->getIterator();
    }

    public function map(Closure $func)
    {
        return $this->_coll->map($func);
    }

    public function filter(Closure $p)
    {
        return $this->_coll->filter($p);
    }

    public function forAll(Closure $p)
    {
        return $this->_coll->forAll($p);
    }

    public function partition(Closure $p)
    {
        return $this->_coll->partition($p);
    }

    public function toArray()
    {
        return $this->_coll->toArray();
    }

    public function clear()
    {
        return $this->_coll->clear();
    }

    public function __sleep()
    {
        return array('_coll');
    }

    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    public function key()
    {
        return $this->_coll->key();
    }

    public function current()
    {
        return $this->_coll->current();
    }

    public function next()
    {
        return $this->_coll->next();
    }

    public function unwrap()
    {
        return $this->_coll;
    }
}