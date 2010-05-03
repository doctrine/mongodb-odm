<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class MongoCollection
{
    private $_mongoCollection;
    private $_class;

    public function __construct(\MongoCollection $mongoCollection, ClassMetadata $class)
    {
        $this->_mongoCollection = $mongoCollection;
        $this->_class = $class;
    }

    public function getMongoCollection()
    {
        return $this->_mongoCollection;
    }

    public function batchInsert(array &$a, array $options = array())
    {
        if ($this->_mongoCollection instanceof \MongoGridFS) {
            foreach ($a as $key => $array) {
                $this->saveFile($array);
                $a[$key] = $array;
            }
            return $a;
        }
        return $this->_mongoCollection->batchInsert($a, $options);
    }

    public function saveFile(array &$a)
    {
        $fileName = $this->_class->fieldMappings[$this->_class->file]['fieldName'];
        $file = $a[$fileName];
        unset($a[$fileName]);
        if ($file instanceof \MongoGridFSFile) {
            $id = $a['_id'];
            unset($a['_id']);
            $set = array('$set' => $a);
            $this->_mongoCollection->update(array('_id' => $id), $set);
        } else {
            if (isset($a['_id'])) {
                $this->_mongoCollection->chunks->remove(array('files_id' => $a['_id']));
            }
            if (file_exists($file)) {
                $id = $this->_mongoCollection->storeFile($file, $a);
            } else if (is_string($file)) {
                $id = $this->_mongoCollection->storeBytes($file, $a);
            }
            $file = $this->_mongoCollection->findOne(array('_id' => $id));
        }
        $a = $file->file;
        $a[$this->_class->file] = $file;
        return $a;
    }

    public function getDBRef(array $reference)
    {
        if ($this->_class->isFile()) {
            $ref = $this->_mongoCollection->getDBRef($reference);
            $file = $this->_mongoCollection->findOne(array('_id' => $ref['_id']));
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_mongoCollection->getDBRef($reference);
    }

    public function save(array &$a, array $options = array())
    {
        if ($this->_class->isFile()) {
            return $this->saveFile($a);
        }
        return $this->_mongoCollection->save($a, $options);
    }

    public function find(array $query = array(), array $fields = array())
    {
        return $this->_mongoCollection->find($query, $fields);
    }

    public function findOne(array $query = array(), array $fields = array())
    {
        if ($this->_mongoCollection instanceof \MongoGridFS) {
            $file = $this->_mongoCollection->findOne($query);
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_mongoCollection->findOne($query, $fields);
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this->_mongoCollection, $method)) {
            return call_user_func_array(array($this->_mongoCollection, $method), $arguments);
        }
    }
}