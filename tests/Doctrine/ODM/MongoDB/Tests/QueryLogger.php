<?php

namespace Doctrine\ODM\MongoDB\Tests;

class QueryLogger
{
    private $queries = array();

    public function count()
    {
        return count($this->queries);
    }

    public function countAndReset()
    {
        $cnt = $this->count();
        $this->reset();
        return $cnt;
    }

    public function log($q)
    {
        $this->queries[] = $q;
    }

    public function getAll()
    {
        return $this->queries;
    }

    public function reset()
    {
        $this->queries = array();
    }
}
