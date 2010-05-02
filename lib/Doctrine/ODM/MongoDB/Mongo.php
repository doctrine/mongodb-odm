<?php

namespace Doctrine\ODM\MongoDB;

class Mongo
{
    private $_mongo;

    public function __construct($server = null, array $options = array())
    {
        if ($server instanceof \Mongo) {
            $this->_mongo = $server;
        } else if ($server !== null) {
            $this->_mongo = new \Mongo($server, $options);
        } else {
            $this->_mongo = new \Mongo();
        }
    }

    public function setMongo(\Mongo $mongo)
    {
        $this->_mongo = $mongo;
    }

    public function getMongo()
    {
        return $this->_mongo;
    }

    public function __get($key)
    {
        return $this->_mongo->$key;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->_mongo, $method), $arguments);
    }
}