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
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Events,
    Doctrine\ODM\MongoDB\Event\OnUpdatePreparedArgs,
    Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\ODM\MongoDB\LockException,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Query\Builder,
    Doctrine\MongoDB\ArrayIterator,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Doctrine\ODM\MongoDB\LockMode,
    Doctrine\ODM\MongoDB\Cursor;

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
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

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
     * The string document name being persisted.
     *
     * @var string
     */
    private $documentName;

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
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     */
    public function __construct(PersistenceBuilder $pb, DocumentManager $dm, ClassMetadata $class)
    {
        $this->dp = $pb;
        $this->dm = $dm;
        $this->uow = $dm->getUnitOfWork();
        $this->class = $class;
        $this->documentName = $class->getName();
        $this->collection = $dm->getDocumentCollection($class->name);
        $this->cmd = $this->dm->getConfiguration()->getMongoCmd();
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
            $data = $this->dp->prepareInsertData($document);
            if ( ! $data) {
                continue;
            }

            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                if ($versionMapping['type'] === 'int') {
                    $currentVersion = $this->class->getFieldValue($document, $this->class->versionField);
                    $data[$versionMapping['name']] = $currentVersion;
                    $this->class->setFieldValue($document, $this->class->versionField, $currentVersion);
                } elseif ($versionMapping['type'] === 'date') {
                    $nextVersion = new \DateTime();
                    $data[$versionMapping['name']] = new \MongoDate($nextVersion->getTimestamp());
                    $this->class->setFieldValue($document, $this->class->versionField, $nextVersion);
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
            $postInsertIds[] = array($data['_id'], $document);
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
        $update = $this->dp->prepareUpdateData($document);

        if ( ! empty($update)) {

            $id = $this->class->getDatabaseIdentifierValue($id);
            $query = array('_id' => $id);

            // Include versioning updates
            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                $currentVersion = $this->class->getFieldValue($document, $this->class->versionField);
                if ($versionMapping['type'] === 'int') {
                    $nextVersion = $currentVersion + 1;
                    $update[$this->cmd . 'inc'][$versionMapping['name']] = 1;
                    $query[$versionMapping['name']] = $currentVersion;
                    $this->class->setFieldValue($document, $this->class->versionField, $nextVersion);
                } elseif ($versionMapping['type'] === 'date') {
                    $nextVersion = new \DateTime();
                    $update[$this->cmd . 'set'][$versionMapping['name']] = new \MongoDate($nextVersion->getTimestamp());
                    $query[$versionMapping['name']] = new \MongoDate($currentVersion->getTimestamp());
                    $this->class->setFieldValue($document, $this->class->versionField, $nextVersion);
                }
                $options['safe'] = true;
            }

            if ($this->class->isLockable) {
                $isLocked = $this->class->getFieldValue($document, $this->class->lockField);
                $lockMapping = $this->class->fieldMappings[$this->class->lockField];
                if ($isLocked) {
                    $update[$this->cmd . 'unset'] = array($lockMapping['name'] => true);
                } else {
                    $query[$lockMapping['name']] = array($this->cmd . 'exists' => false);
                }
            }

            if ($this->dm->getEventManager()->hasListeners(Events::onUpdatePrepared)) {
                $this->dm->getEventManager()->dispatchEvent(
                    Events::onUpdatePrepared, new OnUpdatePreparedArgs($this->dm, $document, $update)
                );
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
        $data = $this->collection->findOne(array('_id' => $id));
        $this->dm->getHydrator()->hydrate($document, $data);
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
        $mongoCursor = $cursor->getMongoCursor();
        return new Cursor($mongoCursor, $this->uow, $this->class);
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
        $criteria = array('_id' => $this->uow->getDocumentIdentifier($document));
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
        $criteria = array('_id' => $this->uow->getDocumentIdentifier($document));
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
            $hints[Builder::HINT_REFRESH] = true;
            $id = $result['_id'];
            $this->uow->registerManaged($document, $id, $result);
        }

        return $this->uow->getOrCreateDocument($this->class->name, $result, $hints);
    }

    public function loadCollection(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $cmd = $this->dm->getConfiguration()->getMongoCmd();
        $groupedIds = array();
        foreach ($collection->getReferences() as $reference) {
            $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $reference);
            $id = $reference[$cmd . 'id'];
            $reference = $this->dm->getReference($className, (string) $id);
            $collection->add($reference);
            if ($reference instanceof Proxy && ! $reference->__isInitialized__) {
                if ( ! isset($groupedIds[$className])) {
                    $groupedIds[$className] = array();
                }
                $groupedIds[$className][] = $id;
            }
        }

        foreach ($groupedIds as $className => $ids) {
            $mongoCollection = $this->dm->getDocumentCollection($className);
            $data = $mongoCollection->find(array('_id' => array($cmd . 'in' => $ids)));
            $hints = array(Builder::HINT_REFRESH => true);
            foreach ($data as $id => $documentData) {
                $document = $this->uow->getOrCreateDocument($className, $documentData, $hints);
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