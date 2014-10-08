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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
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
     * The ClassMetadataInfo instance.
     *
     * @var ClassMetadataInfo
     */
    private $class;
    
    /**
     * Indexes
     * 
     * @var array
     */
    private $indexes;
    
    /**
     * The Query instance.
     *
     * @var Query
     */
    private $query;
    
    /**
     * Constructor
     * 
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
        $this->class = $query->getClass();
    }
    
    /**
     * Checks if given Query can use any existing Index
     * 
     * @return true|MongoDbException
     */
    public function run()
    {
        // $or clausules executes in parallel and may use other index
        $orClauses = $this->query->getFieldExtractor()->getOrClauses();
        foreach ($orClauses as $or) {
            $orExtractor = new FieldExtractor($or);
            if (!$this->getIndexesIncludingFields($orExtractor->getFields(), true)) {
                return MongoDBException::orClauseNotIndexed($this->query->getClass()->name, $orExtractor->getFields());
            }
        }
        $queryWithourOrs = $this->query->getFieldExtractor()->getQueryWithoutOrClauses();
        $qwoExtractor = new FieldExtractor($queryWithourOrs);
        $usedFields = $qwoExtractor->getFields();
        if (empty($usedFields)) {
            $possibleMatches = $this->getIndexes();
        } else {
            $possibleMatches = $this->getIndexesIncludingFields($usedFields);
        }
        if (empty($possibleMatches)) {
            return MongoDBException::queryNotIndexed($this->query->getClass()->name, $qwoExtractor->getFields());
        }
        $sort = $this->query->getFieldExtractor()->getSort();
        if (empty($sort)) {
            return true;
        }
        if (null === $this->getIndexCapableOfSorting($possibleMatches, $sort, $qwoExtractor->getFieldsWithEqualityCondition())) {
            return MongoDBException::queryNotIndexedForSorting($this->query->getClass()->name, $sort);
        }
        return true;
    }
    
    /**
     * Gets all indexes for queried Document
     * 
     * @return array
     */
    private function getIndexes()
    {
        if ($this->indexes === null) {
            // mongo always has index on _id => 1 and it is not
            // included in ClassMetadataInfo::getIndexes
            $this->indexes = array_merge(
                array(array("keys" => array('_id' => 1))),
                $this->class->getIndexes()
            );
        }
        return $this->indexes;
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
        $indexes = $this->getIndexes();
        $numFields = count($fieldsNames);
        foreach ($indexes as $index) {
            // no keys or less keys are indexed than we need
            if (!isset($index['keys']) || count($index['keys']) < $numFields) {
                continue;
            }
            // array of index_field => position
            $indexFieldPositions = array(); $i = 0;
            foreach ($index['keys'] as $field => $order) {
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
            if ($matchedPositions[0] === 0) {
                if ($findAny) {
                    return $index;
                } else {
                    $matchingIndexes[] = $index;
                }
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
            if (!isset($index['keys']) || count($index['keys']) < $numFields) {
                continue;
            }
            // array of index_field => position
            $indexFieldPositions = array(); $i = 0;
            foreach ($index['keys'] as $field => $order) {
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
                $indexToUse[$field] = $index['keys'][$field];
            }
            sort($matchedPositions);
            if (!isset($matchedPositions[0]) || $matchedPositions[0] !== 0) {
                continue; // this is not prefix
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
