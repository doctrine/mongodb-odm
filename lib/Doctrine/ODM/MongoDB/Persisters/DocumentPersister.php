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

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\MongoDB\CursorInterface;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * The DocumentPersister is responsible for persisting documents.
 *
 * @since       1.0
 */
class DocumentPersister
{
    /**
     * The PersistenceBuilder instance.
     *
     * @var PersistenceBuilder
     */
    private $pb;

    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The EventManager instance
     *
     * @var EventManager
     */
    private $evm;

    /**
     * The UnitOfWork instance.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * The ClassMetadata instance for the document type being persisted.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * The MongoCollection instance for this document.
     *
     * @var \MongoCollection
     */
    private $collection;

    /**
     * Array of queued inserts for the persister to insert.
     *
     * @var array
     */
    private $queuedInserts = array();

    /**
     * Array of queued inserts for the persister to insert.
     *
     * @var array
     */
    private $queuedUpserts = array();

    /**
     * The CriteriaMerger instance.
     *
     * @var CriteriaMerger
     */
    private $cm;

    /**
     * The CollectionPersister instance.
     *
     * @var CollectionPersister
     */
    private $cp;

    /**
     * Initializes this instance.
     *
     * @param PersistenceBuilder $pb
     * @param DocumentManager $dm
     * @param EventManager $evm
     * @param UnitOfWork $uow
     * @param HydratorFactory $hydratorFactory
     * @param ClassMetadata $class
     * @param CriteriaMerger $cm
     */
    public function __construct(
        PersistenceBuilder $pb,
        DocumentManager $dm,
        EventManager $evm,
        UnitOfWork $uow,
        HydratorFactory $hydratorFactory,
        ClassMetadata $class,
        CriteriaMerger $cm = null
    ) {
        $this->pb = $pb;
        $this->dm = $dm;
        $this->evm = $evm;
        $this->cm = $cm ?: new CriteriaMerger();
        $this->uow = $uow;
        $this->hydratorFactory = $hydratorFactory;
        $this->class = $class;
        $this->collection = $dm->getDocumentCollection($class->name);
        $this->cp = $this->uow->getCollectionPersister();
    }

    /**
     * @return array
     */
    public function getInserts()
    {
        return $this->queuedInserts;
    }

    /**
     * @param object $document
     * @return bool
     */
    public function isQueuedForInsert($document)
    {
        return isset($this->queuedInserts[spl_object_hash($document)]);
    }

    /**
     * Adds a document to the queued insertions.
     * The document remains queued until {@link executeInserts} is invoked.
     *
     * @param object $document The document to queue for insertion.
     */
    public function addInsert($document)
    {
        $this->queuedInserts[spl_object_hash($document)] = $document;
    }

    /**
     * @return array
     */
    public function getUpserts()
    {
        return $this->queuedUpserts;
    }

    /**
     * @param object $document
     * @return boolean
     */
    public function isQueuedForUpsert($document)
    {
        return isset($this->queuedUpserts[spl_object_hash($document)]);
    }

    /**
     * Adds a document to the queued upserts.
     * The document remains queued until {@link executeUpserts} is invoked.
     *
     * @param object $document The document to queue for insertion.
     */
    public function addUpsert($document)
    {
        $this->queuedUpserts[spl_object_hash($document)] = $document;
    }

    /**
     * Gets the ClassMetadata instance of the document class this persister is used for.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Executes all queued document insertions.
     *
     * Queued documents without an ID will inserted in a batch and queued
     * documents with an ID will be upserted individually.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @param array $options Options for batchInsert() and update() driver methods
     */
    public function executeInserts(array $options = array())
    {
        if ( ! $this->queuedInserts) {
            return;
        }

        $inserts = array();
        $options = $this->getWriteOptions($options);
        foreach ($this->queuedInserts as $oid => $document) {
            $data = $this->pb->prepareInsertData($document);

            // Set the initial version for each insert
            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                if ($versionMapping['type'] === 'int') {
                    $nextVersion = max(1, (int) $this->class->reflFields[$this->class->versionField]->getValue($document));
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
                } elseif ($versionMapping['type'] === 'date') {
                    $nextVersionDateTime = new \DateTime();
                    $nextVersion = new \MongoDate($nextVersionDateTime->getTimestamp());
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersionDateTime);
                }
                $data[$versionMapping['name']] = $nextVersion;
            }

            $inserts[$oid] = $data;
        }

        if ($inserts) {
            try {
                $this->collection->batchInsert($inserts, $options);
            } catch (\MongoException $e) {
                $this->queuedInserts = array();
                throw $e;
            }
        }

        /* All collections except for ones using addToSet have already been
         * saved. We have left these to be handled separately to avoid checking
         * collection for uniqueness on PHP side.
         */
        foreach ($this->queuedInserts as $document) {
            $this->handleCollections($document, $options);
        }

        $this->queuedInserts = array();
    }

    /**
     * Executes all queued document upserts.
     *
     * Queued documents with an ID are upserted individually.
     *
     * If no upserts are queued, invoking this method is a NOOP.
     *
     * @param array $options Options for batchInsert() and update() driver methods
     */
    public function executeUpserts(array $options = array())
    {
        if ( ! $this->queuedUpserts) {
            return;
        }

        $options = $this->getWriteOptions($options);
        foreach ($this->queuedUpserts as $oid => $document) {
            try {
                $this->executeUpsert($document, $options);
                $this->handleCollections($document, $options);
                unset($this->queuedUpserts[$oid]);
            } catch (\MongoException $e) {
                unset($this->queuedUpserts[$oid]);
                throw $e;
            }
        }
    }

    /**
     * Executes a single upsert in {@link executeUpserts}
     *
     * @param object $document
     * @param array  $options
     */
    private function executeUpsert($document, array $options)
    {
        $options['upsert'] = true;
        $criteria = $this->getQueryForDocument($document);

        $data = $this->pb->prepareUpsertData($document);

        // Set the initial version for each upsert
        if ($this->class->isVersioned) {
            $versionMapping = $this->class->fieldMappings[$this->class->versionField];
            if ($versionMapping['type'] === 'int') {
                $nextVersion = max(1, (int) $this->class->reflFields[$this->class->versionField]->getValue($document));
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
            } elseif ($versionMapping['type'] === 'date') {
                $nextVersionDateTime = new \DateTime();
                $nextVersion = new \MongoDate($nextVersionDateTime->getTimestamp());
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersionDateTime);
            }
            $data['$set'][$versionMapping['name']] = $nextVersion;
        }

        foreach (array_keys($criteria) as $field) {
            unset($data['$set'][$field]);
        }

        // Do not send an empty $set modifier
        if (empty($data['$set'])) {
            unset($data['$set']);
        }

        /* If there are no modifiers remaining, we're upserting a document with
         * an identifier as its only field. Since a document with the identifier
         * may already exist, the desired behavior is "insert if not exists" and
         * NOOP otherwise. MongoDB 2.6+ does not allow empty modifiers, so $set
         * the identifier to the same value in our criteria.
         *
         * This will fail for versions before MongoDB 2.6, which require an
         * empty $set modifier. The best we can do (without attempting to check
         * server versions in advance) is attempt the 2.6+ behavior and retry
         * after the relevant exception.
         *
         * See: https://jira.mongodb.org/browse/SERVER-12266
         */
        if (empty($data)) {
            $retry = true;
            $data = array('$set' => array('_id' => $criteria['_id']));
        }

        try {
            $this->collection->update($criteria, $data, $options);
            return;
        } catch (\MongoCursorException $e) {
            if (empty($retry) || strpos($e->getMessage(), 'Mod on _id not allowed') === false) {
                throw $e;
            }
        }

        $this->collection->update($criteria, array('$set' => new \stdClass), $options);
    }

    /**
     * Updates the already persisted document if it has any new changesets.
     *
     * @param object $document
     * @param array $options Array of options to be used with update()
     * @throws \Doctrine\ODM\MongoDB\LockException
     */
    public function update($document, array $options = array())
    {
        $update = $this->pb->prepareUpdateData($document);

        $query = $this->getQueryForDocument($document);

        foreach (array_keys($query) as $field) {
            unset($update['$set'][$field]);
        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }


        // Include versioning logic to set the new version value in the database
        // and to ensure the version has not changed since this document object instance
        // was fetched from the database
        if ($this->class->isVersioned) {
            $versionMapping = $this->class->fieldMappings[$this->class->versionField];
            $currentVersion = $this->class->reflFields[$this->class->versionField]->getValue($document);
            if ($versionMapping['type'] === 'int') {
                $nextVersion = $currentVersion + 1;
                $update['$inc'][$versionMapping['name']] = 1;
                $query[$versionMapping['name']] = $currentVersion;
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
            } elseif ($versionMapping['type'] === 'date') {
                $nextVersion = new \DateTime();
                $update['$set'][$versionMapping['name']] = new \MongoDate($nextVersion->getTimestamp());
                $query[$versionMapping['name']] = new \MongoDate($currentVersion->getTimestamp());
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
            }
        }

        if ( ! empty($update)) {
            // Include locking logic so that if the document object in memory is currently
            // locked then it will remove it, otherwise it ensures the document is not locked.
            if ($this->class->isLockable) {
                $isLocked = $this->class->reflFields[$this->class->lockField]->getValue($document);
                $lockMapping = $this->class->fieldMappings[$this->class->lockField];
                if ($isLocked) {
                    $update['$unset'] = array($lockMapping['name'] => true);
                } else {
                    $query[$lockMapping['name']] = array('$exists' => false);
                }
            }

            $options = $this->getWriteOptions($options);

            $result = $this->collection->update($query, $update, $options);

            if (($this->class->isVersioned || $this->class->isLockable) && ! $result['n']) {
                throw LockException::lockFailed($document);
            }
        }

        $this->handleCollections($document, $options);
    }

    /**
     * Removes document from mongo
     *
     * @param mixed $document
     * @param array $options Array of options to be used with remove()
     * @throws \Doctrine\ODM\MongoDB\LockException
     */
    public function delete($document, array $options = array())
    {
        $query = $this->getQueryForDocument($document);

        if ($this->class->isLockable) {
            $query[$this->class->lockField] = array('$exists' => false);
        }

        $options = $this->getWriteOptions($options);

        $result = $this->collection->remove($query, $options);

        if (($this->class->isVersioned || $this->class->isLockable) && ! $result['n']) {
            throw LockException::lockFailed($document);
        }
    }

    /**
     * Refreshes a managed document.
     *
     * @param string $id
     * @param object $document The document to refresh.
     *
     * @deprecated The first argument is deprecated.
     */
    public function refresh($id, $document)
    {
        $query = $this->getQueryForDocument($document);
        $data = $this->collection->findOne($query);
        $data = $this->hydratorFactory->hydrate($document, $data);
        $this->uow->setOriginalDocumentData($document, $data);
    }

    /**
     * Finds a document by a set of criteria.
     *
     * If a scalar or MongoId is provided for $criteria, it will be used to
     * match an _id value.
     *
     * @param mixed   $criteria Query criteria
     * @param object  $document Document to load the data into. If not specified, a new document is created.
     * @param array   $hints    Hints for document creation
     * @param integer $lockMode
     * @param array   $sort     Sort array for Cursor::sort()
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @return object|null The loaded and managed document instance or null if no document was found
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load($criteria, $document = null, array $hints = array(), $lockMode = 0, array $sort = null)
    {
        // TODO: remove this
        if ($criteria === null || is_scalar($criteria) || $criteria instanceof \MongoId) {
            $criteria = array('_id' => $criteria);
        }

        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);

        $cursor = $this->collection->find($criteria);

        if (null !== $sort) {
            $cursor->sort($this->prepareSortOrProjection($sort));
        }

        $result = $cursor->getSingleResult();

        if ($this->class->isLockable) {
            $lockMapping = $this->class->fieldMappings[$this->class->lockField];
            if (isset($result[$lockMapping['name']]) && $result[$lockMapping['name']] === LockMode::PESSIMISTIC_WRITE) {
                throw LockException::lockFailed($result);
            }
        }

        return $this->createDocument($result, $document, $hints);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array        $criteria Query criteria
     * @param array        $sort     Sort array for Cursor::sort()
     * @param integer|null $limit    Limit for Cursor::limit()
     * @param integer|null $skip     Skip for Cursor::skip()
     * @return Cursor
     */
    public function loadAll(array $criteria = array(), array $sort = null, $limit = null, $skip = null)
    {
        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);

        $baseCursor = $this->collection->find($criteria);
        $cursor = $this->wrapCursor($baseCursor);

        if (null !== $sort) {
            $cursor->sort($sort);
        }

        if (null !== $limit) {
            $cursor->limit($limit);
        }

        if (null !== $skip) {
            $cursor->skip($skip);
        }

        return $cursor;
    }

    /**
     * @param object $document
     *
     * @return array
     * @throws MongoDBException
     */
    private function getShardKeyQuery($document)
    {
        if ( ! $this->class->isSharded()) {
            return array();
        }

        $shardKey = $this->class->getShardKey();
        $keys = array_keys($shardKey['keys']);
        $data = $this->uow->getDocumentActualData($document);

        $shardKeyQueryPart = array();
        foreach ($keys as $key) {
            $mapping = $this->class->getFieldMappingByDbFieldName($key);
            $this->guardMissingShardKey($document, $key, $data);
            $value = Type::getType($mapping['type'])->convertToDatabaseValue($data[$mapping['fieldName']]);
            $shardKeyQueryPart[$key] = $value;
        }

        return $shardKeyQueryPart;
    }

    /**
     * Wraps the supplied base cursor in the corresponding ODM class.
     *
     * @param CursorInterface $baseCursor
     * @return Cursor
     */
    private function wrapCursor(CursorInterface $baseCursor)
    {
        return new Cursor($baseCursor, $this->dm->getUnitOfWork(), $this->class);
    }

    /**
     * Checks whether the given managed document exists in the database.
     *
     * @param object $document
     * @return boolean TRUE if the document exists in the database, FALSE otherwise.
     */
    public function exists($document)
    {
        $id = $this->class->getIdentifierObject($document);
        return (boolean) $this->collection->findOne(array('_id' => $id), array('_id'));
    }

    /**
     * Locks document by storing the lock mode on the mapped lock field.
     *
     * @param object $document
     * @param int $lockMode
     */
    public function lock($document, $lockMode)
    {
        $id = $this->uow->getDocumentIdentifier($document);
        $criteria = array('_id' => $this->class->getDatabaseIdentifierValue($id));
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        $this->collection->update($criteria, array('$set' => array($lockMapping['name'] => $lockMode)));
        $this->class->reflFields[$this->class->lockField]->setValue($document, $lockMode);
    }

    /**
     * Releases any lock that exists on this document.
     *
     * @param object $document
     */
    public function unlock($document)
    {
        $id = $this->uow->getDocumentIdentifier($document);
        $criteria = array('_id' => $this->class->getDatabaseIdentifierValue($id));
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        $this->collection->update($criteria, array('$unset' => array($lockMapping['name'] => true)));
        $this->class->reflFields[$this->class->lockField]->setValue($document, null);
    }

    /**
     * Creates or fills a single document object from an query result.
     *
     * @param object $result The query result.
     * @param object $document The document object to fill, if any.
     * @param array $hints Hints for document creation.
     * @return object The filled and managed document object or NULL, if the query result is empty.
     */
    private function createDocument($result, $document = null, array $hints = array())
    {
        if ($result === null) {
            return null;
        }

        if ($document !== null) {
            $hints[Query::HINT_REFRESH] = true;
            $id = $this->class->getPHPIdentifierValue($result['_id']);
            $this->uow->registerManaged($document, $id, $result);
        }

        return $this->uow->getOrCreateDocument($this->class->name, $result, $hints, $document);
    }

    /**
     * Loads a PersistentCollection data. Used in the initialize() method.
     *
     * @param PersistentCollectionInterface $collection
     */
    public function loadCollection(PersistentCollectionInterface $collection)
    {
        $mapping = $collection->getMapping();
        switch ($mapping['association']) {
            case ClassMetadata::EMBED_MANY:
                $this->loadEmbedManyCollection($collection);
                break;

            case ClassMetadata::REFERENCE_MANY:
                if (isset($mapping['repositoryMethod']) && $mapping['repositoryMethod']) {
                    $this->loadReferenceManyWithRepositoryMethod($collection);
                } else {
                    if ($mapping['isOwningSide']) {
                        $this->loadReferenceManyCollectionOwningSide($collection);
                    } else {
                        $this->loadReferenceManyCollectionInverseSide($collection);
                    }
                }
                break;
        }
    }

    private function loadEmbedManyCollection(PersistentCollectionInterface $collection)
    {
        $embeddedDocuments = $collection->getMongoData();
        $mapping = $collection->getMapping();
        $owner = $collection->getOwner();
        if ($embeddedDocuments) {
            foreach ($embeddedDocuments as $key => $embeddedDocument) {
                $className = $this->uow->getClassNameForAssociation($mapping, $embeddedDocument);
                $embeddedMetadata = $this->dm->getClassMetadata($className);
                $embeddedDocumentObject = $embeddedMetadata->newInstance();

                $this->uow->setParentAssociation($embeddedDocumentObject, $mapping, $owner, $mapping['name'] . '.' . $key);

                $data = $this->hydratorFactory->hydrate($embeddedDocumentObject, $embeddedDocument, $collection->getHints());
                $id = $embeddedMetadata->identifier && isset($data[$embeddedMetadata->identifier])
                    ? $data[$embeddedMetadata->identifier]
                    : null;
                
                if (empty($collection->getHints()[Query::HINT_READ_ONLY])) {
                    $this->uow->registerManaged($embeddedDocumentObject, $id, $data);
                }
                if (CollectionHelper::isHash($mapping['strategy'])) {
                    $collection->set($key, $embeddedDocumentObject);
                } else {
                    $collection->add($embeddedDocumentObject);
                }
            }
        }
    }

    private function loadReferenceManyCollectionOwningSide(PersistentCollectionInterface $collection)
    {
        $hints = $collection->getHints();
        $mapping = $collection->getMapping();
        $groupedIds = array();

        $sorted = isset($mapping['sort']) && $mapping['sort'];

        foreach ($collection->getMongoData() as $key => $reference) {
            if (isset($mapping['storeAs']) && $mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                $className = $mapping['targetDocument'];
                $mongoId = $reference;
            } else {
                $className = $this->uow->getClassNameForAssociation($mapping, $reference);
                $mongoId = $reference['$id'];
            }
            $id = $this->dm->getClassMetadata($className)->getPHPIdentifierValue($mongoId);

            // create a reference to the class and id
            $reference = $this->dm->getReference($className, $id);

            // no custom sort so add the references right now in the order they are embedded
            if ( ! $sorted) {
                if (CollectionHelper::isHash($mapping['strategy'])) {
                    $collection->set($key, $reference);
                } else {
                    $collection->add($reference);
                }
            }

            // only query for the referenced object if it is not already initialized or the collection is sorted
            if (($reference instanceof Proxy && ! $reference->__isInitialized__) || $sorted) {
                $groupedIds[$className][] = $mongoId;
            }
        }
        foreach ($groupedIds as $className => $ids) {
            $class = $this->dm->getClassMetadata($className);
            $mongoCollection = $this->dm->getDocumentCollection($className);
            $criteria = $this->cm->merge(
                array('_id' => array('$in' => array_values($ids))),
                $this->dm->getFilterCollection()->getFilterCriteria($class),
                isset($mapping['criteria']) ? $mapping['criteria'] : array()
            );
            $criteria = $this->uow->getDocumentPersister($className)->prepareQueryOrNewObj($criteria);
            $cursor = $mongoCollection->find($criteria);
            if (isset($mapping['sort'])) {
                $cursor->sort($mapping['sort']);
            }
            if (isset($mapping['limit'])) {
                $cursor->limit($mapping['limit']);
            }
            if (isset($mapping['skip'])) {
                $cursor->skip($mapping['skip']);
            }
            if ( ! empty($hints[Query::HINT_SLAVE_OKAY])) {
                $cursor->slaveOkay(true);
            }
            if ( ! empty($hints[Query::HINT_READ_PREFERENCE])) {
                $cursor->setReadPreference($hints[Query::HINT_READ_PREFERENCE], $hints[Query::HINT_READ_PREFERENCE_TAGS]);
            }
            $documents = $cursor->toArray(false);
            foreach ($documents as $documentData) {
                $document = $this->uow->getById($documentData['_id'], $class);
                if ($document instanceof Proxy && ! $document->__isInitialized()) {
                    $data = $this->hydratorFactory->hydrate($document, $documentData);
                    $this->uow->setOriginalDocumentData($document, $data);
                    $document->__isInitialized__ = true;
                }
                if ($sorted) {
                    $collection->add($document);
                }
            }
        }
    }

    private function loadReferenceManyCollectionInverseSide(PersistentCollectionInterface $collection)
    {
        $query = $this->createReferenceManyInverseSideQuery($collection);
        $documents = $query->execute()->toArray(false);
        foreach ($documents as $key => $document) {
            $collection->add($document);
        }
    }

    /**
     * @param PersistentCollectionInterface $collection
     *
     * @return Query
     */
    public function createReferenceManyInverseSideQuery(PersistentCollectionInterface $collection)
    {
        $hints = $collection->getHints();
        $mapping = $collection->getMapping();
        $owner = $collection->getOwner();
        $ownerClass = $this->dm->getClassMetadata(get_class($owner));
        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        $mappedByMapping = isset($targetClass->fieldMappings[$mapping['mappedBy']]) ? $targetClass->fieldMappings[$mapping['mappedBy']] : array();
        $mappedByFieldName = isset($mappedByMapping['storeAs']) && $mappedByMapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID ? $mapping['mappedBy'] : $mapping['mappedBy'] . '.$id';
        $criteria = $this->cm->merge(
            array($mappedByFieldName => $ownerClass->getIdentifierObject($owner)),
            $this->dm->getFilterCollection()->getFilterCriteria($targetClass),
            isset($mapping['criteria']) ? $mapping['criteria'] : array()
        );
        $criteria = $this->uow->getDocumentPersister($mapping['targetDocument'])->prepareQueryOrNewObj($criteria);
        $qb = $this->dm->createQueryBuilder($mapping['targetDocument'])
            ->setQueryArray($criteria);

        if (isset($mapping['sort'])) {
            $qb->sort($mapping['sort']);
        }
        if (isset($mapping['limit'])) {
            $qb->limit($mapping['limit']);
        }
        if (isset($mapping['skip'])) {
            $qb->skip($mapping['skip']);
        }
        if ( ! empty($hints[Query::HINT_SLAVE_OKAY])) {
            $qb->slaveOkay(true);
        }
        if ( ! empty($hints[Query::HINT_READ_PREFERENCE])) {
            $qb->setReadPreference($hints[Query::HINT_READ_PREFERENCE], $hints[Query::HINT_READ_PREFERENCE_TAGS]);
        }

        return $qb->getQuery();
    }

    private function loadReferenceManyWithRepositoryMethod(PersistentCollectionInterface $collection)
    {
        $cursor = $this->createReferenceManyWithRepositoryMethodCursor($collection);
        $mapping = $collection->getMapping();
        $documents = $cursor->toArray(false);
        foreach ($documents as $key => $obj) {
            if (CollectionHelper::isHash($mapping['strategy'])) {
                $collection->set($key, $obj);
            } else {
                $collection->add($obj);
            }
        }
    }

    /**
     * @param PersistentCollectionInterface $collection
     *
     * @return CursorInterface
     */
    public function createReferenceManyWithRepositoryMethodCursor(PersistentCollectionInterface $collection)
    {
        $hints = $collection->getHints();
        $mapping = $collection->getMapping();
        $repositoryMethod = $mapping['repositoryMethod'];
        $cursor = $this->dm->getRepository($mapping['targetDocument'])
            ->$repositoryMethod($collection->getOwner());

        if ( ! $cursor instanceof CursorInterface) {
            throw new \BadMethodCallException("Expected repository method {$repositoryMethod} to return a CursorInterface");
        }

        if (isset($mapping['sort'])) {
            $cursor->sort($mapping['sort']);
        }
        if (isset($mapping['limit'])) {
            $cursor->limit($mapping['limit']);
        }
        if (isset($mapping['skip'])) {
            $cursor->skip($mapping['skip']);
        }
        if ( ! empty($hints[Query::HINT_SLAVE_OKAY])) {
            $cursor->slaveOkay(true);
        }
        if ( ! empty($hints[Query::HINT_READ_PREFERENCE])) {
            $cursor->setReadPreference($hints[Query::HINT_READ_PREFERENCE], $hints[Query::HINT_READ_PREFERENCE_TAGS]);
        }

        return $cursor;
    }

    /**
     * Prepare a sort or projection array by converting keys, which are PHP
     * property names, to MongoDB field names.
     *
     * @param array $fields
     * @return array
     */
    public function prepareSortOrProjection(array $fields)
    {
        $preparedFields = array();

        foreach ($fields as $key => $value) {
            $preparedFields[$this->prepareFieldName($key)] = $value;
        }

        return $preparedFields;
    }

    /**
     * Prepare a mongodb field name and convert the PHP property names to MongoDB field names.
     *
     * @param string $fieldName
     * @return string
     */
    public function prepareFieldName($fieldName)
    {
        $fieldNames = $this->prepareQueryElement($fieldName, null, null, false);

        return $fieldNames[0][0];
    }

    /**
     * Adds discriminator criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called before
     * {@link DocumentPerister::addFilterToPreparedQuery()}.
     *
     * @param array $preparedQuery
     * @return array
     */
    public function addDiscriminatorToPreparedQuery(array $preparedQuery)
    {
        /* If the class has a discriminator field, which is not already in the
         * criteria, inject it now. The field/values need no preparation.
         */
        if ($this->class->hasDiscriminator() && ! isset($preparedQuery[$this->class->discriminatorField])) {
            $discriminatorValues = $this->getClassDiscriminatorValues($this->class);
            if (count($discriminatorValues) === 1) {
                $preparedQuery[$this->class->discriminatorField] = $discriminatorValues[0];
            } else {
                $preparedQuery[$this->class->discriminatorField] = array('$in' => $discriminatorValues);
            }
        }

        return $preparedQuery;
    }

    /**
     * Adds filter criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called after
     * {@link DocumentPerister::addDiscriminatorToPreparedQuery()}.
     *
     * @param array $preparedQuery
     * @return array
     */
    public function addFilterToPreparedQuery(array $preparedQuery)
    {
        /* If filter criteria exists for this class, prepare it and merge
         * over the existing query.
         *
         * @todo Consider recursive merging in case the filter criteria and
         * prepared query both contain top-level $and/$or operators.
         */
        if ($filterCriteria = $this->dm->getFilterCollection()->getFilterCriteria($this->class)) {
            $preparedQuery = $this->cm->merge($preparedQuery, $this->prepareQueryOrNewObj($filterCriteria));
        }

        return $preparedQuery;
    }

    /**
     * Prepares the query criteria or new document object.
     *
     * PHP field names and types will be converted to those used by MongoDB.
     *
     * @param array $query
     * @param bool $isNewObj
     * @return array
     */
    public function prepareQueryOrNewObj(array $query, $isNewObj = false)
    {
        $preparedQuery = array();

        foreach ($query as $key => $value) {
            // Recursively prepare logical query clauses
            if (in_array($key, array('$and', '$or', '$nor')) && is_array($value)) {
                foreach ($value as $k2 => $v2) {
                    $preparedQuery[$key][$k2] = $this->prepareQueryOrNewObj($v2, $isNewObj);
                }
                continue;
            }

            if (isset($key[0]) && $key[0] === '$' && is_array($value)) {
                $preparedQuery[$key] = $this->prepareQueryOrNewObj($value, $isNewObj);
                continue;
            }

            $preparedQueryElements = $this->prepareQueryElement($key, $value, null, true, $isNewObj);
            foreach ($preparedQueryElements as list($preparedKey, $preparedValue)) {
                $preparedQuery[$preparedKey] = is_array($preparedValue)
                    ? array_map('\Doctrine\ODM\MongoDB\Types\Type::convertPHPToDatabaseValue', $preparedValue)
                    : Type::convertPHPToDatabaseValue($preparedValue);
            }
        }

        return $preparedQuery;
    }

    /**
     * Prepares a query value and converts the PHP value to the database value
     * if it is an identifier.
     *
     * It also handles converting $fieldName to the database name if they are different.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param ClassMetadata $class        Defaults to $this->class
     * @param bool $prepareValue Whether or not to prepare the value
     * @param bool $inNewObj Whether or not newObj is being prepared
     * @return array An array of tuples containing prepared field names and values
     */
    private function prepareQueryElement($fieldName, $value = null, $class = null, $prepareValue = true, $inNewObj = false)
    {
        $class = isset($class) ? $class : $this->class;

        // @todo Consider inlining calls to ClassMetadataInfo methods

        // Process all non-identifier fields by translating field names
        if ($class->hasField($fieldName) && ! $class->isIdentifier($fieldName)) {
            $mapping = $class->fieldMappings[$fieldName];
            $fieldName = $mapping['name'];

            if ( ! $prepareValue) {
                return [[$fieldName, $value]];
            }

            // Prepare mapped, embedded objects
            if ( ! empty($mapping['embedded']) && is_object($value) &&
                ! $this->dm->getMetadataFactory()->isTransient(get_class($value))) {
                return [[$fieldName, $this->pb->prepareEmbeddedDocumentValue($mapping, $value)]];
            }

            if (! empty($mapping['reference']) && is_object($value) && ! ($value instanceof \MongoId)) {
                try {
                    return $this->prepareDbRefElement($fieldName, $value, $mapping, $inNewObj);
                } catch (MappingException $e) {
                    // do nothing in case passed object is not mapped document
                }
            }

            // No further preparation unless we're dealing with a simple reference
            // We can't have expressions in empty() with PHP < 5.5, so store it in a variable
            $arrayValue = (array) $value;
            if (empty($mapping['reference']) || $mapping['storeAs'] !== ClassMetadataInfo::REFERENCE_STORE_AS_ID || empty($arrayValue)) {
                return [[$fieldName, $value]];
            }

            // Additional preparation for one or more simple reference values
            $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if ( ! is_array($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if ( ! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $targetClass)]];
        }

        // Process identifier fields
        if (($class->hasField($fieldName) && $class->isIdentifier($fieldName)) || $fieldName === '_id') {
            $fieldName = '_id';

            if ( ! $prepareValue) {
                return [[$fieldName, $value]];
            }

            if ( ! is_array($value)) {
                return [[$fieldName, $class->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if ( ! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $class->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $class)]];
        }

        // No processing for unmapped, non-identifier, non-dotted field names
        if (strpos($fieldName, '.') === false) {
            return [[$fieldName, $value]];
        }

        /* Process "fieldName.objectProperty" queries (on arrays or objects).
         *
         * We can limit parsing here, since at most three segments are
         * significant: "fieldName.objectProperty" with an optional index or key
         * for collections stored as either BSON arrays or objects.
         */
        $e = explode('.', $fieldName, 4);

        // No further processing for unmapped fields
        if ( ! isset($class->fieldMappings[$e[0]])) {
            return [[$fieldName, $value]];
        }

        $mapping = $class->fieldMappings[$e[0]];
        $e[0] = $mapping['name'];

        // Hash and raw fields will not be prepared beyond the field name
        if ($mapping['type'] === Type::HASH || $mapping['type'] === Type::RAW) {
            $fieldName = implode('.', $e);

            return [[$fieldName, $value]];
        }

        if ($mapping['type'] == 'many' && CollectionHelper::isHash($mapping['strategy'])
                && isset($e[2])) {
            $objectProperty = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty = implode('.', array_slice($e, 3));
        } elseif ($e[1] != '$') {
            $fieldName = $e[0] . '.' . $e[1];
            $objectProperty = $e[1];
            $objectPropertyPrefix = '';
            $nextObjectProperty = implode('.', array_slice($e, 2));
        } elseif (isset($e[2])) {
            $fieldName = $e[0] . '.' . $e[1] . '.' . $e[2];
            $objectProperty = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty = implode('.', array_slice($e, 3));
        } else {
            $fieldName = $e[0] . '.' . $e[1];

            return [[$fieldName, $value]];
        }

        // No further processing for fields without a targetDocument mapping
        if ( ! isset($mapping['targetDocument'])) {
            if ($nextObjectProperty) {
                $fieldName .= '.'.$nextObjectProperty;
            }

            return [[$fieldName, $value]];
        }

        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

        // No further processing for unmapped targetDocument fields
        if ( ! $targetClass->hasField($objectProperty)) {
            if ($nextObjectProperty) {
                $fieldName .= '.'.$nextObjectProperty;
            }

            return [[$fieldName, $value]];
        }

        $targetMapping = $targetClass->getFieldMapping($objectProperty);
        $objectPropertyIsId = $targetClass->isIdentifier($objectProperty);

        // Prepare DBRef identifiers or the mapped field's property path
        $fieldName = ($objectPropertyIsId && ! empty($mapping['reference']) && $mapping['storeAs'] !== ClassMetadataInfo::REFERENCE_STORE_AS_ID)
            ? $e[0] . '.$id'
            : $e[0] . '.' . $objectPropertyPrefix . $targetMapping['name'];

        // Process targetDocument identifier fields
        if ($objectPropertyIsId) {
            if ( ! $prepareValue) {
                return [[$fieldName, $value]];
            }

            if ( ! is_array($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if ( ! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $targetClass)]];
        }

        /* The property path may include a third field segment, excluding the
         * collection item pointer. If present, this next object property must
         * be processed recursively.
         */
        if ($nextObjectProperty) {
            // Respect the targetDocument's class metadata when recursing
            $nextTargetClass = isset($targetMapping['targetDocument'])
                ? $this->dm->getClassMetadata($targetMapping['targetDocument'])
                : null;

            $fieldNames = $this->prepareQueryElement($nextObjectProperty, $value, $nextTargetClass, $prepareValue);

            return array_map(function ($preparedTuple) use ($fieldName) {
                list($key, $value) = $preparedTuple;

                return [$fieldName . '.' . $key, $value];
            }, $fieldNames);
        }

        return [[$fieldName, $value]];
    }

    /**
     * Prepares a query expression.
     *
     * @param array|object  $expression
     * @param ClassMetadata $class
     * @return array
     */
    private function prepareQueryExpression($expression, $class)
    {
        foreach ($expression as $k => $v) {
            // Ignore query operators whose arguments need no type conversion
            if (in_array($k, array('$exists', '$type', '$mod', '$size'))) {
                continue;
            }

            // Process query operators whose argument arrays need type conversion
            if (in_array($k, array('$in', '$nin', '$all')) && is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    $expression[$k][$k2] = $class->getDatabaseIdentifierValue($v2);
                }
                continue;
            }

            // Recursively process expressions within a $not operator
            if ($k === '$not' && is_array($v)) {
                $expression[$k] = $this->prepareQueryExpression($v, $class);
                continue;
            }

            $expression[$k] = $class->getDatabaseIdentifierValue($v);
        }

        return $expression;
    }

    /**
     * Checks whether the value has DBRef fields.
     *
     * This method doesn't check if the the value is a complete DBRef object,
     * although it should return true for a DBRef. Rather, we're checking that
     * the value has one or more fields for a DBref. In practice, this could be
     * $elemMatch criteria for matching a DBRef.
     *
     * @param mixed $value
     * @return boolean
     */
    private function hasDBRefFields($value)
    {
        if ( ! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $_) {
            if ($key === '$ref' || $key === '$id' || $key === '$db') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the value has query operators.
     *
     * @param mixed $value
     * @return boolean
     */
    private function hasQueryOperators($value)
    {
        if ( ! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $_) {
            if (isset($key[0]) && $key[0] === '$') {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the array of discriminator values for the given ClassMetadata
     *
     * @param ClassMetadata $metadata
     * @return array
     */
    private function getClassDiscriminatorValues(ClassMetadata $metadata)
    {
        $discriminatorValues = array($metadata->discriminatorValue);
        foreach ($metadata->subClasses as $className) {
            if ($key = array_search($className, $metadata->discriminatorMap)) {
                $discriminatorValues[] = $key;
            }
        }

        // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
        if ($metadata->defaultDiscriminatorValue && array_search($metadata->defaultDiscriminatorValue, $discriminatorValues) !== false) {
            $discriminatorValues[] = null;
        }

        return $discriminatorValues;
    }

    private function handleCollections($document, $options)
    {
        // Collection deletions (deletions of complete collections)
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            if ($this->uow->isCollectionScheduledForDeletion($coll)) {
                $this->cp->delete($coll, $options);
            }
        }
        // Collection updates (deleteRows, updateRows, insertRows)
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            if ($this->uow->isCollectionScheduledForUpdate($coll)) {
                $this->cp->update($coll, $options);
            }
        }
        // Take new snapshots from visited collections
        foreach ($this->uow->getVisitedCollections($document) as $coll) {
            $coll->takeSnapshot();
        }
    }

    /**
     * If the document is new, ignore shard key field value, otherwise throw an exception.
     * Also, shard key field should be present in actual document data.
     *
     * @param object $document
     * @param string $shardKeyField
     * @param array  $actualDocumentData
     *
     * @throws MongoDBException
     */
    private function guardMissingShardKey($document, $shardKeyField, $actualDocumentData)
    {
        $dcs = $this->uow->getDocumentChangeSet($document);
        $isUpdate = $this->uow->isScheduledForUpdate($document);

        $fieldMapping = $this->class->getFieldMappingByDbFieldName($shardKeyField);
        $fieldName = $fieldMapping['fieldName'];

        if ($isUpdate && isset($dcs[$fieldName]) && $dcs[$fieldName][0] != $dcs[$fieldName][1]) {
            throw MongoDBException::shardKeyFieldCannotBeChanged($shardKeyField, $this->class->getName());
        }

        if (!isset($actualDocumentData[$fieldName])) {
            throw MongoDBException::shardKeyFieldMissing($shardKeyField, $this->class->getName());
        }
    }

    /**
     * Get shard key aware query for single document.
     *
     * @param object $document
     *
     * @return array
     */
    private function getQueryForDocument($document)
    {
        $id = $this->uow->getDocumentIdentifier($document);
        $id = $this->class->getDatabaseIdentifierValue($id);

        $shardKeyQueryPart = $this->getShardKeyQuery($document);
        $query = array_merge(array('_id' => $id), $shardKeyQueryPart);

        return $query;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function getWriteOptions(array $options = array())
    {
        $defaultOptions = $this->dm->getConfiguration()->getDefaultCommitOptions();
        $documentOptions = [];
        if ($this->class->hasWriteConcern()) {
            $documentOptions['w'] = $this->class->getWriteConcern();
        }

        return array_merge($defaultOptions, $documentOptions, $options);
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     * @param array $mapping
     * @param bool $inNewObj
     * @return array
     */
    private function prepareDbRefElement($fieldName, $value, array $mapping, $inNewObj)
    {
        $dbRef = $this->dm->createDBRef($value, $mapping);
        if ($inNewObj) {
            return [[$fieldName, $dbRef]];
        }
        $keys = ['$ref' => true, '$id' => true, '$db' => true];
        if ($mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
            unset($keys['$db']);
        }
        if (isset($mapping['targetDocument'])) {
            unset($keys['$ref'], $keys['$db']);
        }

        if ($mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
            return [[$fieldName, $dbRef]];
        } elseif ($mapping['type'] === 'many') {
            return [[$fieldName, ['$elemMatch' => array_intersect_key($dbRef, $keys)]]];
        } else {
            return array_map(
                function ($key) use ($dbRef, $fieldName) {
                    return [$fieldName . '.' . $key, $dbRef[$key]];
                },
                array_keys($keys)
            );
        }
    }
}
