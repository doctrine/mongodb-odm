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

        if ($this->class->isVersioned) {
            $query['locked'] = array($this->cmd . 'exists' => false);
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
     * @return object The loaded and managed document instance or NULL if the document can not be found.
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load($criteria, $document = null, array $hints = array(), $lockMode = 0)
    {
        $criteria = $this->prepareQuery($criteria);
        $result = $this->collection->findOne($criteria);

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
    public function loadAll(array $criteria = array())
    {
        $criteria = $this->prepareQuery($criteria);
        $cursor = $this->collection->find($criteria);
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
            $id = $result['_id'];
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
                $this->loadReferenceManyCollection($collection);
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
                $collection->add($embeddedDocumentObject);
            }
        }
    }

    private function loadReferenceManyCollection(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $cmd = $this->cmd;
        $groupedIds = array();
        foreach ($collection->getMongoData() as $reference) {
            $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $reference);
            $mongoId = $reference[$cmd . 'id'];
            $id = (string) $mongoId;
            $reference = $this->dm->getReference($className, $id);
            $collection->add($reference);
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
            $data = $mongoCollection->find(array('_id' => array($cmd . 'in' => $ids)));
            foreach ($data as $documentData) {
                $document = $this->uow->getById((string) $documentData['_id'], $className);
                $data = $this->hydratorFactory->hydrate($document, $documentData);
                $this->uow->setOriginalDocumentData($document, $data);
            }
        }
    }

    /**
     * Prepares a query and converts values to the types mongodb expects.
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
            $value = $this->prepareWhereValue($key, $value);
            $newQuery[$key] = $value;
        }
        return $newQuery;
    }

    /**
     * Prepare where values converting document object field names to the document collection
     * field name.
     *
     * @param string $fieldName
     * @param string $value
     * @return string $value
     */
    private function prepareWhereValue(&$fieldName, $value)
    {
        if (strpos($fieldName, '.') !== false) {
            $e = explode('.', $fieldName);

            $mapping = $this->class->getFieldMapping($e[0]);

            if ($this->class->hasField($e[0])) {
                $name = $this->class->fieldMappings[$e[0]]['name'];
                if ($name !== $e[0]) {
                    $e[0] = $name;
                }
            }

            if (isset($mapping['targetDocument'])) {
                $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
                if ($targetClass->hasField($e[1]) && $targetClass->identifier === $e[1]) {
                    $fieldName = $e[0] . '.$id';
                    $value = $targetClass->getDatabaseIdentifierValue($value);
                } elseif ($e[1] === '$id') {
                    $value = $targetClass->getDatabaseIdentifierValue($value);
                }
            }
        } elseif ($this->class->hasField($fieldName) && ! $this->class->isIdentifier($fieldName)) {
            $name = $this->class->fieldMappings[$fieldName]['name'];
            if ($name !== $fieldName) {
                $fieldName = $name;
            }
        } else {
            if ($fieldName === $this->class->identifier || $fieldName === '_id') {
                $fieldName = '_id';
                if (is_array($value)) {
                    if (isset($value[$this->cmd.'in'])) {
                        foreach ($value[$this->cmd.'in'] as $k => $v) {
                            $value[$this->cmd.'in'][$k] = $this->class->getDatabaseIdentifierValue($v);
                        }
                    } else {
                        foreach ($value as $k => $v) {
                            $value[$k] = $this->class->getDatabaseIdentifierValue($v);
                        }
                    }
                } else {
                    $value = $this->class->getDatabaseIdentifierValue($value);
                }
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