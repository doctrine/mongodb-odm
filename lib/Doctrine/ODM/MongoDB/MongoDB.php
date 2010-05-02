<?php

namespace Doctrine\ODM\MongoDB;

class MongoDB
{
    private $_mongoDB;

    public function __construct(\MongoDB $mongoDB)
    {
        $this->_mongoDB = $mongoDB;
    }

    public function getMongoDB()
    {
        return $this->_mongoDB;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->_mongoDB, $method), $arguments);
    }
}