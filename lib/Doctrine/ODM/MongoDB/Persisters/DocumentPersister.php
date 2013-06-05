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
use Doctrine\MongoDB\Cursor as BaseCursor;
use Doctrine\MongoDB\Iterator;
use Doctrine\MongoDB\LoggableCursor as BaseLoggableCursor;
use Doctrine\ODM\MongoDB\LoggableCursor;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnUpdatePreparedArgs;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * The DocumentPersister is responsible for persisting documents.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Bulat Shakirzyanov <bulat@theopenskyproject.com>
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
     * The Hydrator instance
     *
     * @var HydratorInterface
     */
    private $hydrator;

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
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * Initializes a new DocumentPersister instance.
     *
     * @param PersistenceBuilder $pb
     * @param DocumentManager $dm
     * @param EventManager $evm
     * @param UnitOfWork $uow
     * @param HydratorFactory $hydratorFactory
     * @param ClassMetadata $class
     * @param string $cmd
     */
    public function __construct(PersistenceBuilder $pb, DocumentManager $dm, EventManager $evm, UnitOfWork $uow, HydratorFactory $hydratorFactory, ClassMetadata $class, $cmd)
    {
        $this->pb = $pb;
        $this->dm = $dm;
        $this->evm = $evm;
        $this->cmd = $cmd;
        $this->uow = $uow;
        $this->hydratorFactory = $hydratorFactory;
        $this->class = $class;
        $this->collection = $dm->getDocumentCollection($class->name);
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
        return isset($this->queuedInserts[spl_object_hash($document)]) ? true : false;
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
     * Queued documents without an ID will inserted in a batch and included in
     * the resulting array in a tuple alongside their generated ID. Queued
     * documents with an ID will be upserted individually and omitted from the
     * returned array.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @param array $options Options for batchInsert() and update() driver methods
     * @return array An array of identifier/document tuples for any documents
     *               whose identifier was generated during the batch insertions
     */
    public function executeInserts(array $options = array())
    {
        if ( ! $this->queuedInserts) {
            return array();
        }

        $postInsertIds = array();
        $inserts = array();
        $upserts = array();
        foreach ($this->queuedInserts as $oid => $document) {
            $upsert = $this->uow->isScheduledForUpsert($document);
            if ($upsert) {
                $data = $this->pb->prepareUpsertData($document);
            } else {
                $data = $this->pb->prepareInsertData($document);
            }

            // Set the initial version for each insert
            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                if ($versionMapping['type'] === 'int') {
                    $currentVersion = $this->class->reflFields[$this->class->versionField]->getValue($document);
                    $data[$versionMapping['name']] = $currentVersion;
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $currentVersion);
                } elseif ($versionMapping['type'] === 'date') {
                    $nextVersion = new \DateTime();
                    $data[$versionMapping['name']] = new \MongoDate($nextVersion->getTimestamp());
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
                }
            }

            if ($upsert) {
                $upserts[$oid] = $data;
            } else {
                $inserts[$oid] = $data;
            }
        }
        if ($inserts) {
            try {
                $this->collection->batchInsert($inserts, $options);
            } catch (\MongoException $e) {
                $this->queuedInserts = array();
                throw $e;
            }

            foreach ($inserts as $oid => $data) {
                $document = $this->queuedInserts[$oid];
                $postInsertIds[] = array($this->class->getPHPIdentifierValue($data['_id']), $document);
            }
        }

        $this->queuedInserts = array();

        if ($upserts) {
            $upsertOptions = $options;
            $upsertOptions['upsert'] = true;
            foreach ($upserts as $oid => $data) {
                $criteria = array('_id' => $data[$this->cmd . 'set']['_id']);
                unset($data[$this->cmd . 'set']['_id']);
                // stupid php
                if (empty($data[$this->cmd . 'set'])) {
                    $data[$this->cmd . 'set'] = new \stdClass;
                }
                $this->collection->update($criteria, $data, $upsertOptions);
            }
        }

        return $postInsertIds;
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
        $id = $this->uow->getDocumentIdentifier($document);
        $update = $this->pb->prepareUpdateData($document);

        if ( ! empty($update)) {

            $id = $this->class->getDatabaseIdentifierValue($id);
            $query = array('_id' => $id);

            // Include versioning logic to set the new version value in the database
            // and to ensure the version has not changed since this document object instance
            // was fetched from the database
            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                $currentVersion = $this->class->reflFields[$this->class->versionField]->getValue($document);
                if ($versionMapping['type'] === 'int') {
                    $nextVersion = $currentVersion + 1;
                    $update[$this->cmd . 'inc'][$versionMapping['name']] = 1;
                    $query[$versionMapping['name']] = $currentVersion;
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
                } elseif ($versionMapping['type'] === 'date') {
                    $nextVersion = new \DateTime();
                    $update[$this->cmd . 'set'][$versionMapping['name']] = new \MongoDate($nextVersion->getTimestamp());
                    $query[$versionMapping['name']] = new \MongoDate($currentVersion->getTimestamp());
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
                }
                $options['safe'] = true;
            }

            // Include locking logic so that if the document object in memory is currently
            // locked then it will remove it, otherwise it ensures the document is not locked.
            if ($this->class->isLockable) {
                $isLocked = $this->class->reflFields[$this->class->lockField]->getValue($document);
                $lockMapping = $this->class->fieldMappings[$this->class->lockField];
                if ($isLocked) {
                    $update[$this->cmd . 'unset'] = array($lockMapping['name'] => true);
                } else {
                    $query[$lockMapping['name']] = array($this->cmd . 'exists' => false);
                }
            }

            unset($update[$this->cmd . 'set']['_id']);
            $result = $this->collection->update($query, $update, $options);

            if (($this->class->isVersioned || $this->class->isLockable) && ! $result['n']) {
                throw LockException::lockFailed($document);
            }
        }
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
        $id = $this->uow->getDocumentIdentifier($document);
        $query = array('_id' => $this->class->getDatabaseIdentifierValue($id));

        if ($this->class->isLockable) {
            $query[$this->class->lockField] = array($this->cmd . 'exists' => false);
            $options['safe'] = true;
        }

        $result = $this->collection->remove($query, $options);

        if (($this->class->isVersioned || $this->class->isLockable) && ! $result['n']) {
            throw LockException::lockFailed($document);
        }
    }

    /**
     * Refreshes a managed document.
     *
     * @param array $id The identifier of the document.
     * @param object $document The document to refresh.
     */
    public function refresh($id, $document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $data = $this->collection->findOne(array('_id' => $id));
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
        if (is_scalar($criteria) || $criteria instanceof \MongoId) {
            $criteria = array('_id' => $criteria);
        }

        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);

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
        $criteria = $this->addFilterToPreparedQuery($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);

        $baseCursor = $this->collection->find($criteria);
        $cursor = $this->wrapCursor($baseCursor);

        /* The wrapped cursor may be used if the ODM cursor becomes wrapped with
         * an EagerCursor, so we should apply the same sort, limit, and skip
         * options to both cursors.
         */
        if (null !== $sort) {
            $baseCursor->sort($this->prepareSortOrProjection($sort));
            $cursor->sort($sort);
        }

        if (null !== $limit) {
            $baseCursor->limit($limit);
            $cursor->limit($limit);
        }

        if (null !== $skip) {
            $baseCursor->skip($skip);
            $cursor->skip($skip);
        }

        return $cursor;
    }

    /**
     * Wraps the supplied base cursor in the corresponding ODM class.
     *
     * @param BaseCursor $cursor
     * @return Cursor
     */
    private function wrapCursor(BaseCursor $baseCursor)
    {
        if ($baseCursor instanceof BaseLoggableCursor) {
            return new LoggableCursor(
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
        }

        return new Cursor(
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

    /**
     * Checks whether the given managed document exists in the database.
     *
     * @param object $document
     * @return boolean TRUE if the document exists in the database, FALSE otherwise.
     */
    public function exists($document)
    {
        $id = $this->class->getIdentifierObject($document);
        return (boolean) $this->collection->findOne(array(array('_id' => $id)), array('_id'));
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
        $this->collection->update($criteria, array($this->cmd . 'set' => array($lockMapping['name'] => $lockMode)));
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
        $this->collection->update($criteria, array($this->cmd . 'unset' => array($lockMapping['name'] => true)));
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

        return $this->uow->getOrCreateDocument($this->class->name, $result, $hints);
    }

    /**
     * Prime a collection of documents property with an efficient single query instead of
     * lazily loading each field with a single query.
     *
     * @param Iterator $collection
     * @param string $fieldName
     * @param \Closure|boolean $primer
     * @param array $hints
     */
    public function primeCollection(Iterator $collection, $fieldName, $primer, array $hints = array())
    {
        $collection = $collection->toArray();
        if ( ! count($collection)) {
            return;
        }
        $collectionMetaData = $this->dm->getClassMetaData(get_class(current($collection)));

        $fieldMapping = $collectionMetaData->fieldMappings[$fieldName];

        $cmd = $this->cmd;
        $groupedIds = array();

        foreach ($collection as $element) {
            if ($fieldMapping['type'] == 'many') {
                $fieldValue = $collectionMetaData->getFieldValue($element, $fieldName);
                if ($fieldValue instanceof PersistentCollection) {
                    foreach ($fieldValue->getMongoData() as $key => $reference) {
                        if (isset($fieldMapping['simple']) && $fieldMapping['simple']) {
                            $className = $fieldMapping['targetDocument'];
                            $mongoId = $reference;
                        } else {
                            $className = $this->dm->getClassNameFromDiscriminatorValue($fieldMapping, $reference);
                            $mongoId = $reference[$cmd . 'id'];
                        }
                        $id = $this->dm->getClassMetadata($className)->getPHPIdentifierValue($mongoId);
                        $document = $this->uow->tryGetById($id, $className);
                        if ( ! $document || $document instanceof Proxy && ! $document->__isInitialized__) {
                            if ( ! isset($groupedIds[$className])) {
                                $groupedIds[$className] = array();
                            }
                            $groupedIds[$className][$id] = $mongoId;
                        }
                    }
                }
            } elseif ($fieldMapping['type'] == 'one') {
                $document = $collectionMetaData->getFieldValue($element, $fieldName);
                if ($document && $document instanceof Proxy && ! $document->__isInitialized__) {
                    $class = $this->dm->getClassMetadata(get_class($document));
                    $id = $this->uow->getDocumentIdentifier($document);
                    $groupedIds[$class->name][$id] = $id;
                }
            }
        }
        foreach ($groupedIds as $className => $ids) {
            $class = $this->dm->getClassMetadata($className);
            if ($primer instanceof \Closure) {
                $primer($this->dm, $className, $fieldName, $ids, $hints);
            } else {
                $repository = $this->dm->getRepository($className);
                $qb = $repository->createQueryBuilder()
                    ->field($class->identifier)->in($ids);
                if (isset($hints[Query::HINT_SLAVE_OKAY])) {
                    $qb->slaveOkay(true);
                }
                $query = $qb->getQuery();
                $query->execute()->toArray();
            }
        }
    }

    /**
     * Loads a PersistentCollection data. Used in the initialize() method.
     *
     * @param PersistentCollection $collection
     */
    public function loadCollection(PersistentCollection $collection)
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

    private function loadEmbedManyCollection(PersistentCollection $collection)
    {
        $embeddedDocuments = $collection->getMongoData();
        $mapping = $collection->getMapping();
        $owner = $collection->getOwner();
        if ($embeddedDocuments) {
            foreach ($embeddedDocuments as $key => $embeddedDocument) {
                $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $embeddedDocument);
                $embeddedMetadata = $this->dm->getClassMetadata($className);
                $embeddedDocumentObject = $embeddedMetadata->newInstance();

                $data = $this->hydratorFactory->hydrate($embeddedDocumentObject, $embeddedDocument);
                $this->uow->registerManaged($embeddedDocumentObject, null, $data);
                $this->uow->setParentAssociation($embeddedDocumentObject, $mapping, $owner, $mapping['name'] . '.' . $key);
                if ($mapping['strategy'] === 'set') {
                    $collection->set($key, $embeddedDocumentObject);
                } else {
                    $collection->add($embeddedDocumentObject);
                }
            }
        }
    }

    private function loadReferenceManyCollectionOwningSide(PersistentCollection $collection)
    {
        $hints = $collection->getHints();
        $mapping = $collection->getMapping();
        $cmd = $this->cmd;
        $groupedIds = array();

        $sorted = isset($mapping['sort']) && $mapping['sort'];

        foreach ($collection->getMongoData() as $key => $reference) {
            if (isset($mapping['simple']) && $mapping['simple']) {
                $className = $mapping['targetDocument'];
                $mongoId = $reference;
            } else {
                $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $reference);
                $mongoId = $reference[$cmd . 'id'];
            }
            $id = $this->dm->getClassMetadata($className)->getPHPIdentifierValue($mongoId);
            if ( ! $id) {
                continue;
            }

            // create a reference to the class and id
            $reference = $this->dm->getReference($className, $id);

            // no custom sort so add the references right now in the order they are embedded
            if ( ! $sorted) {
                if ($mapping['strategy'] === 'set') {
                    $collection->set($key, $reference);
                } else {
                    $collection->add($reference);
                }
            }

            // only query for the referenced object if it is not already initialized or the collection is sorted
            if (($reference instanceof Proxy && ! $reference->__isInitialized__) || $sorted) {
                $groupedIds[$className][$id] = $mongoId;
            }
        }
        foreach ($groupedIds as $className => $ids) {
            $class = $this->dm->getClassMetadata($className);
            $mongoCollection = $this->dm->getDocumentCollection($className);
            $criteria = array_merge(
                array('_id' => array($cmd . 'in' => $ids)),
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
            if (isset($hints[Query::HINT_SLAVE_OKAY])) {
                $cursor->slaveOkay(true);
            }
            $documents = $cursor->toArray();
            foreach ($documents as $documentData) {
                $document = $this->uow->getById((string) $documentData['_id'], $class->rootDocumentName);
                $data = $this->hydratorFactory->hydrate($document, $documentData);
                $this->uow->setOriginalDocumentData($document, $data);
                $document->__isInitialized__ = true;
                if ($sorted) {
                    $collection->add($document);
                }
            }
        }
    }

    private function loadReferenceManyCollectionInverseSide(PersistentCollection $collection)
    {
        $hints = $collection->getHints();
        $mapping = $collection->getMapping();
        $owner = $collection->getOwner();
        $ownerClass = $this->dm->getClassMetadata(get_class($owner));
        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        $mappedByMapping = isset($targetClass->fieldMappings[$mapping['mappedBy']]) ? $targetClass->fieldMappings[$mapping['mappedBy']] : array();
        $mappedByFieldName = isset($mappedByMapping['simple']) && $mappedByMapping['simple'] ? $mapping['mappedBy'] : $mapping['mappedBy'] . '.$id';
        $criteria = array_merge(
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
        if (isset($hints[Query::HINT_SLAVE_OKAY])) {
            $qb->slaveOkay(true);
        }
        $documents = $qb->getQuery()->execute()->toArray();
        foreach ($documents as $key => $document) {
            if ($mapping['strategy'] === 'set') {
                $collection->set($key, $document);
            } else {
                $collection->add($document);
            }
        }
    }

    private function loadReferenceManyWithRepositoryMethod(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $cursor = $this->dm->getRepository($mapping['targetDocument'])->$mapping['repositoryMethod']($collection->getOwner());
        if (isset($mapping['sort']) && $mapping['sort']) {
            $cursor->sort($mapping['sort']);
        }
        if (isset($mapping['limit']) && $mapping['limit']) {
            $cursor->limit($mapping['limit']);
        }
        if (isset($mapping['skip']) && $mapping['skip']) {
            $cursor->skip($mapping['skip']);
        }
        if (isset($hints[Query::HINT_SLAVE_OKAY])) {
            $cursor->slaveOkay(true);
        }
        $documents = $cursor->toArray();
        foreach ($documents as $document) {
            $collection->add($document);
        }
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
        list($fieldName) = $this->prepareQueryElement($fieldName, null, null, false);

        return $fieldName;
    }

    /**
     * Adds discriminator criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions.
     *
     * @param array $preparedQuery
     * @return array
     */
    public function addDiscriminatorToPreparedQuery(array $preparedQuery)
    {
        /* If the class has a discriminator field, which is not already in the
         * criteria, inject it now. The field/values need no preparation.
         */
        if ($this->class->hasDiscriminator() && ! isset($preparedQuery[$this->class->discriminatorField['name']])) {
            $discriminatorValues = $this->getClassDiscriminatorValues($this->class);
            $preparedQuery[$this->class->discriminatorField['name']] = array('$in' => $discriminatorValues);
        }

        return $preparedQuery;
    }

    /**
     * Adds filter criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions.
     *
     * @param array $preparedQuery
     * @return array
     */
    public function addFilterToPreparedQuery(array $preparedQuery)
    {
        /* If filter criteria exists for this class, prepare it and merge the
         * existing query over it. This makes it possible to override filter
         * criteria.
         *
         * @todo Consider recursive merging in case the filter criteria and
         * prepared query both contain top-level $and/$or operators.
         */
        if ($filterCriteria = $this->dm->getFilterCollection()->getFilterCriteria($this->class)) {
            $preparedQuery = array_merge($this->prepareQueryOrNewObj($filterCriteria), $preparedQuery);
        }

        return $preparedQuery;
    }

    /**
     * Prepares the query criteria or new document object.
     *
     * PHP field names and types will be converted to those used by MongoDB.
     *
     * @param array $query
     * @return array
     */
    public function prepareQueryOrNewObj(array $query)
    {
        $preparedQuery = array();

        foreach ($query as $key => $value) {
            if (isset($key[0]) && $key[0] === $this->cmd && is_array($value)) {
                $preparedQuery[$key] = $this->prepareQueryOrNewObj($value);
            } else {
                list($key, $value) = $this->prepareQueryElement($key, $value, null, true);
                if (is_array($value)) {
                    $preparedQuery[$key] = $this->prepareQueryOrNewObj($value);
                } else {
                    $preparedQuery[$key] = Type::convertPHPToDatabaseValue($value);
                }
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
     * @param boolean $prepareValue Whether or not to prepare the value
     * @return array        Prepared field name and value
     */
    private function prepareQueryElement($fieldName, $value = null, $class = null, $prepareValue = true)
    {
        $class = isset($class) ? $class : $this->class;

        // @todo Consider inlining calls to ClassMetadataInfo methods

        // Process all non-identifier fields by translating field names
        if ($class->hasField($fieldName) && ! $class->isIdentifier($fieldName)) {
            $mapping = $class->fieldMappings[$fieldName];
            $fieldName = $mapping['name'];

            if ( ! $prepareValue || empty($mapping['reference']) || empty($mapping['simple'])) {
                return array($fieldName, $value);
            }

            // Additional preparation for one or more simple reference values
            if ( ! is_array($value)) {
                $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

                return array($fieldName, $targetClass->getDatabaseIdentifierValue($value));
            }

            return array($fieldName, $this->prepareQueryOrNewObj($value));
        }

        // Process identifier fields
        if (($class->hasField($fieldName) && $class->isIdentifier($fieldName)) || $fieldName === '_id') {
            $fieldName = '_id';

            if ( ! $prepareValue) {
                return array($fieldName, $value);
            }

            if ( ! is_array($value)) {
                return array($fieldName, $class->getDatabaseIdentifierValue($value));
            }

            foreach ($value as $k => $v) {
                // Process query operators (e.g. "$in")
                if (isset($k[0]) && $k[0] === $this->cmd && is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $value[$k][$k2] = $class->getDatabaseIdentifierValue($v2);
                    }
                } else {
                    $value[$k] = $class->getDatabaseIdentifierValue($v);
                }
            }

            return array($fieldName, $value);
        }

        // No processing for unmapped, non-identifier, non-dotted field names
        if (strpos($fieldName, '.') === false) {
            return array($fieldName, $value);
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
            return array($fieldName, $value);
        }

        $mapping = $class->fieldMappings[$e[0]];
        $e[0] = $mapping['name'];

        // Hash fields will not be prepared beyond the field name
        if ($mapping['type'] === 'hash') {
            $fieldName = implode('.', $e);

            return array($fieldName, $value);
        }

        if ($mapping['strategy'] === 'set' && isset($e[2])) {
            $objectProperty = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty = implode('.', array_slice($e, 3));
        } elseif ($e[1] != '$') {
            $fieldName = $e[0] . '.' . $e[1];
            $objectProperty = $e[1];
            $objectPropertyPrefix = '';
            $nextObjectProperty = implode('.', array_slice($e, 2));
        } else {
            $fieldName = $e[0] . '.' . $e[1] . '.' . $e[2];
            $objectProperty = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty = implode('.', array_slice($e, 3));
        }

        // No further processing for fields without a targetDocument mapping
        if ( ! isset($mapping['targetDocument'])) {
            return array($fieldName, $value);
        }

        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

        // No further processing for unmapped targetDocument fields
        if ( ! $targetClass->hasField($objectProperty)) {
            return array($fieldName, $value);
        }

        $targetMapping = $targetClass->getFieldMapping($objectProperty);
        $objectPropertyIsId = $targetClass->isIdentifier($objectProperty);

        // Prepare DBRef identifiers or the mapped field's property path
        $fieldName = ($objectPropertyIsId && ! empty($mapping['reference']) && empty($mapping['simple']))
            ? $e[0] . '.$id'
            : $e[0] . '.' . $objectPropertyPrefix . $targetMapping['name'];

        // Process targetDocument identifier fields
        if ($objectPropertyIsId) {
            if ( ! $prepareValue) {
                return array($fieldName, $value);
            }

            if ( ! is_array($value)) {
                return array($fieldName, $targetClass->getDatabaseIdentifierValue($value));
            }

            foreach ($value as $k => $v) {
                // Process query operators (e.g. "$in")
                if (isset($k[0]) && $k[0] === $this->cmd && is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $value[$k][$k2] = $class->getDatabaseIdentifierValue($v2);
                    }
                } else {
                    $value[$k] = $class->getDatabaseIdentifierValue($v);
                }
            }

            return array($fieldName, $value);
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

            list($key, $value) = $this->prepareQueryElement($nextObjectProperty, $value, $nextTargetClass, $prepareValue);

            $fieldName .= '.' . $key;
        }

        return array($fieldName, $value);
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
        return $discriminatorValues;
    }
}
