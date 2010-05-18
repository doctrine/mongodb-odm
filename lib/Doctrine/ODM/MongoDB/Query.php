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
    /** Array of sort options */
    private $_sort = array();
    /** Limit number of records */
    private $_limit = null;
    /** Skip a specified number of records (offset) */
    private $_skip = null;
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
     * Whether or not to try and hydrate the returned data
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
     */
    public function from($className)
    {
        $this->_className = $className;
        $this->_class = $this->_dm->getClassMetadata($className);
        return $this;
    }

    /**
     * The fields to select.
     *
     * @param string $fieldName
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
     */
    public function addWhere($fieldName, $value)
    {
        if ($fieldName === $this->_class->identifier) {
            $fieldName = '_id';
            $value = new \MongoId($value);
        }
        $this->_where[$fieldName] = $value;
        return $this;
    }

    /**
     * Add a new where in criteria.
     *
     * @param string $fieldName
     * @param mixed $values
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
     */
    public function whereType($fieldName, $type)
    {
        return $this->addWhere($fieldName, array('$type' => $type));
    }

    /**
     * Add where all criteria.
     *
     * @param string $fieldName 
     * @param mixed $values 
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
     */
    public function sort($fieldName, $order)
    {
        $this->_sort = array();
        $this->_sort[$fieldName] = strtolower($order) === 'asc' ? 1 : -1;
        return $this;
    }

    /**
     * Add a new sort order.
     *
     * @param string $fieldName 
     * @param string $order 
     * @return Query $this
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
     * @return Query $this
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
     * @return Query $this
     */
    public function skip($skip)
    {
        $this->_skip = $skip;
        return $this;
    }

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
     * Execute the query and return an array of results
     *
     * @return array $results The array of results for the query.
     */
    public function execute()
    {
        return $this->getCursor()->getResults();
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
        if ($result = $this->execute()) {
            return array_shift($result);
        }
        return null;
    }

    /**
     * Get the MongoCursor for this query instance.
     *
     * @return MongoCursor $cursor
     */
    public function getCursor()
    {
        if ($this->_mapReduce) {
            $cursor = $this->_dm->mapReduce($this->_className, $this->_mapReduce['map'], $this->_mapReduce['reduce'], $this->_where, $this->_mapReduce['options']);
        } else {
            $cursor = $this->_dm->find($this->_className, $this->_where, $this->_select);
        }
        $cursor->limit($this->_limit);
        $cursor->skip($this->_skip);
        $cursor->sort($this->_sort);
        $cursor->immortal($this->_immortal);
        $cursor->slaveOkay($this->_slaveOkay);
        $cursor->hydrate($this->_hydrate);
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
}