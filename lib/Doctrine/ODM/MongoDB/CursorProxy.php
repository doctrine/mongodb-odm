<?php

namespace Doctrine\ODM\MongoDB;

use MongoCursor,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Hydrator;

class CursorProxy implements \Iterator
{
    private $_em;
    private $_uow;
    private $_class;
    private $_mongoCursor;

    public function __construct(EntityManager $em, Hydrator $hydrator, ClassMetadata $class, MongoCursor $mongoCursor)
    {
        $this->_em = $em;
        $this->_uow = $this->_em->getUnitOfWork();
        $this->_hydrator = $hydrator;
        $this->_class = $class;
        $this->_mongoCursor = $mongoCursor;
    }

    public function current()
    {
        $current = $this->_mongoCursor->current();
        $entity = $this->_uow->getOrCreateEntity($this->_class->name, (array) $current, $this->_hydrator->getHints());
        return $entity;
    }

    public function next()
    {
        return $this->_mongoCursor->next();
    }

    public function key()
    {
        return $this->_mongoCursor->key();
    }

    public function valid()
    {
        return $this->_mongoCursor->valid();
    }

    public function rewind()
    {
        return $this->_mongoCursor->rewind();
    }

    public function getResults()
    {
        $results = array();
        foreach ($this as $entity) {
            $results[] = $entity;
        }
        return $results;
    }

    public function __call($method, $arguments)
    {
        $return = call_user_func_array(array($this->_mongoCursor, $method), $arguments);
        if ($return === $this->_mongoCursor) {
            return $this;
        } else {
            return $return;
        }
    }
}