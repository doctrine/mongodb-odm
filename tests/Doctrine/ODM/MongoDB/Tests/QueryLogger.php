<?php

namespace Doctrine\ODM\MongoDB\Tests;

class QueryLogger implements \Countable
{
    private $queries = array();

    /**
     * Log a query.
     *
     * @param array $query
     */
    public function __invoke(array $query)
    {
        $this->queries[] = $query;
    }

    /**
     * Clears the logged queries.
     */
    public function clear()
    {
        $this->queries = array();
    }

    /**
     * Returns the number of logged queries.
     *
     * @see php.net/countable.count
     * @return integer
     */
    public function count()
    {
        return count($this->queries);
    }

    /**
     * Returns the logged queries.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->queries;
    }
}
