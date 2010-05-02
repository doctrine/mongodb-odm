<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Hydrator;

class MongoCursor implements \Iterator
{
    private $_dm;
    private $_uow;
    private $_class;
    private $_mongoCursor;

    public function __construct(DocumentManager $dm, Hydrator $hydrator, ClassMetadata $class, \MongoCursor $mongoCursor)
    {
        $this->_dm = $dm;
        $this->_uow = $this->_dm->getUnitOfWork();
        $this->_hydrator = $hydrator;
        $this->_class = $class;
        $this->_mongoCursor = $mongoCursor;
    }

    public function current()
    {
        if ($this->_mongoCursor instanceof \MongoGridFSCursor) {
            $file = $this->_mongoCursor->current();
            $current = $file->file;
            $current[$this->_class->file] = $file;
        } else {
            $current = $this->_mongoCursor->current();
        }
        $document = $this->_uow->getOrCreateDocument($this->_class->name, $current, $this->_hydrator->getHints());
        return $document;
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
        return iterator_to_array($this);
    }

    public function __call($method, $arguments)
    {
        $return = call_user_func_array(array($this->_mongoCursor, $method), $arguments);
        if ($return === $this->_mongoCursor) {
            return $this;
        }
        return $return;
    }
}