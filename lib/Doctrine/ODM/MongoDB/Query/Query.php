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
use Doctrine\MongoDB\Cursor as BaseCursor;
use Doctrine\MongoDB\CursorInterface;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\EagerCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * ODM Query wraps the raw Doctrine MongoDB queries to add additional functionality
 * and to hydrate the raw arrays of data to Doctrine document objects.
 *
 * @since       1.0
 */
class Query extends \Doctrine\MongoDB\Query\Query
{
    const HINT_REFRESH = 1;
    const HINT_SLAVE_OKAY = 2;
    const HINT_READ_PREFERENCE = 3;
    const HINT_READ_PREFERENCE_TAGS = 4;
    const HINT_READ_ONLY = 5;

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
     * Whether to hydrate results as document class instances.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Array of primer Closure instances.
     *
     * @var array
     */
    private $primers = array();

    /**
     * Whether or not to require indexes.
     *
     * @var boolean
     */
    private $requireIndexes;

    /**
     * Hints for UnitOfWork behavior.
     *
     * @var array
     */
    private $unitOfWorkHints = array();

    /**
     * Constructor.
     *
     * @param DocumentManager $dm
     * @param ClassMetadata $class
     * @param Collection $collection
     * @param array $query
     * @param array $options
     * @param boolean $hydrate
     * @param boolean $refresh
     * @param array $primers
     * @param null $requireIndexes
     * @param boolean $readOnly
     */
    public function __construct(DocumentManager $dm, ClassMetadata $class, Collection $collection, array $query = array(), array $options = array(), $hydrate = true, $refresh = false, array $primers = array(), $requireIndexes = null, $readOnly = false)
    {
        $primers = array_filter($primers);

        if ( ! empty($primers)) {
            $query['eagerCursor'] = true;
        }

        if ( ! empty($query['eagerCursor'])) {
            $query['useIdentifierKeys'] = false;
        }

        parent::__construct($collection, $query, $options);
        $this->dm = $dm;
        $this->class = $class;
        $this->hydrate = $hydrate;
        $this->primers = $primers;
        $this->requireIndexes = $requireIndexes;

        $this->setReadOnly($readOnly);
        $this->setRefresh($refresh);

        if (isset($query['slaveOkay'])) {
            $this->unitOfWorkHints[self::HINT_SLAVE_OKAY] = $query['slaveOkay'];
        }

        if (isset($query['readPreference'])) {
            $this->unitOfWorkHints[self::HINT_READ_PREFERENCE] = $query['readPreference'];
            $this->unitOfWorkHints[self::HINT_READ_PREFERENCE_TAGS] = $query['readPreferenceTags'];
        }
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
     * @param boolean $hydrate
     */
    public function setHydrate($hydrate)
    {
        $this->hydrate = (boolean) $hydrate;
    }

    /**
     * Set whether documents should be registered in UnitOfWork. If document would
     * already be managed it will be left intact and new instance returned.
     * 
     * This option has no effect if hydration is disabled.
     * 
     * @param boolean $readOnly
     */
    public function setReadOnly($readOnly)
    {
        $this->unitOfWorkHints[Query::HINT_READ_ONLY] = (boolean) $readOnly;
    }

    /**
     * Set whether to refresh hydrated documents that are already in the
     * identity map.
     *
     * This option has no effect if hydration is disabled.
     *
     * @param boolean $refresh
     */
    public function setRefresh($refresh)
    {
        $this->unitOfWorkHints[Query::HINT_REFRESH] = (boolean) $refresh;
    }

    /**
     * Gets the fields involved in this query.
     *
     * @return array $fields An array of fields names used in this query.
     */
    public function getFieldsInQuery()
    {
        $query = isset($this->query['query']) ? $this->query['query'] : array();
        $sort = isset($this->query['sort']) ? $this->query['sort'] : array();

        $extractor = new FieldExtractor($query, $sort);
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
            if ( ! $this->collection->isFieldIndexed($field)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets an array of the unindexed fields in this query.
     *
     * @return array
     */
    public function getUnindexedFields()
    {
        $unindexedFields = array();
        $fields = $this->getFieldsInQuery();
        foreach ($fields as $field) {
            if ( ! $this->collection->isFieldIndexed($field)) {
                $unindexedFields[] = $field;
            }
        }
        return $unindexedFields;
    }

    /**
     * Execute the query and returns the results.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @return mixed
     */
    public function execute()
    {
        if ($this->isIndexRequired() && ! $this->isIndexed()) {
            throw MongoDBException::queryNotIndexed($this->class->name, $this->getUnindexedFields());
        }

        $results = parent::execute();

        if ( ! $this->hydrate) {
            return $results;
        }

        $uow = $this->dm->getUnitOfWork();

        /* A geoNear command returns an ArrayIterator, where each result is an
         * object with "dis" (computed distance) and "obj" (original document)
         * properties. If hydration is enabled, eagerly hydrate these results.
         *
         * Other commands results are not handled, since their results may not
         * resemble documents in the collection.
         */
        if ($this->query['type'] === self::TYPE_GEO_NEAR) {
            foreach ($results as $key => $result) {
                $document = $result['obj'];
                if ($this->class->distance !== null) {
                    $document[$this->class->distance] = $result['dis'];
                }
                $results[$key] = $uow->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints);
            }
            $results->reset();
        }

        /* If a single document is returned from a findAndModify command and it
         * includes the identifier field, attempt hydration.
         */
        if (($this->query['type'] === self::TYPE_FIND_AND_UPDATE ||
             $this->query['type'] === self::TYPE_FIND_AND_REMOVE) &&
            is_array($results) && isset($results['_id'])) {

            $results = $uow->getOrCreateDocument($this->class->name, $results, $this->unitOfWorkHints);

            if ( ! empty($this->primers)) {
                $referencePrimer = new ReferencePrimer($this->dm, $uow);

                foreach ($this->primers as $fieldName => $primer) {
                    $primer = is_callable($primer) ? $primer : null;
                    $referencePrimer->primeReferences($this->class, array($results), $fieldName, $this->unitOfWorkHints, $primer);
                }
            }
        }

        return $results;
    }

    /**
     * Prepare the Cursor returned by {@link Query::execute()}.
     *
     * This method will wrap the base Cursor with an ODM Cursor or EagerCursor,
     * and set the hydrate option and UnitOfWork hints. This occurs in addition
     * to any preparation done by the base Query class.
     *
     * @see \Doctrine\MongoDB\Cursor::prepareCursor()
     * @param BaseCursor $cursor
     * @return CursorInterface
     */
    protected function prepareCursor(BaseCursor $cursor)
    {
        $cursor = parent::prepareCursor($cursor);

        // Convert the base Cursor into an ODM Cursor
        $cursorClass = ( ! empty($this->query['eagerCursor'])) ? EagerCursor::class : Cursor::class;
        $cursor = new $cursorClass($cursor, $this->dm->getUnitOfWork(), $this->class);

        $cursor->hydrate($this->hydrate);
        $cursor->setHints($this->unitOfWorkHints);

        if ( ! empty($this->primers)) {
            $referencePrimer = new ReferencePrimer($this->dm, $this->dm->getUnitOfWork());
            $cursor->enableReferencePriming($this->primers, $referencePrimer);
        }

        return $cursor;
    }

    /**
     * Return whether queries on this document should require indexes.
     *
     * @return boolean
     */
    private function isIndexRequired()
    {
        return $this->requireIndexes !== null ? $this->requireIndexes : $this->class->requireIndexes;
    }
}
