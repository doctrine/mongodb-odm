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

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Hydrator\HydratorFactory,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Events,
    Doctrine\ODM\MongoDB\Event\OnUpdatePreparedArgs,
    Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\ODM\MongoDB\LockException,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Query\Query,
    Doctrine\MongoDB\ArrayIterator,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Doctrine\ODM\MongoDB\LockMode,
    Doctrine\ODM\MongoDB\Cursor,
    Doctrine\ODM\MongoDB\LoggableCursor,
    Doctrine\MongoDB\Cursor as BaseCursor,
    Doctrine\MongoDB\LoggableCursor as BaseLoggableCursor;

/**
 * The DocumentPersister is responsible for persisting documents.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class DocumentPersister
{
    /**
     * The PersistenceBuilder instance.
     *
     * @var Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder
     */
    private $pb;

    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The EventManager instance
     *
     * @var Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

    /**
     * The Hydrator instance
     *
     * @var Doctrine\ODM\MongoDB\Hydrator
     */
    private $hydrator;

    /**
     * The ClassMetadata instance for the document type being persisted.
     *
     * @var Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * The MongoCollection instance for this document.
     *
     * @var Doctrine\ODM\MongoDB\MongoCollection
     */
    private $collection;

    /**
     * Array of quered inserts for the persister to insert.
     *
     * @var array
     */
    private $queuedInserts = array();

    /**
     * Documents to be updated, used in executeReferenceUpdates() method
     * @var array
     */
    private $documentsToUpdate = array();

    /**
     * Fields to update, used in executeReferenceUpdates() method
     * @var array
     */
    private $fieldsToUpdate = array();

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * Initializes a new DocumentPersister instance.
     *
     * @param Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder $pb
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\Common\EventManager $evm
     * @param Doctrine\ODM\MongoDB\UnitOfWork $uow
     * @param Doctrine\ODM\MongoDB\Hydrator\HydratorFactory $hydratorFactory
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
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

    public function getInserts()
    {
        return $this->queuedInserts;
    }

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
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Executes all queued document insertions and returns any generated post-insert
     * identifiers that were created as a result of the insertions.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @param array $options Array of options to be used with batchInsert()
     * @return array An array of any generated post-insert IDs. This will be an empty array
     *               if the document class does not use the IDENTITY generation strategy.
     */
    public function executeInserts(array $options = array())
    {
        if ( ! $this->queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $inserts = array();
        foreach ($this->queuedInserts as $oid => $document) {
            $data = $this->pb->prepareInsertData($document);
            if ( ! $data) {
                continue;
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

            $inserts[$oid] = $data;
        }
        if (empty($inserts)) {
            return;
        }
        $this->collection->batchInsert($inserts, $options);

        foreach ($inserts as $oid => $data) {
            $document = $this->queuedInserts[$oid];
            $postInsertIds[] = array($this->class->getPHPIdentifierValue($data['_id']), $document);
        }
        $this->queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * Updates the already persisted document if it has any new changesets.
     *
     * @param object $document
     * @param array $options Array of options to be used with update()
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

        if (($this->class->isVersioned || $this->class->isLockable)  && ! $result['n']) {
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
     * Loads an document by a list of field criteria.
     *
     * @param array $criteria The criteria by which to load the document.
     * @param object $document The document to load the data into. If not specified,
     *        a new document is created.
     * @param array $hints Hints for document creation.
     * @param int $lockMode
     * @param array $sort
     * @return object The loaded and managed document instance or NULL if the document can not be found.
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load($criteria, $document = null, array $hints = array(), $lockMode = 0, array $sort = array())
    {
        $criteria = $this->prepareQuery($criteria);
        $cursor = $this->collection->find($criteria)->limit(1);
        if ($sort) {
            $cursor->sort($sort);
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
     * Loads a list of documents by a list of field criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria = $this->prepareQuery($criteria);
        $cursor = $this->collection->find($criteria);

        if (null !== $orderBy) {
            $cursor->sort($orderBy);
        }

        if (null !== $limit) {
            $cursor->limit($limit);
        }

        if (null !== $offset) {
            $cursor->skip($offset);
        }

        return $this->wrapCursor($cursor);
    }

    /**
     * Wraps the supplied base cursor as an ODM one.
     *
     * @param Doctrine\MongoDB\Cursor $cursor The base cursor
     *
     * @return Cursor An ODM cursor
     */
    private function wrapCursor(BaseCursor $cursor)
    {
        $mongoCursor = $cursor->getMongoCursor();
        if ($cursor instanceof BaseLoggableCursor) {
            return new LoggableCursor(
                $mongoCursor,
                $this->uow,
                $this->class,
                $cursor->getLoggerCallable(),
                $cursor->getQuery(),
                $cursor->getFields()
            );
        } else {
            return new Cursor($mongoCursor, $this->uow, $this->class);
        }
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
        return (bool) $this->collection->findOne(array(array('_id' => $id)), array('_id'));
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
        $this->collection->update($criteria, array($this->cmd.'set' => array($lockMapping['name'] => $lockMode)));
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
        $this->collection->update($criteria, array($this->cmd.'unset' => array($lockMapping['name'] => true)));
        $this->class->reflFields[$this->class->lockField]->setValue($document, null);
    }

    /**
     * Creates or fills a single document object from an query result.
     *
     * @param $result The query result.
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
                $mapping = $collection->getMapping();
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
                $this->uow->setParentAssociation($embeddedDocumentObject, $mapping, $owner, $mapping['name'].'.'.$key);
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
        $mapping = $collection->getMapping();
        $cmd = $this->cmd;
        $groupedIds = array();

        foreach ($collection->getMongoData() as $key => $reference) {
            if (isset($mapping['simple']) && $mapping['simple']) {
                $className = $mapping['targetDocument'];
                $mongoId = $reference;
            } else {
                $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $reference);
                $mongoId = $reference[$cmd . 'id'];
            }
            $id = (string) $mongoId;
            $reference = $this->dm->getReference($className, $id);
            if ($mapping['strategy'] === 'set') {
                $collection->set($key, $reference);
            } else {
                $collection->add($reference);
            }
            if ($reference instanceof Proxy && ! $reference->__isInitialized__) {
                if ( ! isset($groupedIds[$className])) {
                    $groupedIds[$className] = array();
                }
                $groupedIds[$className][] = $mongoId;
            }
        }
        foreach ($groupedIds as $className => $ids) {
            $class = $this->dm->getClassMetadata($className);
            $mongoCollection = $this->dm->getDocumentCollection($className);
            $criteria = array_merge(
                array('_id' => array($cmd . 'in' => $ids)),
                isset($mapping['criteria']) ? $mapping['criteria'] : array()
            );
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
            foreach ($cursor as $documentData) {
                $document = $this->uow->getById((string) $documentData['_id'], $class->rootDocumentName);
                $data = $this->hydratorFactory->hydrate($document, $documentData);
                $this->uow->setOriginalDocumentData($document, $data);
                $document->__isInitialized__ = true;
            }
        }
    }

    private function loadReferenceManyCollectionInverseSide(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $owner = $collection->getOwner();
        $ownerClass = $this->dm->getClassMetadata(get_class($owner));
        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        $mappedByMapping = $targetClass->fieldMappings[$mapping['mappedBy']];
        $mappedByFieldName = isset($mappedByMapping['simple']) && $mappedByMapping['simple'] ? $mapping['mappedBy'] : $mapping['mappedBy'].'.id';
        $criteria = array_merge(
            array($mappedByFieldName => $ownerClass->getIdentifierObject($owner)),
            isset($mapping['criteria']) ? $mapping['criteria'] : array()
        );
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
        $query = $qb->getQuery();
        $cursor = $query->execute();
        foreach ($cursor as $document) {
            $collection->add($document);
        }
    }

    private function loadReferenceManyWithRepositoryMethod(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $cursor = $this->dm->getRepository($mapping['targetDocument'])->$mapping['repositoryMethod']($collection->getOwner());
        if ($mapping['sort']) {
            $cursor->sort($mapping['sort']);
        }
        if ($mapping['limit']) {
            $cursor->limit($mapping['limit']);
        }
        if ($mapping['skip']) {
            $cursor->skip($mapping['skip']);
        }
        foreach ($cursor as $document) {
            $collection->add($document);
        }
    }

    /**
     * Prepares a query array by converting the portable Doctrine types to the types mongodb expects.
     *
     * @param string|array $query
     * @return array $query
     */
    public function prepareQuery($query)
    {
        if (is_scalar($query)) {
            $query = array('_id' => $query);
        }
        if ($this->class->hasDiscriminator() && ! isset($query[$this->class->discriminatorField['name']])) {
            $discriminatorValues = $this->getClassDiscriminatorValues($this->class);
            $query[$this->class->discriminatorField['name']] = array('$in' => $discriminatorValues);
        }
        $newQuery = array();
        foreach ($query as $key => $value) {
            $value = $this->prepareQueryValue($key, $value);
            $newQuery[$key] = $value;
        }
        $newQuery = $this->convertTypes($newQuery);
        return $newQuery;
    }

    /**
     * Converts any local PHP variable types to their related MongoDB type.
     *
     * @param array $query
     * @return array $query
     */
    private function convertTypes(array $query)
    {
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                $query[$key] = $this->convertTypes($value);
            } else {
                $query[$key] = Type::convertPHPToDatabaseValue($value);
            }
        }
        return $query;
    }

    /**
     * Prepares a query value and converts the php value to the database value if it is an identifier.
     * It also handles converting $fieldName to the database name if they are different.
     *
     * @param string        $fieldName
     * @param string        $value
     * @param ClassMetadata $metadata   Defaults to $this->class
     * @return mixed        $value
     */
    private function prepareQueryValue(&$fieldName, $value, $metadata = null)
    {
        $metadata = ($metadata === null) ? $this->class : $metadata;
        
        // Process "association.fieldName"
        if (strpos($fieldName, '.') !== false) {
            $e = explode('.', $fieldName);

            $mapping = $metadata->getFieldMapping($e[0]);
            $name = $mapping['name'];
            if ($name !== $e[0]) {
                $e[0] = $name;
            }

            if (isset($mapping['targetDocument'])) {
                $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
                if ($targetClass->hasField($e[1])) {
                    if ($targetClass->identifier === $e[1]) {
                        $fieldName =  $e[0] . '.$id';
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                $value[$k] = $targetClass->getDatabaseIdentifierValue($v);
                            }
                        } else {
                            $value = $targetClass->getDatabaseIdentifierValue($value);
                        }

                    } else {
                        $targetMapping = $targetClass->getFieldMapping($e[1]);
                        $targetName = $targetMapping['name'];
                        if ($targetName !== $e[1]) {
                            $e[1] = $targetName;
                        }
                        $fieldName =  $e[0] . '.' . $e[1];

                        if(count($e) > 2) {
                            unset($e[0], $e[1]);
                            $key = implode('.', $e);

                            if (isset($targetMapping['targetDocument'])) {
                                $nextTargetClass = $this->dm->getClassMetadata($targetMapping['targetDocument']);
                                $value = $this->prepareQueryValue($key, $value, $nextTargetClass);
                            } else {
                                $value = $this->prepareQueryValue($key, $value);
                            }
                            
                            $fieldName .= '.' . $key;
                        }
                    }
                }
            }

        // Process all non identifier fields
        // We only change the field names here to the mongodb field name used for persistence
        } elseif ($metadata->hasField($fieldName) && ! $metadata->isIdentifier($fieldName)) {
            $mapping = $metadata->fieldMappings[$fieldName];
            $name = $mapping['name'];
            if ($name !== $fieldName) {
                $fieldName = $name;
            }

            if (isset($mapping['reference']) && isset($mapping['simple']) && $mapping['simple']) {
                $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
                $value = $targetClass->getDatabaseIdentifierValue($value);
            }

        // Process identifier
        } elseif (($metadata->hasField($fieldName) && $metadata->isIdentifier($fieldName)) || $fieldName === '_id') {
            $fieldName = '_id';
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($k[0] === '$' && is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            $value[$k][$k2] = $metadata->getDatabaseIdentifierValue($v2);
                        }
                    } else {
                        $value[$k] = $metadata->getDatabaseIdentifierValue($v);
                    }
                }
            } else {
                $value = $metadata->getDatabaseIdentifierValue($value);
            }
        }
        return $value;
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
