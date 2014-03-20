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

use Doctrine\MongoDB\Collection;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * Class responsible for checking if given query will use any
 * index defined in collection
 * 
 * @since       1.0
 * @author      Maciej Malarz <malarzm@gmail.com>
 * @see         http://docs.mongodb.org/manual/core/index-compound/
 * @see         http://docs.mongodb.org/manual/tutorial/sort-results-with-indexes/
 */
class IndexChecker
{
    /**
     * Whether or not to allow less efficient queries on Compound Index
     * 
     * @var boolean
     */
    private $allowLessEfficientIndexes;
    
    /**
     * The Collection instance.
     *
     * @var Collection
     */
    private $collection;
    
    /**
     * The Query instance.
     *
     * @var Query
     */
    private $query;
    
    /**
     * Constructor
     * 
     * @param \Doctrine\ODM\MongoDB\Query\Query $query
     * @param \Doctrine\MongoDB\Collection $collection
     * @param boolean $allowLessEfficientIndexes
     */
    public function __construct(Query $query, Collection $collection, $allowLessEfficientIndexes = true)
    {
        $this->collection = $collection;
        $this->query = $query;
        $this->allowLessEfficientIndexes = $allowLessEfficientIndexes;
    }
    
    /**
     * Checks if given Query can use any existing Index
     * 
     * @return true|MongoDbException
     */
    public function isQueryIndexed()
    {
        // $or clausules executes in parallel and may use other index
        $orClauses = $this->query->getFieldExtractor()->getOrClauses();
        foreach ($orClauses as $or) {
            $orExtractor = new FieldExtractor($or);
            if (!$this->getIndexesIncludingFields($orExtractor->getFields(), true)) {
                if (!$this->allowLessEfficientIndexes) {
                    return MongoDBException::orClauseNotEfficientlyIndexed($this->query->getClass()->name, $orExtractor->getFields());
                }
                return MongoDBException::orClauseNotIndexed($this->query->getClass()->name, $orExtractor->getFields());
            }
        }
        $queryWithourOrs = $this->query->getFieldExtractor()->getQueryWithoutOrClauses();
        $qwoExtractor = new FieldExtractor($queryWithourOrs);
        $possibleMatches = $this->getIndexesIncludingFields($qwoExtractor->getFields());
        if (empty($possibleMatches)) {
            if (!$this->allowLessEfficientIndexes) {
                return MongoDBException::queryNotEfficientlyIndexed($this->query->getClass()->name, $qwoExtractor->getFields());
            }
            return MongoDBException::queryNotIndexed($this->query->getClass()->name, $qwoExtractor->getFields());
        }
        $sort = $this->query->getFieldExtractor()->getSort();
        if (empty($sort)) {
            return true;
        }
        if (null === $this->getIndexCapableOfSorting($possibleMatches, $sort, $qwoExtractor->getFieldsWithEqualityCondition())) {
            if (!$this->allowLessEfficientIndexes) {
                return MongoDBException::queryNotEfficientlyIndexedForSorting($this->query->getClass()->name, $sort);
            }
            return MongoDBException::queryNotIndexedForSorting($this->query->getClass()->name, $sort);
        }
        return true;
    }
    
    /**
     * Gets all indexes that might be suitable for given fields
     * 
     * @param array $fieldsNames
     * @param boolean $findAny will return first matched index
     * @return array
     */
    private function getIndexesIncludingFields($fieldsNames, $findAny = false)
    {
        $matchingIndexes = array();
        $indexes = $this->collection->getIndexInfo();
        $numFields = count($fieldsNames);
        foreach ($indexes as $index) {
            // no keys or less keys are indexed than we need
            if (!isset($index['key']) || count($index['key']) < $numFields) {
                continue;
            }
            // array of index_field => position
            $indexFieldPositions = array(); $i = 0;
            foreach ($index['key'] as $field => $order) {
                $indexFieldPositions[$field] = $i++;
            }
            $matchedPositions = array();
            foreach ($fieldsNames as $field) {
                if (isset($indexFieldPositions[$field])) {
                    $matchedPositions[] = $indexFieldPositions[$field];
                } else {
                    continue 2; // field is not indexed, see next index
                }
            }
            sort($matchedPositions);
            if (empty($matchedPositions) || ($this->allowLessEfficientIndexes && $matchedPositions[0] === 0)) {
                if ($findAny) {
                    return $index;
                } else {
                    $matchingIndexes[] = $index;
                }
            }
            foreach ($matchedPositions as $i => $expected) {
                if ($i !== $expected) {
                    continue 2; // prefix is not continuous subset
                }
            }
            if ($findAny) {
                return $index;
            } else {
                $matchingIndexes[] = $index;
            }
        }
        return $matchingIndexes;
    }
    
    /**
     * Checks given indexes if they can be used for sorting results
     * and returns first matched one
     * 
     * @param array $indexes
     * @param array $sort
     * @param array $prefixPrepend
     * @return null|array
     */
    private function getIndexCapableOfSorting($indexes, $sort, $prefixPrepend = array())
    {
        $numFields = count($sort);
        foreach ($indexes as $index) {
            // no keys or less keys are indexed than we need
            if (!isset($index['key']) || count($index['key']) < $numFields) {
                continue;
            }
            // array of index_field => position
            $indexFieldPositions = array(); $i = 0;
            foreach ($index['key'] as $field => $order) {
                $indexFieldPositions[$field] = $i++;
            }
            $matchedPositions = array();
            foreach ($prefixPrepend as $p) {
                $matchedPositions[] = $indexFieldPositions[$p];
            }
            $indexToUse = array(); $currentIndexPosition=-1;
            foreach ($sort as $field => $order) {
                if (!isset($indexFieldPositions[$field])) {
                    continue 2; // field is not indexed
                }
                if ($indexFieldPositions[$field] < $currentIndexPosition) {
                    continue 2; // wrong field order in index
                }
                $currentIndexPosition = $indexFieldPositions[$field];
                $matchedPositions[] = $currentIndexPosition;
                $indexToUse[$field] = $index['key'][$field];
            }
            sort($matchedPositions);
            if (!isset($matchedPositions[0]) || $matchedPositions[0] !== 0) {
                continue; // this is not prefix
            }
            if (!$this->allowLessEfficientIndexes) {
                foreach ($matchedPositions as $i => $expected) {
                    if ($i !== $expected) {
                        continue 2; // prefix is not continuous subset
                    }
                }
            }
            // Mongo can traverse index from end to beginning as well
            $reversedIndexToUse = array_map(function($order) { return -$order; }, $indexToUse);
            if ($sort === $indexToUse || $sort === $reversedIndexToUse) {
                return $index;
            }
        }
        return null;
    }
}
