<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Hydrator;

/**
 * Query object that represents a query using a documents MongoCollection::find()
 * method. Offers a fluent chainable interface similar to the Doctrine ORM.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 4930 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Query
{
    const TYPE_FIND   = 1;
    const TYPE_UPDATE = 2;
    const TYPE_REMOVE = 3;
    const TYPE_GROUP  = 4;

    /** The DocumentManager instance for this query */
    private $_dm;

    /** The Document class name being queried */
    private $_className;

    /** The ClassMetadata instance for the class being queried */
    private $_class;

    /** Array of fields to select */
    private $_select = array();

    /** Array of criteria to query for */
    private $_where = array();

    /** Array to pass to MongoCollection::update() 2nd argument */
    private $_newObj = array();

    /** Array of sort options */
    private $_sort = array();

    /** Limit number of records */
    private $_limit = null;

    /** Skip a specified number of records (offset) */
    private $_skip = null;

    /** Group information. */
    private $_group = array();

    /** Pass hints to the MongoCursor */
    private $_hints = array();

    /** Pass immortal to cursor */
    private $_immortal = false;

    /** Pass snapshot to cursor */
    private $_snapshot = false;

    /** Pass slaveOkaye to cursor */
    private $_slaveOkay = false;

    /** Whether or not to try and hydrate the returned data */
    private $_hydrate = true;

    /** Map reduce information */
    private $_mapReduce = array();

    /** The type of query */
    private $_type = self::TYPE_FIND;

    /** Refresh hint */
    const HINT_REFRESH = 1;

    /**
     * Create a new MongoDB Query.
     *
     * @param DocumentManager $dm
     * @param string $className
     */
    public function __construct(DocumentManager $dm, $className = null)
    {
        $this->_dm = $dm;
        $this->_hydrator = $dm->getHydrator();
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
    }

    /**
     * Returns the DocumentManager instance for this query.
     *
     * @return Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function getDocumentManager()
    {
        return $this->_dm;
    }

    /**
     * Get the type of this query.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Whether or not to hydrate the data into objects or to return the raw results
     * from mongo.
     *
     * @param boolean $bool
     */
    public function hydrate($bool)
    {
        $this->_hydrate = $bool;
        return $this;
    }

    /**
     * Set slave okaye.
     *
     * @param bool $bool
     * @return Query
     */
    public function slaveOkay($bool = true)
    {
        $this->_slaveOkay = $bool;
        return $this;
    }

    /**
     * Set snapshot.
     *
     * @param bool $bool
     * @return Query
     */
    public function snapshot($bool = true)
    {
        $this->_snapshot = $bool;
        return $this;
    }

    /**
     * Set immortal.
     *
     * @param bool $bool
     * @return Query
     */
    public function immortal($bool = true)
    {
        $this->_immortal = $bool;
        return $this;
    }

    /**
     * Pass a hint to the MongoCursor
     *
     * @param string $keyPattern
     * @return Query
     */
    public function hint($keyPattern)
    {
        $this->_hints[] = $keyPattern;
        return $this;
    }

    /**
     * Set the Document class being queried.
     *
     * @param string $className The Document class being queried.
     * @return Query
     */
    public function from($className)
    {
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
        $this->_type = self::TYPE_FIND;
        return $this;
    }

    /**
     * Proxy method to from() to match mongo naming.
     *
     * @param string $className
     * @return Query
     */
    public function find($className = null)
    {
        return $this->from($className);
    }

    /**
     * Sets the query as an update query for the given class name or changes
     * the type for the current class.
     *
     * @param string $className
     * @return Query
     */
    public function update($className = null)
    {
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
        $this->_type = self::TYPE_UPDATE;
        return $this;
    }

    /**
     * Sets the query as a remove query for the given class name or changes
     * the type for the current class.
     *
     * @param string $className
     * @return Query
     */
    public function remove($className = null)
    {
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
        $this->_type = self::TYPE_REMOVE;
        return $this;
    }

    /**
     * Perform an operation similar to SQL's GROUP BY command
     *
     * @param array $keys 
     * @param array $initial 
     * @param string $reduce 
     * @param array $condition 
     * @return Query
     */
    public function group($keys, array $initial)
    {
        $this->_group = array(
            'keys' => $keys,
            'initial' => $initial
        );
        $this->_type = self::TYPE_GROUP;
        return $this;
    }

    /**
     * The fields to select.
     *
     * @param string $fieldName
     * @return Query
     */
    public function select($fieldName = null)
    {
        $this->_select = func_get_args();
        return $this;
    }

    /**
     * Add a new field to select.
     *
     * @param string $fieldName
     * @return Query
     */
    public function addSelect($fieldName = null)
    {
        $select = func_get_args();
        foreach ($select as $fieldName) {
            $this->_select[] = $fieldName;
        }
        return $this;
    }

    /**
     * Add a new where criteria erasing all old criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function where($fieldName, $value)
    {
        $this->_where = array();
        $this->addWhere($fieldName, $value);
        return $this;
    }

    /**
     * Add a new where criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function addWhere($fieldName, $value)
    {
        if ($fieldName === $this->_class->identifier) {
            $fieldName = '_id';
            $value = new \MongoId($value);
        }
        if (isset($this->_where[$fieldName])) {
            $this->_where[$fieldName] = array_merge($this->_where[$fieldName], $value);
        } else {
            $this->_where[$fieldName] = $value;
        }
        return $this;
    }

    /**
     * Add a new where in criteria.
     *
     * @param string $fieldName
     * @param mixed $values
     * @return Query
     */
    public function whereIn($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$in' => (array) $values));
    }

    /**
     * Add where not in criteria.
     *
     * @param string $fieldName
     * @param mixed $values
     * @return Query
     */
    public function whereNotIn($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$nin' => (array) $values));
    }

    /**
     * Add where not equal criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function whereNotEqual($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$ne' => $value));
    }

    /**
     * Add where greater than criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function whereGt($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$gt' => $value));
    }

    /**
     * Add where greater than or equal to criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function whereGte($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$gte' => $value));
    }

    /**
     * Add where less than criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function whereLt($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$lt' => $value));
    }

    /**
     * Add where less than or equal to criteria.
     *
     * @param string $fieldName
     * @param string $value
     * @return Query
     */
    public function whereLte($fieldName, $value)
    {
        return $this->addWhere($fieldName, array('$lte' => $value));
    }

    /**
     * Add where range criteria.
     *
     * @param string $fieldName
     * @param string $start
     * @param string $end
     * @return Query
     */
    public function whereRange($fieldName, $start, $end)
    {
        return $this->addWhere($fieldName, array('$gt' => $start, '$lt' => $end));
    }

    /**
     * Add where size criteria.
     *
     * @param string $fieldName
     * @param string $size
     * @return Query
     */
    public function whereSize($fieldName, $size)
    {
        return $this->addWhere($fieldName, array('$size' => $size));
    }

    /**
     * Add where exists criteria.
     *
     * @param string $fieldName
     * @param string $bool
     * @return Query
     */
    public function whereExists($fieldName, $bool)
    {
        return $this->addWhere($fieldName, array('$exists' => $bool));
    }

    /**
     * Add where type criteria.
     *
     * @param string $fieldName
     * @param string $type
     * @return Query
     */
    public function whereType($fieldName, $type)
    {
        $map = array(
            'double' => 1,
            'string' => 2,
            'embedded' => 3,
            'array' => 4,
            'binary' => 5,
            'undefined' => 6,
            'objectid' => 7,
            'boolean' => 8,
            'date' => 9,
            'null' => 10,
            'regex' => 11,
            'dbpointer' => 12,
            'jscode' => 13,
            'symbol' => 14,
            'jscode_w_s' => 15,
            'timestamp' => 16,
            'integer64' => 17,
            'minkey' => 255,
            'maxkey' => 127
        );
        if (is_string($type) && isset($map[$type])) {
            $type = $map[$type];
        }
        return $this->addWhere($fieldName, array('$type' => $type));
    }

    /**
     * Add where all criteria.
     *
     * @param string $fieldName
     * @param mixed $values
     * @return Query
     */
    public function whereAll($fieldName, $values)
    {
        return $this->addWhere($fieldName, array('$all' => (array) $values));
    }

    /**
     * Add where mod criteria.
     *
     * @param string $fieldName
     * @param string $mod
     * @return Query
     */
    public function whereMod($fieldName, $mod)
    {
        return $this->addWhere($fieldName, array('$mod' => $mod));
    }

    /**
     * Set sort and erase all old sorts.
     *
     * @param string $fieldName
     * @param string $order
     * @return Query
     */
    public function sort($fieldName, $order)
    {
        $this->_sort = array();
        $this->addSort($fieldName, $order);
        return $this;
    }

    /**
     * Add a new sort order.
     *
     * @param string $fieldName
     * @param string $order
     * @return Query
     */
    public function addSort($fieldName, $order)
    {
        $this->_sort[$fieldName] = strtolower($order) === 'asc' ? 1 : -1;
        return $this;
    }

    /**
     * Set the Document limit for the MongoCursor
     *
     * @param string $limit
     * @return Query
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Set the number of Documents to skip for the MongoCursor
     *
     * @param string $skip
     * @return Query
     */
    public function skip($skip)
    {
        $this->_skip = $skip;
        return $this;
    }

    /**
     * Specify a map reduce operation for this query.
     *
     * @param mixed $map
     * @param mixed $reduce
     * @param array $options
     * @return Query
     */
    public function mapReduce($map, $reduce, array $options = array())
    {
        $this->_mapReduce = array(
            'map' => $map,
            'reduce' => $reduce,
            'options' => $options
        );
        return $this;
    }

    /**
     * Specify a map operation for this query.
     *
     * @param string $map
     * @return Query
     */
    public function map($map)
    {
        $this->_mapReduce['map'] = $map;
        return $this;
    }

    /**
     * Specify a reduce operation for this query.
     *
     * @param string $reduce
     * @return Query
     */
    public function reduce($reduce)
    {
        $this->_mapReduce['reduce'] = $reduce;
        return $this;
    }

    /**
     * Specify the map reduce array of options for this query.
     *
     * @param array $options
     * @return Query
     */
    public function mapReduceOptions(array $options)
    {
        $this->_mapReduce['options'] = $options;
        return $this;
    }

    /**
     * Set field to value.
     *
     * @param string $name
     * @param mixed $value
     * @param boolean $atomic
     * @return Query
     */
    public function set($name, $value, $atomic = true)
    {
        if ($atomic === true) {
            $this->_newObj['$set'][$name] = $value;
        } else {
            $this->_newObj[$name] = $value;
        }
        return $this;
    }

    /**
     * Set the $newObj array
     *
     * @param array $newObj
     */
    public function setNewObj($newObj)
    {
        $this->_newObj = $newObj;
        return $this;
    }

    /**
     * Increment field by the number value if field is present in the document,
     * otherwise sets field to the number value.
     *
     * @param string $name
     * @param integer $value
     * @return Query
     */
    public function inc($name, $value)
    {
        $this->_newObj['$inc'][$name] = $value;
        return $this;
    }

    /**
     * Deletes a given field.
     *
     * @param string $field
     * @return Query
     */
    public function unsetField($field)
    {
        $this->_newObj['$unset'][$field] = 1;
        return $this;
    }

    /**
     * Appends value to field, if field is an existing array, otherwise sets
     * field to the array [value] if field is not present. If field is present
     * but is not an array, an error condition is raised.
     *
     * @param string $field
     * @param mixed $value
     * @return Query
     */
    public function push($field, $value)
    {
        $this->_newObj['$push'][$field] = $value;
        return $this;
    }

    /**
     * Appends each value in valueArray to field, if field is an existing
     * array, otherwise sets field to the array valueArray if field is not
     * present. If field is present but is not an array, an error condition is
     * raised.
     *
     * @param string $field
     * @param array $valueArray
     * @return Query
     */
    public function pushAll($field, array $valueArray)
    {
        $this->_newObj['$pushAll'][$field] = $valueArray;
        return $this;
    }

    /**
     * Adds value to the array only if its not in the array already.
     *
     * @param string $field
     * @param mixed $value
     * @return Query
     */
    public function addToSet($field, $value)
    {
        $this->_newObj['$addToSet'][$field] = $value;
        return $this;
    }

    /**
     * Adds values to the array only they are not in the array already.
     *
     * @param string $field
     * @param array $values
     * @return Query
     */
    public function addManyToSet($field, array $values)
    {
        $this->_newObj['$addToSet'][$field]['$each'] = $values;
    }

    /**
     * Removes first element in an array
     *
     * @param string $field  The field name
     * @return Query
     */
    public function popFirst($field)
    {
        $this->_newObj['$pop'][$field] = 1;
        return $this;
    }

    /**
     * Removes last element in an array
     *
     * @param string $field  The field name
     * @return Query
     */
    public function popLast($field)
    {
        $this->_newObj['$pop'][$field] = -1;
        return $this;
    }

    /**
     * Removes all occurrences of value from field, if field is an array.
     * If field is present but is not an array, an error condition is raised.
     *
     * @param string $field
     * @param mixed $value
     * @return Query
     */
    public function pull($field, $value)
    {
        $this->_newObj['$pull'][$field] = $value;
        return $this;
    }

    /**
     * Removes all occurrences of each value in value_array from field, if
     * field is an array. If field is present but is not an array, an error
     * condition is raised.
     *
     * @param string $field
     * @param array $valueArray
     * @return Query
     */
    public function pullAll($field, array $valueArray)
    {
        $this->_newObj['$pullAll'][$field] = $valueArray;
        return $this;
    }

    /**
     * Proxy to execute() method
     *
     * @param array $options 
     * @return Query
     */
    public function getResult(array $options = array())
    {
        return $this->execute($options);
    }

    /**
     * Execute the query and return an array of results
     *
     * @param array $options
     * @return mixed $result The result of the query.
     */
    public function execute(array $options = array())
    {
        switch ($this->_type) {
            case self::TYPE_FIND;
                return $this->getCursor()->getResults();
                break;

            case self::TYPE_REMOVE;
                return $this->_dm->getDocumentCollection($this->_className)
                    ->remove($this->_where, $options);
                break;

            case self::TYPE_UPDATE;
                return $this->_dm->getDocumentCollection($this->_className)
                    ->update($this->_where, $this->_newObj, $options);
                break;
            case self::TYPE_GROUP;
                return $this->_dm->getDocumentCollection($this->_className)
                    ->group(
                        $this->_group['keys'], $this->_group['initial'],
                        $this->_mapReduce['reduce'], $this->_where
                    );
                break;
        }
    }

    /**
     * Count the number of results for this query.
     *
     * @param bool $all
     * @return integer $count
     */
    public function count($all = false)
    {
        return $this->getCursor()->count($all);
    }

    /**
     * Execute the query and get a single result
     *
     * @return object $document  The single document.
     */
    public function getSingleResult()
    {
        return $this->getCursor()->getSingleResult();
    }

    /**
     * Get the MongoCursor for this query instance.
     *
     * @return MongoCursor $cursor
     */
    public function getCursor()
    {
        if ($this->_type !== self::TYPE_FIND) {
            throw new \InvalidArgumentException(
                'Cannot get cursor for an update or remove query. Use execute() method.'
            );
        }

        if (isset($this->_mapReduce['map']) && $this->_mapReduce['reduce']) {
            $cursor = $this->_dm->mapReduce($this->_className, $this->_mapReduce['map'], $this->_mapReduce['reduce'], $this->_where, isset($this->_mapReduce['options']) ? $this->_mapReduce['options'] : array());
            $cursor->hydrate(false);
        } else {
            if (isset($this->_mapReduce['reduce'])) {
                $this->_where['$where'] = $this->_mapReduce['reduce'];
            }
            $cursor = $this->_dm->find($this->_className, $this->_where, $this->_select);
            $cursor->hydrate($this->_hydrate);
        }
        $cursor->limit($this->_limit);
        $cursor->skip($this->_skip);
        $cursor->sort($this->_sort);
        $cursor->immortal($this->_immortal);
        $cursor->slaveOkay($this->_slaveOkay);
        if ($this->_snapshot) {
            $cursor->snapshot();
        }
        foreach ($this->_hints as $keyPattern) {
            $cursor->hint($keyPattern);
        }
        return $cursor;
    }

    /**
     * Iterator over the query using the MongoCursor.
     *
     * @return MongoCursor $cursor
     */
    public function iterate()
    {
        return $this->getCursor();
    }

    /**
     * Gets an array of information about this query for debugging.
     *
     * @return array $debug
     */
    public function debug()
    {
        return array(
            'className' => $this->_className,
            'type' => $this->_type,
            'select' => $this->_select,
            'where' => $this->_where,
            'newObj' => $this->_newObj,
            'sort' => $this->_sort,
            'limit' => $this->_limit,
            'skip' => $this->_skip,
            'group' => $this->_group,
            'hints' => $this->_hints,
            'immortal' => $this->_immortal,
            'snapshot' => $this->_snapshot,
            'slaveOkay' => $this->_slaveOkay,
            'hydrate' => $this->_hydrate,
            'mapReduce' => $this->_mapReduce
        );
    }
}