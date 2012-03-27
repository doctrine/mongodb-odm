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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\MongoDB\Cursor as BaseCursor;
use Doctrine\MongoDB\EagerCursor as BaseEagerCursor;
use Doctrine\MongoDB\LoggableCursor as BaseLoggableCursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\EagerCursor;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\MongoDB\Database;
use Doctrine\MongoDB\Collection;
use Doctrine\ODM\MongoDB\LoggableCursor;

/**
 * ODM Query wraps the raw Doctrine MongoDB queries to add additional functionality
 * and to hydrate the raw arrays of data to Doctrine document objects.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Query extends \Doctrine\MongoDB\Query\Query
{
    const HINT_REFRESH = 1;
    const HINT_SLAVE_OKAY = 2;

    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Whether or not to hydrate the results in to document objects.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     *
     * @var boolean
     */
    private $refresh = false;

    /**
     * Array of primer Closure instances.
     *
     * @var array
     */
    private $primers = array();

    /**
     * Whether or not to require indexes.
     *
     * @var bool
     */
    private $requireIndexes;

    public function __construct(DocumentManager $dm, ClassMetadata $class, Database $database, Collection $collection, array $query = array(), array $options = array(), $cmd = '$', $hydrate = true, $refresh = false, array $primers = array(), $requireIndexes = null)
    {
        parent::__construct($database, $collection, $query, $options, $cmd);
        $this->dm = $dm;
        $this->class = $class;
        $this->hydrate = $hydrate;
        $this->refresh = $refresh;
        $this->primers = $primers;
        $this->requireIndexes = $requireIndexes;
    }

    /**
     * Gets the DocumentManager instance.
     *
     * @return DocumentManager $dm
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * Gets the ClassMetadata instance.
     *
     * @return ClassMetadata $class
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets whether or not to hydrate the documents to objects.
     *
     * @param boolean $bool
     */
    public function setHydrate($bool)
    {
        $this->hydrate = $bool;
    }

    /**
     * Sets whether or not to refresh the documents data if it already exists in the identity map.
     *
     * @param boolean $bool
     */
    public function setRefresh($bool)
    {
        $this->refresh = $bool;
    }

    /**
     * Gets the fields involved in this query.
     *
     * @return array $fields An array of fields names used in this query.
     */
    public function getFieldsInQuery()
    {
        $extractor = new FieldExtractor($this->query['query'], $this->query['sort'], $this->cmd);
        return $extractor->getFields();
    }

    /**
     * Check if this query is indexed.
     *
     * @return bool
     */
    public function isIndexed()
    {
        $fields = $this->getFieldsInQuery();
        foreach ($fields as $field) {
            if (!$this->collection->isFieldIndexed($field)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets an array of the unindexed fields in this query.
     *
     * @return array $unindexedFields
     */
    public function getUnindexedFields()
    {
        $unindexedFields = array();
        $fields = $this->getFieldsInQuery();
        foreach ($fields as $field) {
            if (!$this->collection->isFieldIndexed($field)) {
                $unindexedFields[] = $field;
            }
        }
        return $unindexedFields;
    }

    /**
     * Execute the query and returns the results.
     *
     * @return mixed
     */
    public function execute()
    {
        $uow = $this->dm->getUnitOfWork();

        if ($this->isIndexRequired() && !$this->isIndexed()) {
            throw MongoDBException::queryNotIndexed($this->class->name, $this->getUnindexedFields());
        }

        $results = parent::execute();

        $hints = array();
        if ($this->refresh) {
            $hints[self::HINT_REFRESH] = true;
        }
        if ($this->query['slaveOkay'] === true) {
            $hints[self::HINT_SLAVE_OKAY] = true;
        }

        // Unwrap the BaseEagerCursor
        if ($results instanceof BaseEagerCursor) {
            $results = $results->getCursor();
        }

        // Convert the regular mongodb cursor to the odm cursor
        if ($results instanceof BaseCursor) {
            $results = $this->wrapCursor($results, $hints);
        }

        // Wrap odm cursor with EagerCursor if true
        if ($this->query['eagerCursor'] === true) {
            $results = new EagerCursor($results, $this->dm->getUnitOfWork(), $this->class);
        }

        // GeoLocationFindQuery just returns an instance of ArrayIterator so we have to
        // iterator over it and hydrate each object.
        if ($this->query['type'] === self::TYPE_GEO_LOCATION && $this->hydrate) {
            foreach ($results as $key => $result) {
                $document = $result['obj'];
                if ($this->class->distance) {
                    $document[$this->class->distance] = $result['dis'];
                }
                $results[$key] = $uow->getOrCreateDocument($this->class->name, $document, $hints);
            }
            $results->reset();
        }

        if ($this->primers) {
            $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
            foreach ($this->primers as $fieldName => $primer) {
                if ($primer) {
                    $documentPersister->primeCollection($results, $fieldName, $primer, $hints);
                }
            }
        }

        if ($this->hydrate && is_array($results) && isset($results['_id'])) {
            // Convert a single document array to a document object
            $results = $uow->getOrCreateDocument($this->class->name, $results, $hints);
        }

        return $results;
    }

    private function isIndexRequired()
    {
        if ($this->class->requireIndexes && $this->requireIndexes !== false) {
            return true;
        }
        return $this->requireIndexes !== null ? $this->requireIndexes : false;
    }

    private function wrapCursor(BaseCursor $baseCursor, array $hints)
    {
        if ($baseCursor instanceof BaseLoggableCursor) {
            $cursor = new LoggableCursor(
                $this->dm->getConnection(),
                $this->collection,
                $this->dm->getUnitOfWork(),
                $this->class,
                $baseCursor,
                $baseCursor->getQuery(),
                $baseCursor->getFields(),
                $this->dm->getConfiguration()->getRetryQuery(),
                $baseCursor->getLoggerCallable()
            );
        } else {
            $cursor = new Cursor(
                $this->dm->getConnection(),
                $this->collection,
                $this->dm->getUnitOfWork(),
                $this->class,
                $baseCursor,
                $baseCursor->getQuery(),
                $baseCursor->getFields(),
                $this->dm->getConfiguration()->getRetryQuery()
            );
        }
        $cursor->hydrate($this->hydrate);
        $cursor->setHints($hints);

        return $cursor;
    }
}