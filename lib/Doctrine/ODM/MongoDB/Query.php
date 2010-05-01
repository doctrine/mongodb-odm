<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Hydrator;

class Query
{
    private $_dm;
    private $_className;
    private $_class;
    private $_select = array();
    private $_where = array();
    private $_sort = array();
    private $_limit = null;
    private $_skip = null;

    const HINT_REFRESH = 1;

    public function __construct(DocumentManager $dm, $className = null)
    {
        $this->_dm = $dm;
        $this->_hydrator = $dm->getHydrator();
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
    }

    public function getDocumentManager()
    {
        return $this->_dm;
    }

    public function hint($hint)
    {
        $this->_hydrator->hint($hint);
        return $this;
    }

    public function refresh()
    {
        $this->_hydrator->hint(self::HINT_REFRESH);
        return $this;
    }

    public function from($className)
    {
        $this->_className = $className;
        $this->_class = $this->_dm->getClassMetadata($className);
        return $this;
    }

    public function loadAssociation($fieldName)
    {
        $this->_hydrator->hint('load_association_' . $fieldName);
        return $this;
    }

    public function select($fieldName = null)
    {
        $this->_select = func_get_args();
        return $this;
    }

    public function addSelect($fieldName)
    {
        $this->_select[] = $fieldName;
        return $this;
    }

    public function where($fieldName, $value)
    {
        $this->_where = array();
        $this->addWhere($fieldName, $value);
        return $this;
    }

    public function addWhere($fieldName, $value)
    {
        if ($fieldName === $this->_class->identifier) {
            $fieldName = '_id';
            $value = new \MongoId($value);
        }
        $this->_where[$fieldName] = $value;
        return $this;
    }

    public function whereIn($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$in' => (array) $values));
    }

    public function whereNotIn($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$nin' => (array) $values));
    }

    public function whereNotEqual($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$ne' => $value));
    }

    public function whereGt($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$gt' => $value));
    }

    public function whereGte($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$gte' => $value));
    }

    public function whereLt($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$lt' => $value));
    }

    public function whereLte($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$lte' => $value));
    }

    public function whereRange($fieldName, $start, $end)
    {
        return $this->addWhere($fieldName, array('$gt' => $start, '$lt' => $end));
    }

    public function whereSize($fieldName, $size)
    {
        return $this->addWhere($fieldName, array('$size' => $size));
    }

    public function whereExists($fieldName, $bool)
    {
        return $this->addWhere($fieldName, array('$exists' => $bool));
    }

    public function whereType($fieldName, $type)
    {
        return $this->addWhere($fieldName, array('$type' => $type));
    }

    public function whereAll($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$all' => (array) $values));
    }

    public function whereMod($fieldName, $mod)
    {
        return $this->addWhere($fieldName, array('$mod' => $mod));
    }

    public function sort($fieldName, $order)
    {
        $this->_sort = array();
        $this->_sort[$fieldName] = strtolower($order) === 'asc' ? 1 : -1;
        return $this;
    }

    public function addSort($fieldName, $order)
    {
        $this->_sort[$fieldName] = strtolower($order) === 'asc' ? 1 : -1;
        return $this;
    }

    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function skip($skip)
    {
        $this->_skip = $skip;
        return $this;
    }

    public function execute()
    {
        return $this->iterate()->getResults();
    }

    public function getSingleResult()
    {
        $result = $this->execute();
        return array_shift($result);
    }

    public function iterate()
    {
        $cursorProxy = $this->_dm->find($this->_className, $this->_where, $this->_select);
        $cursorProxy->limit($this->_limit);
        $cursorProxy->skip($this->_skip);
        $cursorProxy->sort($this->_sort);
        return $cursorProxy;
    }
}