<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class MongoCollection
{
    private $_collection;
    private $_class;

    public function __construct(\MongoCollection $collection, ClassMetadata $class)
    {
        $this->_collection = $collection;
        $this->_class = $class;
    }

    public function getCollection()
    {
        return $this->_collection;
    }

    public function batchInsert(array &$a, array $options = array())
    {
        if ($this->_collection instanceof \MongoGridFS) {
            foreach ($a as $key => $array) {
                $this->saveFile($array);
                $a[$key] = $array;
            }
            return $a;
        }
        return $this->_collection->batchInsert($a, $options);
    }

    public function saveFile(array &$a)
    {
        $fileName = $this->_class->fieldMappings[$this->_class->file]['name'];
        $file = $a[$fileName];
        unset($a[$fileName]);
        if (file_exists($file)) {
            $this->_collection->chunks->remove(array('files_id' => $a['_id']));
            $id = $this->_collection->storeFile($file, $a);
        } else if (is_string($file)) {
            $this->_collection->chunks->remove(array('files_id' => $a['_id']));
            $id = $this->_collection->storeBytes($file, $a);
        }
        $file = $this->_collection->findOne(array('_id' => $id));
        $a = $file->file;
        $a[$fileName] = $file;
        return $a;
    }

    public function getDBRef(array $reference)
    {
        if ($this->_class->isFile()) {
            $ref = $this->_collection->getDBRef($reference);
            $file = $this->_collection->findOne(array('_id' => $ref['_id']));
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_collection->getDBRef($reference);
    }

    public function save(array &$a, array $options = array())
    {
        if ($this->_class->isFile()) {
            return $this->saveFile($a);
        }
        return $this->_collection->save($a, $options);
    }

    public function find(array $query = array(), array $fields = array())
    {
        return $this->_collection->find($query, $fields);
    }

    public function findOne(array $query = array(), array $fields = array())
    {
        if ($this->_collection instanceof \MongoGridFS) {
            $file = $this->_collection->findOne($query);
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_collection->findOne($query, $fields);
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->_collection, $method), $arguments);
    }
}