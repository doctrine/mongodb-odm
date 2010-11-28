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
    Doctrine\ODM\MongoDB\Hydrator,
    Doctrine\ODM\MongoDB\Query\AbstractQuery;

/**
 * Query object that represents a query using a documents MongoCollection::find()
 * method. Offers a fluent chainable interface similar to the Doctrine ORM.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class QueryBuilder
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The Document class name being queried
     *
     * @var string
     */
    private $className;

    /**
     * The ClassMetadata instance for the class being queried
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Array of fields to select
     *
     * @var array
     */
    private $select = array();

    /**
     * Array that stores the built up query to execute.
     *
     * @var array
     */
    private $query = array();

    /**
     * Array to pass to MongoCollection::update() 2nd argument
     *
     * @var array
     */
    private $newObj = array();

    /**
     * Array of sort options
     *
     * @var array
     */
    private $sort = array();

    /**
     * Limit number of records
     *
     * @var integer
     */
    private $limit = null;

    /**
     * Skip a specified number of records (offset)
     *
     * @var integer
     */
    private $skip = null;

    /**
     * Group information.
     *
     * @var array
     */
    private $group = array();

    /**
     * Pass hints to the MongoCursor
     *
     * @var array
     */
    private $hints = array();

    /**
     * Pass immortal to cursor
     *
     * @var bool
     */
    private $immortal = false;

    /**
     * Pass snapshot to cursor
     *
     * @var bool
     */
    private $snapshot = false;

    /**
     * Pass slaveOkay to cursor
     *
     * @var bool
     */
    private $slaveOkay = false;

    /**
     * Whether or not to try and hydrate the returned data
     *
     * @var bool
     */
    private $hydrate = true;

    /**
     * Map reduce information
     *
     * @var array
     */
    private $mapReduce = array();

    /**
     * Field to select distinct values of
     *
     * @var string
     */
    private $distinctField;

    /**
     * Data to use with $near operator for geospatial indexes
     *
     * @var array
     */
    private $near;

    /**
     * The type of query
     *
     * @var integer
     */
    private $type = AbstractQuery::TYPE_FIND;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * The current field adding conditions to
     *
     * @var string
     */
    private $currentField;

    /**
     * Whether or not the query is a findAndModify query. Stores an array of options if not false.
     *
     * @var mixed
     */
    private $findAndModify = false;

    /** Refresh hint */
    const HINT_REFRESH = 1;

    /**
     * Create a new MongoDB Query.
     *
     * @param DocumentManager $dm
     * @param string $className
     */
    public function __construct(DocumentManager $dm, Hydrator $h, $cmd, $className = null)
    {
        $this->dm = $dm;
        $this->hydrator = $h;
        $this->cmd = $cmd;
        if ($className !== null) {
            $this->find($className);
        }
    }

    /**
     * Returns the DocumentManager instance for this query.
     *
     * @return Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * Get the type of this query.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the current query array.
     *
     * @param array $query A query array
     */
    public function setQueryArray($query)
    {
        $this->query = $query;
    }

    /**
     * Returns the current query array.
     *
     * @return array The query array
     */
    public function getQueryArray()
    {
        return $this->query;
    }

    /**
     * Whether or not to hydrate the data into objects or to return the raw results
     * from mongo.
     *
     * @param boolean $bool
     */
    public function hydrate($bool)
    {
        $this->hydrate = $bool;
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
        $this->slaveOkay = $bool;
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
        $this->snapshot = $bool;
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
        $this->immortal = $bool;
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
        $this->hints[] = $keyPattern;
        return $this;
    }

    /**
     * Change the query type to find and optionally set and change the class being queried.
     *
     * @param string $className The Document class being queried.
     * @return Query
     */
    public function find($className = null)
    {
        $this->setClassName($className);
        $this->type = AbstractQuery::TYPE_FIND;
        return $this;
    }

    /**
     * Sets a flag for the query to be executed as a findAndModify query query.
     *
     * @param array $findAndModify Boolean true value or array containing key "upsert" set to true to
     *                             create object if it doesn't exist and a second key "new"
     *                             set to true if you want to return the modified object
     *                             rather than the original. "new" is ignored for remove.
     * @return Query
     */
    public function findAndModify($findAndModify = true)
    {
        $this->findAndModify = $findAndModify;
        return $this;
    }

    /**
     * Change the query type to update and optionally set and change the class being queried.
     *
     * @param string $className The Document class being queried.
     * @return Query
     */
    public function update($className = null)
    {
        $this->setClassName($className);
        $this->type = AbstractQuery::TYPE_UPDATE;
        return $this;
    }

    /**
     * Change the query type to insert and optionally set and change the class being queried.
     *
     * @param string $className The Document class being queried.
     * @return Query
     */
    public function insert($className = null)
    {
        $this->setClassName($className);
        $this->type = AbstractQuery::TYPE_INSERT;
        return $this;
    }

    /**
     * Change the query type to remove and optionally set and change the class being queried.
     *
     * @param string $className The Document class being queried.
     * @return Query
     */
    public function remove($className = null)
    {
        $this->setClassName($className);
        $this->type = AbstractQuery::TYPE_REMOVE;
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
        $this->group = array(
            'keys' => $keys,
            'initial' => $initial
        );
        $this->type = AbstractQuery::TYPE_GROUP;
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
        $this->distinctField = $field;
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
            $this->select[] = $fieldName;
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
        $this->select[$fieldName][$this->cmd . 'slice'] = $slice;
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
        $this->currentField = $field;
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
        if ($this->currentField) {
            $this->query[$this->currentField] = $value;
        } else {
            $this->query = $value;
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
        return $this->field($this->cmd . 'where')->equals($javascript);
    }

    /**
     * Add MongoDB operator to the query.
     *
     * @param string $operator
     * @param string $value
     * @return Query
     */
    public function operator($operator, $value)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$operator] = $value;
        } else {
            $this->query[$operator] = $value;
        }
        return $this;
    }

    /**
     * Add a new where not criteria
     *
     * @param string $value
     * @param array $options
     * @return Query
     */
    public function not($value)
    {
        return $this->operator($this->cmd . 'not', $value);
    }

    /**
     * Add a new where in criteria.
     *
     * @param mixed $values
     * @return Query
     */
    public function in($values)
    {
        return $this->operator($this->cmd . 'in', $values);
    }

    /**
     * Add where not in criteria.
     *
     * @param mixed $values
     * @return Query
     */
    public function notIn($values)
    {
        return $this->operator($this->cmd . 'nin', (array) $values);
    }

    /**
     * Add where not equal criteria.
     *
     * @param string $value
     * @return Query
     */
    public function notEqual($value)
    {
        return $this->operator($this->cmd . 'ne', $value);
    }

    /**
     * Add where greater than criteria.
     *
     * @param string $value
     * @return Query
     */
    public function greaterThan($value)
    {
        return $this->operator($this->cmd . 'gt', $value);
    }

    /**
     * Add where greater than or equal to criteria.
     *
     * @param string $value
     * @return Query
     */
    public function greaterThanOrEq($value)
    {
        return $this->operator($this->cmd . 'gte', $value);
    }

    /**
     * Add where less than criteria.
     *
     * @param string $value
     * @return Query
     */
    public function lessThan($value)
    {
        return $this->operator($this->cmd . 'lt', $value);
    }

    /**
     * Add where less than or equal to criteria.
     *
     * @param string $value
     * @return Query
     */
    public function lessThanOrEq($value)
    {
        return $this->operator($this->cmd . 'lte', $value);
    }

    /**
     * Add where range criteria.
     *
     * @param string $start
     * @param string $end
     * @return Query
     */
    public function range($start, $end)
    {
        return $this->operator($this->cmd . 'gte', $start)
            ->operator($this->cmd . 'lt', $end);
    }

    /**
     * Add where size criteria.
     *
     * @param string $size
     * @return Query
     */
    public function size($size)
    {
        return $this->operator($this->cmd . 'size', $size);
    }

    /**
     * Add where exists criteria.
     *
     * @param string $bool
     * @return Query
     */
    public function exists($bool)
    {
        return $this->operator($this->cmd . 'exists', $bool);
    }

    /**
     * Add where type criteria.
     *
     * @param string $type
     * @return Query
     */
    public function type($type)
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
        return $this->operator($this->cmd . 'type', $type);
    }

    /**
     * Add where all criteria.
     *
     * @param mixed $values
     * @return Query
     */
    public function all($values)
    {
        return $this->operator($this->cmd . 'all', (array) $values);
    }

    /**
     * Add where mod criteria.
     *
     * @param string $mod
     * @return Query
     */
    public function mod($mod)
    {
        return $this->operator($this->cmd . 'mod', $mod);
    }

    /**
     * Add where near criteria.
     *
     * @param string $x
     * @param string $y
     * @return Query
     */
    public function near($x, $y)
    {
        list($xMapping, $yMapping) = array_values($this->dm->getClassMetadata($this->class->fieldMappings[$this->currentField]['targetDocument'])->fieldMappings);
        $this->near = array($xMapping['name'] => $x, $yMapping['name'] => $y);
        return $this;
    }

    /**
     * Add where $within $box query.
     *
     * @param string $x1
     * @param string $y1
     * @param string $x2
     * @param string $y2
     * @return Query
     */
    public function withinBox($x1, $y1, $x2, $y2)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'within'][$this->cmd . 'box'] = array(array($x1, $y1), array($x2, $y2));
        } else {
            $this->query[$this->cmd . 'within'][$this->cmd . 'box'] = array(array($x1, $y1), array($x2, $y2));
        }
        return $this;
    }

    /**
     * Add where $within $center query.
     *
     * @param string $x
     * @param string $y
     * @param string $radius
     * @return Query
     */
    public function withinCenter($x, $y, $radius)
    {
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'within'][$this->cmd . 'center'] = array(array($x, $y), $radius);
        } else {
            $this->query[$this->cmd . 'within'][$this->cmd . 'center'] = array(array($x, $y), $radius);
        }
        return $this;
    }

    /**
     * Uses $elemMatch to limit results to documents that reference another document.
     *
     * @param mixed $document A document
     * @return Query
     */
    public function references($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));

        $reference = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id'  => $class->getDatabaseIdentifierValue($class->getIdentifierValue($document)),
            $this->cmd . 'db'  => $class->getDB()
        );

        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'elemMatch'] = $reference;
        } else {
            $this->query[$this->cmd . 'elemMatch'] = $reference;
        }

        return $this;
    }

    /**
     * Set sort and erase all old sorts.
     *
     * @param string $order
     * @return Query
     */
    public function sort($fieldName, $order)
    {
        $this->sort[$fieldName] = strtolower($order) === 'asc' ? 1 : -1;
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
        $this->limit = $limit;
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
        $this->skip = $skip;
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
        $this->mapReduce = array(
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
        $this->mapReduce['map'] = $map;
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
        $this->mapReduce['reduce'] = $reduce;
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
        $this->mapReduce['options'] = $options;
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
        if ($this->type == AbstractQuery::TYPE_INSERT) {
            $atomic = false;
        }
        if ($atomic === true) {
            $this->newObj[$this->cmd . 'set'][$this->currentField] = $value;
        } else {
            if (strpos($this->currentField, '.') !== false) {
                $e = explode('.', $this->currentField);
                $current = &$this->newObj;
                foreach ($e as $v) {
                    $current[$v] = null;
                    $current = &$current[$v];
                }
                $current = $value;
            } else {
                $this->newObj[$this->currentField] = $value;
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
        $this->newObj = $newObj;
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
        $this->newObj[$this->cmd . 'inc'][$this->currentField] = $value;
        return $this;
    }

    /**
     * Deletes a given field.
     *
     * @return Query
     */
    public function unsetField()
    {
        $this->newObj[$this->cmd . 'unset'][$this->currentField] = 1;
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
        $this->newObj[$this->cmd . 'push'][$this->currentField] = $value;
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
        $this->newObj[$this->cmd . 'pushAll'][$this->currentField] = $valueArray;
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
        $this->newObj[$this->cmd . 'addToSet'][$this->currentField] = $value;
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
        if ( ! isset($this->newObj[$this->cmd . 'addToSet'][$this->currentField])) {
            $this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'] = array();
        }
        if ( ! is_array($this->newObj[$this->cmd . 'addToSet'][$this->currentField])) {
            $this->newObj[$this->cmd . 'addToSet'][$this->currentField] = array($this->cmd . 'each' => array($this->newObj[$this->cmd . 'addToSet'][$this->currentField]));
        }
        $this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'] = array_merge_recursive($this->newObj[$this->cmd . 'addToSet'][$this->currentField][$this->cmd . 'each'], $values);
    }

    /**
     * Removes first element in an array
     *
     * @return Query
     */
    public function popFirst()
    {
        $this->newObj[$this->cmd . 'pop'][$this->currentField] = 1;
        return $this;
    }

    /**
     * Removes last element in an array
     *
     * @return Query
     */
    public function popLast()
    {
        $this->newObj[$this->cmd . 'pop'][$this->currentField] = -1;
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
        $this->newObj[$this->cmd . 'pull'][$this->currentField] = $value;
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
        $this->newObj[$this->cmd . 'pullAll'][$this->currentField] = $valueArray;
        return $this;
    }

    /**
     * Adds an "or" expression to the current query.
     *
     * You can create the expression using the expr() method:
     *
     *     $qb = $this->createQueryBuilder('User');
     *     $qb
     *         ->addOr($qb->expr()->field('first_name')->equals('Kris'))
     *         ->addOr($qb->expr()->field('first_name')->equals('Chris'));
     *
     * @param array|QueryBuilder $expression
     * @return Query
     */
    public function addOr($expression)
    {
        if ($expression instanceof QueryBuilder) {
            $expression = $expression->getQueryArray();
        }
        $this->query[$this->cmd . 'or'][] = $expression;
        return $this;
    }

    /**
     * Adds an "elemMatch" expression to the current query.
     *
     * You can create the expression using the expr() method:
     *
     *     $qb = $this->createQueryBuilder('User');
     *     $qb
     *         ->field('phonenumbers')
     *         ->addElemMatch($qb->expr()->field('phonenumber')->equals('6155139185'));
     *
     * @param array|QueryBuilder $expression
     * @return Query
     */
    public function addElemMatch($expression)
    {
        if ($expression instanceof QueryBuilder) {
            $expression = $expression->getQueryArray();
        }
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'elemMatch'] = $expression;
        } else {
            $this->query[$this->cmd . 'elemMatch'] = $expression;
        }
        return $this;
    }

    /**
     * Adds a "not" expression to the current query.
     *
     * You can create the expression using the expr() method:
     *
     *     $qb = $this->createQueryBuilder('User');
     *     $qb
     *         ->field('id')
     *         ->addNot($qb->expr()->in(1));
     *
     * @param array|QueryBuilder $expression
     * @return Query
     */
    public function addNot($expression)
    {
        if ($expression instanceof QueryBuilder) {
            $expression = $expression->getQueryArray();
        }
        if ($this->currentField) {
            $this->query[$this->currentField][$this->cmd . 'not'] = $expression;
        } else {
            $this->query[$this->cmd . 'not'] = $expression;
        }
        return $this;
    }

    /**
     * Create a new QueryBuilder instance that can be used as an expression with the addOr()
     * method.
     *
     * @return QueryBuilder $expr
     */
    public function expr()
    {
        $expr = new self($this->dm, $this->hydrator, $this->cmd);
        if (isset($this->class->fieldMappings[$this->currentField]['targetDocument'])) {
            $expr->className = $this->class->fieldMappings[$this->currentField]['targetDocument'];
            $expr->class = $this->dm->getClassMetadata($expr->className);
        } else {
            $expr->className = $this->className;
            $expr->class = $this->class;
        }
        return $expr;
    }

    /**
     * Gets the Query executable.
     *
     * @param array $options
     * @return QueryInterface $query
     */
    public function getQuery()
    {
        switch ($this->type) {
            case AbstractQuery::TYPE_FIND;
                if ($this->distinctField !== null) {
                    $query = new Query\DistinctFieldQuery($this->dm, $this->class, $this->cmd);
                    $query->setDistinctField($this->distinctField);
                    $query->setQuery($this->query);
                    return $query;
                } elseif ($this->near !== null) {
                    $query = new Query\GeoLocationFindQuery($this->dm, $this->class, $this->cmd);
                    $query->setQuery($this->query);
                    $query->setNear($this->near);
                    $query->setLimit($this->limit);
                    $query->setHydrate($this->hydrate);
                    return $query;
                } else if (isset($this->mapReduce['map']) && $this->mapReduce['reduce']) {
                    $query = new Query\MapReduceQuery($this->dm, $this->class, $this->cmd);
                    $query->setQuery($this->query);
                    $query->setMap(isset($this->mapReduce['map']) ? $this->mapReduce['map'] : null);
                    $query->setReduce(isset($this->mapReduce['reduce']) ? $this->mapReduce['reduce'] : null);
                    $query->setOptions(isset($this->mapReduce['options']) ? $this->mapReduce['options'] : array());
                    return $query;
                } else {
                    $query = new Query\FindQuery($this->dm, $this->class, $this->cmd);
                    $query->setReduce(isset($this->mapReduce['reduce']) ? $this->mapReduce['reduce'] : null);
                    $query->setSelect($this->select);
                    $query->setQuery($this->query);
                    $query->setHydrate($this->hydrate);
                    $query->setLimit($this->limit);
                    $query->setSkip($this->skip);
                    $query->setSort($this->sort);
                    $query->setImmortal($this->immortal);
                    $query->setSlaveOkay($this->slaveOkay);
                    $query->setSnapshot($this->snapshot);
                    $query->setHints($this->hints);
                    return $query;
                }
                break;
            case AbstractQuery::TYPE_REMOVE;
                if ($this->findAndModify !== false) {
                    $query = new Query\FindAndRemoveQuery($this->dm, $this->class, $this->cmd);
                    $query->setSelect($this->select);
                    $query->setQuery($this->query);
                    $query->setSort($this->sort);
                    $query->setLimit($this->limit);
                    return $query;
                } else {
                    $query = new Query\RemoveQuery($this->dm, $this->class, $this->cmd);
                    $query->setQuery($this->query);
                    return $query;
                }
                break;

            case AbstractQuery::TYPE_UPDATE;
                if ($this->findAndModify !== false) {
                    $query = new Query\FindAndUpdateQuery($this->dm, $this->class, $this->cmd);
                    $query->setSelect($this->select);
                    $query->setQuery($this->query);
                    $query->setNewObj($this->newObj);
                    $query->setSort($this->sort);
                    $query->setUpsert(isset($this->findAndModify['upsert']));
                    $query->setNew(isset($this->findAndModify['new']));
                    $query->setLimit($this->limit);
                    return $query;
                } else {
                    $query = new Query\UpdateQuery($this->dm, $this->class, $this->cmd);
                    $query->setQuery($this->query);
                    $query->setNewObj($this->newObj);
                    return $query;
                }
                break;

            case AbstractQuery::TYPE_INSERT;
                $query = new Query\InsertQuery($this->dm, $this->class, $this->cmd);
                $query->setNewObj($this->newObj);
                return $query;
                break;

            case AbstractQuery::TYPE_GROUP;
                $query = new Query\GroupQuery($this->dm, $this->class, $this->cmd);
                $query->setKeys($this->group['keys']);
                $query->setInitial($this->group['initial']);
                $query->setReduce($this->mapReduce['reduce']);
                $query->setQuery($this->query);
                return $query;
                break;
        }
    }

    /**
     * Gets an array of information about this query for debugging.
     *
     * @param string $name
     * @return array $debug
     */
    public function debug($name = null)
    {
        $debug = get_object_vars($this);

        unset($debug['dm'], $debug['hydrator'], $debug['class']);
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

    private function setClassName($className)
    {
        if (is_array($className)) {
            $classNames = $className;
            $className = $classNames[0];

            $discriminatorField = $this->dm->getClassMetadata($className)->discriminatorField['name'];
            $discriminatorValues = $this->dm->getDiscriminatorValues($classNames);
            $this->field($discriminatorField)->in($discriminatorValues);
        }

        if ($className !== null) {
            $this->className = $className;
            $this->class = $this->dm->getClassMetadata($className);
        }
    }
}