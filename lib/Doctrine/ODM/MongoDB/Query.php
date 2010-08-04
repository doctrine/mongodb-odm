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
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Query
{
    const TYPE_FIND     = 1;
    const TYPE_INSERT   = 2;
    const TYPE_UPDATE   = 3;
    const TYPE_REMOVE   = 4;
    const TYPE_GROUP    = 5;

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

    /** Field to select distinct values of */
    private $_distinctField;

    /** The type of query */
    private $_type = self::TYPE_FIND;

    /**
     * Mongo command prefix
     * @var string
     */
    private $_cmd;

    /** The current field adding conditions to */
    private $_currentField;

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
        $this->_cmd = $dm->getConfiguration()->getMongoCmd();
        if ($className !== null) {
            $this->from($className);
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
        if (is_array($className)) {
            $classNames = $className;
            $className = $classNames[0];

            $discriminatorField = $this->_dm->getClassMetadata($className)->discriminatorField['name'];
            $discriminatorValues = $this->_dm->getDiscriminatorValues($classNames);
            $this->field($discriminatorField)->in($discriminatorValues);
        }

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
     * Sets the query as an insert query for the given class name or change
     * the type for the current class.
     *
     * @param string $className
     * @return Query
     */
    public function insert($className = null)
    {
        if ($className !== null) {
            $this->_className = $className;
            $this->_class = $this->_dm->getClassMetadata($className);
        }
        $this->_type = self::TYPE_INSERT;
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
     * The distinct method queries for a list of distinct values for the given
     * field for the document being queried for.
     *
     * @param string $field
     * @return Query
     */
    public function distinct($field)
    {
        $this->_distinctField = $field;
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
        $select = func_get_args();
        foreach ($select as $fieldName) {
            $this->_select[] = $fieldName;
        }
        return $this;
    }

    /**
     * Select a slice of an embedded document.
     *
     * @param string $fieldName
     * @param integer $skip
     * @param integer $limit
     * @return Query
     */
    public function selectSlice($fieldName, $skip, $limit = null)
    {
        $slice = array($skip);
        if ($limit !== null) {
            $slice[] = $limit;
        }
        $this->_select[$fieldName][$this->_cmd . 'slice'] = $slice;
        return $this;
    }

    /**
     * Set the current field to operate on.
     *
     * @param string $field
     * @return Query
     */
    public function field($field)
    {
        $this->_currentField = $field;
        return $this;
    }

    /**
     * Add a new where criteria erasing all old criteria.
     *
     * @param string $value
     * @return Query
     */
    public function equals($value, array $options = array())
    {
        $value = $this->_prepareWhereValue($this->_currentField, $value);

        if (isset($options['elemMatch'])) {
            return $this->elemMatch($value, $options);
        }

        if (isset($options['not'])) {
            return $this->not($value, $options);
        }

        if (isset($this->_where[$this->_currentField])) {
            $this->_where[$this->_currentField] = array_merge_recursive($this->_where[$this->_currentField], $value);
        } else {
            $this->_where[$this->_currentField] = $value;
        }

        return $this;
    }

    /**
     * Add $where javascript function to reduce result sets.
     *
     * @param string $javascript
     * @return Query
     */
    public function where($javascript)
    {
        return $this->field($this->_cmd . 'where')->equals($javascript);
    }

    /**
     * Add element match to query.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function elemMatch($value, array $options = array())
    {
        $e = explode('.', $this->_currentField);
        $fieldName = array_pop($e);
        $embeddedPath = implode('.', $e);
        $this->_where[$embeddedPath][$this->_cmd . 'elemMatch'][$fieldName] = $value;
        return $this;
    }

    /**
     * Add element match operator to the query.
     *
     * @param string $operator 
     * @param string $value 
     * @return Query
     */
    public function elemMatchOperator($operator, $value)
    {
        $e = explode('.', $this->_currentField);
        $fieldName = array_pop($e);
        $embeddedPath = implode('.', $e);
        $this->_where[$embeddedPath][$this->_cmd . 'elemMatch'][$fieldName][$operator] = $value;
        return $this;
    }

    /**
     * Add MongoDB operator to the query.
     *
     * @param string $operator
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function operator($operator, $value, array $options = array())
    {
        if (isset($options['elemMatch'])) {
            return $this->elemMatchOperator($operator, $value);
        }
        if (isset($options['not'])) {
            $this->_where[$this->_currentField][$this->_cmd . 'not'][$operator] = $value;
            return $this;
        }
        $this->_where[$this->_currentField][$operator] = $value;
        return $this;
    }

    /**
     * Add a new where not criteria
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function not($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'not', $value);
    }

    /**
     * Add a new where in criteria.
     *
     * @param mixed $values
     * @param array $options
     * @return Query
     */
    public function in($values, array $options = array())
    {
        return $this->operator($this->_cmd . 'in', $values, $options);
    }

    /**
     * Add where not in criteria.
     *
     * @param mixed $values
     * @param array $options
     * @return Query
     */
    public function notIn($values, array $options = array())
    {
        return $this->operator($this->_cmd . 'nin', (array) $values, $options);
    }

    /**
     * Add where not equal criteria.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function notEqual($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'ne', $value, $options);
    }

    /**
     * Add where greater than criteria.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function greaterThan($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'gt', $value, $options);
    }

    /**
     * Add where greater than or equal to criteria.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function greaterThanOrEq($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'gte', $value, $options);
    }

    /**
     * Add where less than criteria.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function lessThan($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'lt', $value, $options);
    }

    /**
     * Add where less than or equal to criteria.
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function lessThanOrEq($value, array $options = array())
    {
        return $this->operator($this->_cmd . 'lte', $value, $options);
    }

    /**
     * Add where range criteria.
     *
     * @param string $start
     * @param string $end
     * @param array $options
     * @return Query
     */
    public function range($start, $end, array $options = array())
    {
        return $this->operator($this->_cmd . 'gt', $start, $options)
            ->operator($this->_cmd . 'lt', $end, $options);
    }

    /**
     * Add where size criteria.
     *
     * @param string $size
     * @param array $options
     * @return Query
     */
    public function size($size, array $options = array())
    {
        return $this->operator($this->_cmd . 'size', $size, $options);
    }

    /**
     * Add where exists criteria.
     *
     * @param string $bool
     * @param array $options
     * @return Query
     */
    public function exists($bool, array $options = array())
    {
        return $this->operator($this->_cmd . 'exists', $bool, $options);
    }

    /**
     * Add where type criteria.
     *
     * @param string $type
     * @param array $options
     * @return Query
     */
    public function type($type, array $options = array())
    {
        $map = array(
            'double' => 1,
            'string' => 2,
            'object' => 3,
            'array' => 4,
            'binary' => 5,
            'undefined' => 6,
            'objectid' => 7,
            'boolean' => 8,
            'date' => 9,
            'null' => 10,
            'regex' => 11,
            'jscode' => 13,
            'symbol' => 14,
            'jscodewithscope' => 15,
            'integer32' => 16,
            'timestamp' => 17,
            'integer64' => 18,
            'minkey' => 255,
            'maxkey' => 127
        );
        if (is_string($type) && isset($map[$type])) {
            $type = $map[$type];
        }
        return $this->operator($this->_cmd . 'type', $type, $options);
    }

    /**
     * Add where all criteria.
     *
     * @param mixed $values
     * @param array $options
     * @return Query
     */
    public function all($values, array $options = array())
    {
        return $this->operator($this->_cmd . 'all', (array) $values, $options);
    }

    /**
     * Add where mod criteria.
     *
     * @param string $mod
     * @param array $options
     * @return Query
     */
    public function mod($mod, array $options = array())
    {
        return $this->operator($this->_cmd . 'mod', $mod, $options);
    }

    /**
     * Set sort and erase all old sorts.
     *
     * @param string $order
     * @return Query
     */
    public function sort($fieldName, $order)
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
     * @param mixed $value
     * @param boolean $atomic
     * @return Query
     */
    public function set($value, $atomic = true)
    {
        if ($this->_type == self::TYPE_INSERT) {
            $atomic = false;
        }
        if ($atomic === true) {
            $this->_newObj[$this->_cmd . 'set'][$this->_currentField] = $value;
        } else {
            if (strpos($this->_currentField, '.') !== false) {
                $e = explode('.', $this->_currentField);
                $current = &$this->_newObj;
                foreach ($e as $v) {
                    $current[$v] = null;
                    $current = &$current[$v];
                }
                $current = $value;
            } else {
                $this->_newObj[$this->_currentField] = $value;
            }
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
     * @param integer $value
     * @return Query
     */
    public function inc($value)
    {
        $this->_newObj[$this->_cmd . 'inc'][$this->_currentField] = $value;
        return $this;
    }

    /**
     * Deletes a given field.
     *
     * @return Query
     */
    public function unsetField()
    {
        $this->_newObj[$this->_cmd . 'unset'][$this->_currentField] = 1;
        return $this;
    }

    /**
     * Appends value to field, if field is an existing array, otherwise sets
     * field to the array [value] if field is not present. If field is present
     * but is not an array, an error condition is raised.
     *
     * @param mixed $value
     * @return Query
     */
    public function push($value)
    {
        $this->_newObj[$this->_cmd . 'push'][$this->_currentField] = $value;
        return $this;
    }

    /**
     * Appends each value in valueArray to field, if field is an existing
     * array, otherwise sets field to the array valueArray if field is not
     * present. If field is present but is not an array, an error condition is
     * raised.
     *
     * @param array $valueArray
     * @return Query
     */
    public function pushAll(array $valueArray)
    {
        $this->_newObj[$this->_cmd . 'pushAll'][$this->_currentField] = $valueArray;
        return $this;
    }

    /**
     * Adds value to the array only if its not in the array already.
     *
     * @param mixed $value
     * @return Query
     */
    public function addToSet($value)
    {
        $this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField] = $value;
        return $this;
    }

    /**
     * Adds values to the array only they are not in the array already.
     *
     * @param array $values
     * @return Query
     */
    public function addManyToSet(array $values)
    {
        if ( ! isset($this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField])) {
            $this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField][$this->_cmd . 'each'] = array();
        }
        if ( ! is_array($this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField])) {
            $this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField] = array($this->_cmd . 'each' => array($this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField]));
        }
        $this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField][$this->_cmd . 'each'] = array_merge_recursive($this->_newObj[$this->_cmd . 'addToSet'][$this->_currentField][$this->_cmd . 'each'], $values);
    }

    /**
     * Removes first element in an array
     *
     * @return Query
     */
    public function popFirst()
    {
        $this->_newObj[$this->_cmd . 'pop'][$this->_currentField] = 1;
        return $this;
    }

    /**
     * Removes last element in an array
     *
     * @return Query
     */
    public function popLast()
    {
        $this->_newObj[$this->_cmd . 'pop'][$this->_currentField] = -1;
        return $this;
    }

    /**
     * Removes all occurrences of value from field, if field is an array.
     * If field is present but is not an array, an error condition is raised.
     *
     * @param mixed $value
     * @return Query
     */
    public function pull($value)
    {
        $this->_newObj[$this->_cmd . 'pull'][$this->_currentField] = $value;
        return $this;
    }

    /**
     * Removes all occurrences of each value in value_array from field, if
     * field is an array. If field is present but is not an array, an error
     * condition is raised.
     *
     * @param array $valueArray
     * @return Query
     */
    public function pullAll(array $valueArray)
    {
        $this->_newObj[$this->_cmd . 'pullAll'][$this->_currentField] = $valueArray;
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
                if ($this->_distinctField !== null) {
                    $result = $this->_dm->getDocumentDB($this->_className)
                        ->command(array(
                            'distinct' => $this->_dm->getDocumentCollection($this->_className)->getName(),
                            'key' => $this->_distinctField,
                            'query' => $this->_where
                        ));
                    return $result['values'];
                }
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
            
            case self::TYPE_INSERT;
                return $this->_dm->getDocumentCollection($this->_className)
                    ->insert($this->_newObj);
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
                $this->_where[$this->_cmd . 'where'] = $this->_mapReduce['reduce'];
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
     * @param string $name
     * @return array $debug
     */
    public function debug($name = null)
    {
        $debug = array(
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
            'mapReduce' => $this->_mapReduce,
            'distinctField' => $this->_distinctField,
        );
        if ($name !== null) {
            return $debug[$name];
        }
        foreach ($debug as $key => $value) {
            if ( ! $value) {
                unset($debug[$key]);
            }
        }
        return $debug;
    }

    private function _prepareWhereValue(&$fieldName, $value)
    {
        if ($fieldName === $this->_class->identifier) {
            $fieldName = '_id';
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $this->_class->getDatabaseIdentifierValue($v);
                }
            } else {
                $value = $this->_class->getDatabaseIdentifierValue($value);
            }
        }
        return $value;
    }

    public function __call($method, $arguments)
    {
        return $this->field($method);
    }
}