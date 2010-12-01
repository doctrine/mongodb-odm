<?php

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\MongoDB\Query\AbstractQuery;
use Doctrine\MongoDB\Iterator;
use Doctrine\MongoDB\Query\QueryInterface;
use Doctrine\MongoDB\Cursor as BaseCursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Cursor;

class Query implements QueryInterface, Iterator
{
    private $query;
    private $dm;
    private $class;
    private $iterator;
    private $hydrate = true;

    public function __construct(AbstractQuery $query, DocumentManager $dm, ClassMetadata $class, $hydrate)
    {
        $this->query = $query;
        $this->dm = $dm;
        $this->class = $class;
        $this->hydrate = $hydrate;
    }

    public function debug($name = null)
    {
        return $this->query->debug($name);
    }

    public function execute(array $options = array())
    {
        $uow = $this->dm->getUnitOfWork();

        $results = $this->query->execute($options);

        // Convert the regular mongodb cursor to the odm cursor
        if ($results instanceof BaseCursor) {
            $cursor = $results->getMongoCursor();
            $results = new Cursor($cursor, $this->dm->getUnitOfWork(), $this->class);
            $results->hydrate($this->hydrate);
        }

        // GeoLocationFindQuery just returns an instance of ArrayIterator so we have to
        // iterator over it and hydrate each object.
        if ($this->query instanceof \Doctrine\MongoDB\Query\GeoLocationFindQuery) {
            if ($this->hydrate) {
                foreach ($results as $key => $result) {
                    $document = $result['obj'];
                    if ($this->class->distance) {
                        $document[$this->class->distance] = $result['dis'];
                    }
                    $results[$key] = $uow->getOrCreateDocument($this->class->name, $document);
                }
                $results->reset();
            }
        }

        // Convert a single document array to a document object
        if (is_array($results) && isset($results['_id']) && $this->hydrate) {
            $results = $uow->getOrCreateDocument($this->class->name, $results);
        }

        return $results;
    }

    public function getIterator(array $options = array())
    {
        if ($this->iterator === null) {
            $iterator = $this->execute($options);
            if ($iterator !== null && !$iterator instanceof Iterator) {
                throw new \BadMethodCallException('Query execution did not return an iterator. This query may not support returning iterators. ');
            }
            $this->iterator = $iterator;
        }
        return $this->iterator;
    }

    /**
     * Count the number of results for this query.
     *
     * @param bool $all
     * @return integer $count
     */
    public function count($all = false)
    {
        return $this->getIterator()->count($all);
    }

    /**
     * Execute the query and get a single result
     *
     * @return object $document  The single document.
     */
    public function getSingleResult(array $options = array())
    {
        return $this->getIterator($options)->getSingleResult();
    }

    /**
     * Iterator over the query using the Cursor.
     *
     * @return Cursor $cursor
     */
    public function iterate()
    {
        return $this->getIterator();
    }

    /** @inheritDoc */
    public function first()
    {
        return $this->getIterator()->first();
    }

    /** @inheritDoc */
    public function last()
    {
        return $this->getIterator()->last();
    }

    /** @inheritDoc */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /** @inheritDoc */
    public function next()
    {
        return $this->getIterator()->next();
    }

    /** @inheritDoc */
    public function current()
    {
        return $this->getIterator()->current();
    }

    /** @inheritDoc */
    public function rewind()
    {
        return $this->getIterator()->rewind();
    }

    /** @inheritDoc */
    public function valid()
    {
        return $this->getIterator()->valid();
    }

    /** @inheritDoc */
    public function toArray()
    {
        return $this->getIterator()->toArray();
    }
}