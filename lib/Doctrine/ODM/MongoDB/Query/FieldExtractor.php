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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Query;

/**
 * Class responsible for extracting various things from a given
 * mongodb query. Used for checking if query is indexed.
 *
 * @see Doctrine\ODM\MongoDB\Query::isIndexed()
 */
class FieldExtractor
{
    private $query;
    private $sort;

    /**
     * Constructor
     * 
     * @param array $query
     * @param array $sort
     */
    public function __construct(array $query, array $sort = array())
    {
        $this->query = $query;
        $this->sort = $sort;
    }
    
    /**
     * Gets all fields involved in query that run only equality check
     * 
     * @return array
     */
    public function getFieldsWithEqualityCondition()
    {
        $fields = array();
        foreach ($this->query as $k => $v) {
            if (is_array($v) && isset($v['$elemMatch']) && is_array($v['$elemMatch'])) {
                $elemMatchFields = $this->getFieldsFromElemMatch($v['$elemMatch'], true);
                foreach ($elemMatchFields as $field) {
                    $fields[] = $k.'.'.$field;
                }
            } elseif ($this->isOperator($k, array('and', 'or'))) {
                foreach ($v as $q) {
                    $test = new self($q);
                    $fields = array_merge($fields, $test->getFieldsWithEqualityCondition());
                }
            } elseif ($k[0] !== '$' && !is_array($v)) {
                $fields[] = $k;
            }
        }
        return array_values(array_unique($fields));
    }

    /**
     * Gets all fields involved in query
     * 
     * @return array
     */
    public function getFields()
    {
        $fields = array();

        foreach ($this->query as $k => $v) {
            if (is_array($v) && isset($v['$elemMatch']) && is_array($v['$elemMatch'])) {
                $elemMatchFields = $this->getFieldsFromElemMatch($v['$elemMatch']);
                foreach ($elemMatchFields as $field) {
                    $fields[] = $k.'.'.$field;
                }
            } elseif ($this->isOperator($k, array('and', 'or'))) {
                foreach ($v as $q) {
                    $test = new self($q);
                    $fields = array_merge($fields, $test->getFields());
                }
            } elseif ($k[0] !== '$') {
                $fields[] = $k;
            }
        }
        $fields = array_unique(array_merge($fields, array_keys($this->sort)));
        return $fields;
    }
    
    /**
     * Gets all $or clauses used in query
     * 
     * @return array
     */
    public function getOrClauses()
    {
        $clauses = array();
        foreach ($this->query as $k => $v) {
            if ($this->isOperator($k, 'or')) {
                foreach ($v as $q) {
                    $test = new self($q);
                    $foundClauses = $test->getOrClauses();
                    if (!empty($foundClauses)) {
                        $clauses = array_merge($clauses, $foundClauses);
                    } else {
                        $clauses[] = $q;
                    }
                }
            } elseif ($this->isOperator($k, 'and')) {
                foreach ($v as $q) {
                    $test = new self($q);
                    $foundClauses = $test->getOrClauses();
                    if (!empty($foundClauses)) {
                        $clauses = array_merge($clauses, $foundClauses);
                    }
                }
            } elseif (is_array($v) && isset($v['$elemMatch']) && is_array($v['$elemMatch'])) {
                $test = new self($v['$elemMatch']);
                $foundClauses = $test->getOrClauses();
                if (!empty($foundClauses)) {
                    $clauses = array_merge($clauses, $foundClauses);
                }
            }
        }
        return $clauses;
    }
    
    /**
     * Gets sort criteria
     * 
     * @return array
     */
    public function getSort()
    {
        return $this->sort;
    }
    
    /**
     * Gets all fields involved in sorting
     * 
     * @return array
     */
    public function getSortFields()
    {
        return array_keys($this->sort);
    }
    
    /**
     * Gets find criteria
     * 
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }
    
    /**
     * Gets find criteria withour $or clauses
     * 
     * @param array $query to start with
     * @return array
     */
    public function getQueryWithoutOrClauses($query = null)
    {
        if ($query === null) {
            $query = $this->query;
        }
        if (isset($query['$or'])) {
            unset($query['$or']);
        }
        foreach ($query as $k => $v) {
            if ($this->isOperator($k, 'and')) {
                foreach ($v as $i => $q) {
                    $query[$k][$i] = $this->getQueryWithoutOrClauses($q);
                    if (empty($query[$k][$i])) {
                        unset($query[$k][$i]);
                    }
                }
                if (empty($query[$k])) {
                    unset($query[$k]);
                }
            } elseif (is_array($v) && isset($v['$elemMatch']) && is_array($v['$elemMatch'])) {
                $query[$k]['$elemMatch'] = $this->getQueryWithoutOrClauses($v['$elemMatch']);
                if (empty($query[$k]['$elemMatch'])) {
                    unset($query[$k]['$elemMatch']);
                }
                if (empty($query[$k])) {
                    unset($query[$k]);
                }
            }
        }
        return $query;
    }
    
    /**
     * Gets fields involved in $elemMatch
     * 
     * @param array $elemMatch
     * @param boolean $onlyEqualityConditions
     * @return array
     */
    private function getFieldsFromElemMatch(array $elemMatch, $onlyEqualityConditions = false)
    {
        $fields = array();
        foreach ($elemMatch as $fieldName => $value) {
            if ($this->isOperator($fieldName, 'where')) {
                continue;
            }

            if ($this->isOperator($fieldName, array('and', 'or'))) {
                foreach ($value as $q) {
                    $test = new self($q);
                    if (!$onlyEqualityConditions) {
                        $fields = array_merge($fields, $test->getFields());
                    } else {
                        $fields = array_merge($fields, $test->getFieldsWithEqualityCondition());
                    }
                }
            } elseif (!$onlyEqualityConditions || ($onlyEqualityConditions && !is_array($value))) {
                $fields[] = $fieldName;
            }
        }
        return $fields;
    }

    /**
     * Checks if given field(s) is one of given operators
     * 
     * @param string $fieldName
     * @param string|array $operator
     * @return boolean
     */
    private function isOperator($fieldName, $operator)
    {
        if ( ! is_array($operator)) {
            $operator = array($operator);
        }
        foreach ($operator as $op) {
            if ($fieldName === '$' . $op) {
                return true;
            }
        }
        return false;
    }
}
