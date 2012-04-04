<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE <?php
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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\ODM\MongoDB\Event\LifecycleEventArgs,
    Doctrine\ODM\MongoDB\Event\PreLoadEventArgs,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\NotifyPropertyChanged,
    Doctrine\Common\PropertyChangedListener,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\MongoDB\GridFSFile,
    Doctrine\ODM\MongoDB\Query\Query,
    Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * An document is in MANAGED state when its persistence is managed by an DocumentManager.
     */
    const STATE_MANAGED = 1;

    /**
     * An document is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by an DocumentManager.
     */
    const STATE_NEW = 2;

    /**
     * A detached document is an instance with a persistent identity that is not
     * (or no longer) associated with an DocumentManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;

    /**
     * A removed document instance is an instance with a persistent identity,
     * associated with an DocumentManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_REMOVED = 4;

    /**
     * The identity map that holds references to all managed documents that have
     * an identity. The documents are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var array
     */
    private $identityMap = array();

    /**
     * Map of all identifiers of managed documents.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $documentIdentifiers = array();

    /**
     * Map of the original document data of managed documents.
     * Keys are object ids (spl_object_hash). This is used for calculating changesets
     * at commit time.
     *
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *           A value will only really be copied if the value in the document is modified
     *           by the user.
     */
    private $originalDocumentData = array();

    /**
     * Map of document changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $documentChangeSets = array();

    /**
     * The (cached) states of any known documents.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $documentStates = array();

    /**
     * Map of documents that are scheduled for dirty checking at commit time.
     * This is only used for documents with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     * @todo rename: scheduledForSynchronization
     */
    private $scheduledForDirtyCheck = array();

    /**
     * A list of all pending document insertions.
     *
     * @var array
     */
    private $documentInsertions = array();

    /**
     * A list of all pending document updates.
     *
     * @var array
     */
    private $documentUpdates = array();

    /**
     * A list of all pending document upserts.
     *
     * @var array
     */
    private $documentUpserts = array();

    /**
     * Any pending extra updates that have been scheduled by persisters.
     *
     * @var array
     */
    private $extraUpdates = array();

    /**
     * A list of all pending document deletions.
     *
     * @var array
     */
    private $documentDeletions = array();

    /**
     * All pending collection deletions.
     *
     * @var array
     */
    private $collectionDeletions = array();

    /**
     * All pending collection updates.
     *
     * @var array
     */
    private $collectionUpdates = array();

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array
     */
    private $visitedCollections = array();

    /**
     * The DocumentManager that "owns" this UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The calculator used to calculate the order in which changes to
     * documents need to be written to the database.
     *
     * @var Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator
     */
    private $commitOrderCalculator;

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $evm;

    /**
     * Embedded documents that are scheduled for removal.
     *
     * @var array
     */
    private $orphanRemovals = array();

    /**
     * The HydratorFactory used for hydrating array Mongo documents to Doctrine object documents.
     *
     * @var HydratorFactory
     */
    private $hydratorFactory;

    /**
     * The document persister instances used to persist document instances.
     *
     * @var array
     */
    private $persisters = array();

    /**
     * The collection persister instance used to persist changes to collections.
     *
     * @var CollectionPersister
     */
    private $collectionPersister;

    /**
     * The persistence builder instance used in DocumentPersisters.
     *
     * @var PersistenceBuilder
     */
    private $persistenceBuilder;

    /**
     * Array of parent associations between embedded documents
     *
     * @todo We might need to clean up this array in clear(), doDetch(), etc.
     * @var array
     */
    private $parentAssociations = array();

    /**
     * Mongo command character
     *
     * @var string
     */
    private $cmd;

    /**
     * Initializes a new UnitOfWork instance, bound to the given DocumentManager.
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\Common\EventManager $evm
     * @param Doctrine\ODM\MongoDB\Hydrator\HydratorFactory $hydratorFactory
     * @param string $cmd
     */
    public function __construct(DocumentManager $dm, EventManager $evm, HydratorFactory $hydratorFactory, $cmd)
    {
        $this->dm = $dm;
        $this->evm = $evm;
        $this->hydratorFactory = $hydratorFactory;
        $this->cmd = $cmd;
    }

    /**
     * Factory for returning new PersistenceBuilder instances used for preparing data into
     * queries for insert persistence.
     *
     * @return PersistenceBuilder $pb
     */
    public function getPersistenceBuilder()
    {
        if (!$this->persistenceBuilder) {
            $this->persistenceBuilder = new PersistenceBuilder($this->dm, $this, $this->cmd);
        }
        return $this->persistenceBuilder;
    }

    /**
     * Sets the parent association for a given embedded document.
     *
     * @param object $document
     * @param array $mapping
     * @param object $parent
     * @param string $propertyPath
     */
    public function setParentAssociation($document, $mapping, $parent, $propertyPath)
    {
        $oid = spl_object_hash($document);
        $this->parentAssociations[$oid] = array($mapping, $parent, $propertyPath);
    }

    /**
     * Gets the parent association for a given embedded document.
     *
     *     <code>
     *     list($mapping, $parent, $propertyPath) = $this->getParentAssociation($embeddedDocument);
     *     </code>
     *
     * @param object $document
     * @return array $association
     */
    public function getParentAssociation($document)
    {
        $oid = spl_object_hash($document);
        if ( ! isset($this->parentAssociations[$oid])) {
            return null;
        }
        return $this->parentAssociations[$oid];
    }

    /**
     * Get the document persister instance for the given document name
     *
     * @param string $documentName
     * @return Persisters\DocumentPersister
     */
    public function getDocumentPersister($documentName)
    {
        if ( ! isset($this->persisters[$documentName])) {
            $class = $this->dm->getClassMetadata($documentName);
            $pb = $this->getPersistenceBuilder();
            $this->persisters[$documentName] = new Persisters\DocumentPersister($pb, $this->dm, $this->evm, $this, $this->hydratorFactory, $class, $this->cmd);
        }
        return $this->persisters[$documentName];
    }

    /**
     * Gets a collection persister for a collection-valued association.
     *
     * @param array $mapping
     * @return Persisters\CollectionPersister
     */
    public function getCollectionPersister(array $mapping)
    {
        if ( ! isset($this->collectionPersister)) {
            $pb = $this->getPersistenceBuilder();
            $this->collectionPersister = new Persisters\CollectionPersister($this->dm, $pb, $this, $this->cmd);
        }
        return $this->collectionPersister;
    }

    /**
     * Set the document persister instance to use for the given document name
     *
     * @param string $documentName
     * @param Persisters\DocumentPersister $persister
     */
    public function setDocumentPersister($documentName, Persisters\DocumentPersister $persister)
    {
        $this->persisters[$documentName] = $persister;
    }

    /**
     * Commits the UnitOfWork, executing all operations that have been postponed
     * up to this point. The state of all managed documents will be synchronized with
     * the database.
     *
     * The operations are executed in the following order:
     *
     * 1) All document insertions
     * 2) All document updates
     * 3) All document deletions
     *
     * @param object $document
     * @param array $options Array of options to be used with batchInsert(), update() and remove()
     */
    public function commit($document = null, array $options = array())
    {
        // Raise preFlush
        if ($this->evm->hasListeners(Events::preFlush)) {
            $this->evm->dispatchEvent(Events::preFlush, new Event\PreFlushEventArgs($this->dm));
        }

        $defaultOptions = $this->dm->getConfiguration()->getDefaultCommitOptions();
        if ($options) {
            $options = array_merge($defaultOptions, $options);
        } else {
            $options = $defaultOptions;
        }
        // Compute changes done since last commit.
        if ($document === null) {
            $this->computeChangeSets();
        } else if (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } else if (is_array($document)) {
            foreach ($document as $object) {
                $this->computeSingleDocumentChangeSet($object);
            }
        }

        if ( ! ($this->documentInsertions ||
                $this->documentUpserts ||
                $this->documentDeletions ||
                $this->documentUpdates ||
                $this->collectionUpdates ||
                $this->collectionDeletions ||
                $this->orphanRemovals)) {
            return; // Nothing to do.
        }

        if ($this->orphanRemovals) {
            foreach ($this->orphanRemovals as $removal) {
                $this->remove($removal);
            }
        }

        // Raise onFlush
        if ($this->evm->hasListeners(Events::onFlush)) {
            $this->evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->dm));
        }

        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->getCommitOrder();

        if ($this->documentInsertions) {
            foreach ($commitOrder as $class) {
                if ($class->isEmbeddedDocument) {
                    continue;
                }
                $this->executeInserts($class, $options);
            }
        }

        if ($this->documentUpdates) {
            foreach ($commitOrder as $class) {
                $this->executeUpdates($class, $options);
            }
        }

        // Extra updates that were requested by persisters.
        if ($this->extraUpdates) {
            $this->executeExtraUpdates($options);
        }

        // Collection deletions (deletions of complete collections)
        foreach ($this->collectionDeletions as $collectionToDelete) {
            $this->getCollectionPersister($collectionToDelete->getMapping())
                    ->delete($collectionToDelete, $options);
        }
        // Collection updates (deleteRows, updateRows, insertRows)
        foreach ($this->collectionUpdates as $collectionToUpdate) {
            $this->getCollectionPersister($collectionToUpdate->getMapping())
                    ->update($collectionToUpdate, $options);
        }

        // Document deletions come last and need to be in reverse commit order
        if ($this->documentDeletions) {
            for ($count = count($commitOrder), $i = $count - 1; $i >= 0; --$i) {
                $this->executeDeletions($commitOrder[$i], $options);
            }
        }

        // Take new snapshots from visited collections
        foreach ($this->visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        // Raise postFlush
        if ($this->evm->hasListeners(Events::postFlush)) {
            $this->evm->dispatchEvent(Events::postFlush, new Event\PostFlushEventArgs($this->em));
        }

        // Clear up
        $this->documentInsertions =
        $this->documentUpserts =
        $this->documentUpdates =
        $this->documentDeletions =
        $this->extraUpdates =
        $this->documentChangeSets =
        $this->collectionUpdates =
        $this->collectionDeletions =
        $this->visitedCollections =
        $this->scheduledForDirtyCheck =
        $this->orphanRemovals = array();
    }

    /**
     * Compute the changesets of all documents scheduled for insertion
     *
     * @return void
     */
    private function computeScheduleInsertsChangeSets()
    {
        foreach ($this->documentInsertions as $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Only flush the given document according to a ruleset that keeps the UoW consistent.
     *
     * 1. All documents scheduled for insertion, (orphan) removals and changes in collections are processed as well!
     * 2. Proxies are skipped.
     * 3. Only if document is properly managed.
     *
     * @param  object $document
     * @return void
     */
    private function computeSingleDocumentChangeSet($document)
    {
        if ($this->getDocumentState($document) !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException("Document has to be managed for single computation " . self::objToStr($document));
        }

        $class = $this->dm->getClassMetadata(get_class($document));

        if ($class->isChangeTrackingDeferredImplicit()) {
            $this->persist($document);
        }

        // Compute changes for INSERTed documents first. This must always happen even in this case.
        $this->computeScheduleInsertsChangeSets();

        // Ignore uninitialized proxy objects
        if ($document instanceof Proxy && ! $document->__isInitialized__) {
            return;
        }

        // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION are processed here.
        $oid = spl_object_hash($document);

        if ( ! isset($this->documentInsertions[$oid]) && isset($this->documentStates[$oid])) {
            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Executes reference updates
     */
    private function executeExtraUpdates(array $options)
    {
        foreach ($this->extraUpdates as $oid => $update) {
            list ($document, $changeset) = $update;
            $this->documentChangeSets[$oid] = $changeset;
            $this->getDocumentPersister(get_class($document))->update($document, $options);
        }
    }

    /**
     * Gets the changeset for an document.
     *
     * @return array
     */
    public function getDocumentChangeSet($document)
    {
        $oid = spl_object_hash($document);
        if (isset($this->documentChangeSets[$oid])) {
            return $this->documentChangeSets[$oid];
        }
        return array();
    }

    /**
     * Get a documents actual data, flattening all the objects to arrays.
     *
     * @param object $document
     * @return array
     */
    public function getDocumentActualData($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            $mapping = $class->fieldMappings[$name];
            $value = $refProp->getValue($document);
            if (isset($mapping['file']) && ! $value instanceof GridFSFile) {
                $value = new GridFSFile($value);
                $class->reflFields[$name]->setValue($document, $value);
                $actualData[$name] = $value;
            } elseif ((isset($mapping['association']) && $mapping['type'] === 'many')
                    && $value !== null && ! ($value instanceof PersistentCollection)) {
                // If $actualData[$name] is not a Collection then use an ArrayCollection.
                if ( ! $value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // Inject PersistentCollection
                $coll = new PersistentCollection($value, $this->dm, $this, $this->cmd);
                $coll->setOwner($document, $mapping);
                $coll->setDirty( ! $value->isEmpty());
                $class->reflFields[$name]->setValue($document, $coll);
                $actualData[$name] = $coll;
            } else {
                $actualData[$name] = $value;
            }
        }
        return $actualData;
    }

    /**
     * Computes the changes that happened to a single document.
     *
     * Modifies/populates the following properties:
     *
     * {@link originalDocumentData}
     * If the document is NEW or MANAGED but not yet fully persisted (only has an id)
     * then it was not fetched from the database and therefore we have no original
     * document data yet. All of the current document data is stored as the original document data.
     *
     * {@link documentChangeSets}
     * The changes detected on all properties of the document are stored there.
     * A change is a tuple array where the first entry is the old value and the second
     * entry is the new value of the property. Changesets are used by persisters
     * to INSERT/UPDATE the persistent document state.
     *
     * {@link documentUpdates}
     * If the document is already fully MANAGED (has been fetched from the database before)
     * and any changes to its properties are detected, then a reference to the document is stored
     * there to mark it for an update.
     *
     * @param ClassMetadata $class The class descriptor of the document.
     * @param object $document The document for which to compute the changes.
     */
    public function computeChangeSet(ClassMetadata $class, $document)
    {
        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata(get_class($document));
        }

        // Fire PreFlush lifecycle callbacks
        if (isset($class->lifecycleCallbacks[Events::preFlush])) {
            $class->invokeLifecycleCallbacks(Events::preFlush, $document);
        }

        $oid = spl_object_hash($document);
        $actualData = $this->getDocumentActualData($document);
        $isNewDocument = ! isset($this->originalDocumentData[$oid]);
        if ($isNewDocument) {
            // Document is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->originalDocumentData[$oid] = $actualData;
            $changeSet = array();
            foreach ($actualData as $propName => $actualValue) {
                $changeSet[$propName] = array(null, $actualValue);
            }
            $this->documentChangeSets[$oid] = $changeSet;
        } else {
            // Document is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData = $this->originalDocumentData[$oid];
            $isChangeTrackingNotify = $class->isChangeTrackingNotify();
            $changeSet = $isChangeTrackingNotify ? $this->documentChangeSets[$oid] : array();

            foreach ($actualData as $propName => $actualValue) {
                $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;
                if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === 'one' && $orgValue !== $actualValue) {
                    if ($orgValue !== null) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }
                    $changeSet[$propName] = array($orgValue, $actualValue);
                } else if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === 'one' && $class->fieldMappings[$propName]['isOwningSide'] && $orgValue !== $actualValue) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                } else if ($isChangeTrackingNotify) {
                    continue;
                } else if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'many' && $orgValue !== $actualValue) {
                    if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                        continue; // ignore inverse side
                    }
                    $changeSet[$propName] = array($orgValue, $actualValue);
                    if ($orgValue instanceof PersistentCollection) {
                        $this->collectionDeletions[] = $orgValue;
                    }
                } else if (isset($class->fieldMappings[$propName]['file'])) {
                    if ($orgValue !== $actualValue || $actualValue->isDirty()) {
                        $changeSet[$propName] = array($orgValue, $actualValue);
                    }
                } else if ($orgValue instanceof \DateTime || $actualValue instanceof \DateTime) {
                    if ($orgValue != $actualValue) {
                        $changeSet[$propName] = array($orgValue, $actualValue);
                    }
                } else if ($orgValue !== $actualValue) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                }
            }
            if ($changeSet) {
                $this->documentChangeSets[$oid] = $changeSet;
                $this->originalDocumentData[$oid] = $actualData;
                $this->documentUpdates[$oid] = $document;
            }
        }

        // Look for changes in associations of the document
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['reference']) || isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value !== null) {
                    $this->computeAssociationChanges($document, $mapping, $value);
                    if(isset($mapping['reference'])) {
                        continue;
                    }

                    $values = $value;
                    if (isset($mapping['type']) && $mapping['type'] === 'one') {
                        $values = array($values);
                    } elseif ($values instanceof PersistentCollection) {
                        $values = $values->unwrap();
                    }
                    foreach ($values as $obj) {
                        $oid2 = spl_object_hash($obj);
                        if (isset($this->documentChangeSets[$oid2])) {
                            $this->documentChangeSets[$oid][$mapping['fieldName']] = array($value, $value);
                            if (!$isNewDocument) {
                                $this->documentUpdates[$oid] = $document;
                            }
                            break;
                        }
                    }
                 }
            }
        }
    }

    /**
     * Computes all the changes that have been done to documents and collections
     * since the last commit and stores these changes in the _documentChangeSet map
     * temporarily for access by the persisters, until the UoW commit is finished.
     */
    public function computeChangeSets()
    {
        $this->computeScheduleInsertsChangeSets();

        // Compute changes for other MANAGED documents. Change tracking policies take effect here.
        foreach ($this->identityMap as $className => $documents) {
            $class = $this->dm->getClassMetadata($className);
            if($class->isEmbeddedDocument) {
                // Embedded documents should only compute by the document itself which include the embedded document.
                // This is done separately later.
                // @see computeChangeSet()
                // @see computeAssociationChanges()
                continue;
            }

            // If change tracking is explicit or happens through notification, then only compute
            // changes on documents of that type that are explicitly marked for synchronization.
            $documentsToProcess = ! $class->isChangeTrackingDeferredImplicit() ?
                    (isset($this->scheduledForDirtyCheck[$className]) ?
                        $this->scheduledForDirtyCheck[$className] : array())
                    : $documents;

            foreach ($documentsToProcess as $document) {
                // Ignore uninitialized proxy objects
                if (/* $document is readOnly || */ $document instanceof Proxy && ! $document->__isInitialized__) {
                    continue;
                }
                // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION are processed here.
                $oid = spl_object_hash($document);
                if ( ! isset($this->documentInsertions[$oid]) && isset($this->documentStates[$oid])) {
                    $this->computeChangeSet($class, $document);
                }
            }
        }
    }

    /**
     * Computes the changes of an embedded document.
     *
     * @param object $parentDocument
     * @param array $mapping
     * @param mixed $value The value of the association.
     */
    private function computeAssociationChanges($parentDocument, $mapping, $value)
    {
        if ($value instanceof PersistentCollection && $value->isDirty() && $mapping['isOwningSide']) {
            $owner = $value->getOwner();
            $className = get_class($owner);
            $class = $this->dm->getClassMetadata($className);
            if (!in_array($value, $this->collectionUpdates, true)) {
                $this->collectionUpdates[] = $value;
            }
            $this->visitedCollections[] = $value;
        }

        if ( ! isset($mapping['embedded']) && ! $mapping['isCascadePersist']) {
            return; // "Persistence by reachability" only if persist cascade specified
        }

        if ($mapping['type'] === 'one') {
            if ($value instanceof Proxy && ! $value->__isInitialized__) {
                return; // Ignore uninitialized proxy objects
            }
            $value = array($value);
        } elseif ($value instanceof PersistentCollection) {
            $value = $value->unwrap();
        }
        $count = 0;
        foreach ($value as $key => $entry) {
            $targetClass = $this->dm->getClassMetadata(get_class($entry));
            $state = $this->getDocumentState($entry, self::STATE_NEW);
            $oid = spl_object_hash($entry);
            $path = $mapping['type'] === 'many' ? $mapping['name'].'.'.$count : $mapping['name'];
            $count++;
            if ($state == self::STATE_NEW) {
                if ( ! $targetClass->isEmbeddedDocument && ! $mapping['isCascadePersist']) {
                    throw new \InvalidArgumentException("A new document was found through a relationship that was not"
                            . " configured to cascade persist operations: " . self::objToStr($entry) . "."
                            . " Explicitly persist the new document or configure cascading persist operations"
                            . " on the relationship.");
                }
                $this->persistNew($targetClass, $entry);
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } else if ($state == self::STATE_MANAGED && $targetClass->isEmbeddedDocument) {
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } else if ($state == self::STATE_REMOVED) {
                return new \InvalidArgumentException("Removed document detected during flush: "
                        . self::objToStr($removedDocument).". Remove deleted documents from associations.");
            } else if ($state == self::STATE_DETACHED) {
                // Can actually not happen right now as we assume STATE_NEW,
                // so the exception will be raised from the DBAL layer (constraint violation).
                throw new \InvalidArgumentException("A detached document was found through a "
                        . "relationship during cascading a persist operation.");
            }
        }
    }

    /**
     * INTERNAL:
     * Computes the changeset of an individual document, independently of the
     * computeChangeSets() routine that is used at the beginning of a UnitOfWork#commit().
     *
     * The passed document must be a managed document. If the document already has a change set
     * because this method is invoked during a commit cycle then the change sets are added.
     * whereby changes detected in this method prevail.
     *
     * @ignore
     * @param ClassMetadata $class The class descriptor of the document.
     * @param object $document The document for which to (re)calculate the change set.
     * @throws InvalidArgumentException If the passed document is not MANAGED.
     */
    public function recomputeSingleDocumentChangeSet($class, $document)
    {
        $oid = spl_object_hash($document);

        if ( ! isset($this->documentStates[$oid]) || $this->documentStates[$oid] != self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Document must be managed.');
        }

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata(get_class($document));
        }

        $actualData = $this->getDocumentActualData($document);
        $isChangeTrackingNotify = $class->isChangeTrackingNotify();

        $originalData = isset($this->originalDocumentData[$oid]) ? $this->originalDocumentData[$oid] : array();
        $changeSet = array();
        foreach ($actualData as $propName => $actualValue) {
            $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;
            if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === 'one' && $orgValue !== $actualValue) {
                if ($orgValue !== null) {
                    $this->scheduleOrphanRemoval($orgValue);
                }
                $changeSet[$propName] = array($orgValue, $actualValue);
            } else if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === 'one' && $orgValue !== $actualValue) {
                $changeSet[$propName] = array($orgValue, $actualValue);
            } else if ($isChangeTrackingNotify) {
                continue;
            } else if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'many') {
                if ($orgValue !== $actualValue) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                    if ($orgValue instanceof PersistentCollection) {
                        $this->collectionDeletions[] = $orgValue;
                        $this->collectionUpdates[] = $actualValue;
                        $this->visitedCollections[] = $actualValue;
                    }
                }
            } else if ($orgValue !== $actualValue) {
                $changeSet[$propName] = array($orgValue, $actualValue);
            }
        }
        if ($changeSet) {
            if (isset($this->documentChangeSets[$oid])) {
                $this->documentChangeSets[$oid] = $changeSet + $this->documentChangeSets[$oid];
            }
            $this->originalDocumentData[$oid] = $actualData;
        }
    }

    private function persistNew($class, $document)
    {
        $oid = spl_object_hash($document);
        if (isset($class->lifecycleCallbacks[Events::prePersist])) {
            $class->invokeLifecycleCallbacks(Events::prePersist, $document);
        }
        if ($this->evm->hasListeners(Events::prePersist)) {
            $this->evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($document, $this->dm));
        }

        $this->documentStates[$oid] = self::STATE_MANAGED;

        $this->scheduleForInsert($class, $document);
    }

    /**
     * Executes all document insertions for documents of the specified type.
     *
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with batchInsert()
     */
    private function executeInserts($class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getDocumentPersister($className);
        $collection = $this->dm->getDocumentCollection($className);

        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postPersist]);
        $hasListeners = $this->evm->hasListeners(Events::postPersist);
        if ($hasLifecycleCallbacks || $hasListeners) {
            $documents = array();
        }

        $inserts = array();
        foreach ($this->documentInsertions as $oid => $document) {
            if (get_class($document) === $className) {
                $persister->addInsert($document);
                unset($this->documentInsertions[$oid]);
                if ($hasLifecycleCallbacks || $hasListeners) {
                    $documents[] = $document;
                }
            }
        }

        $postInsertIds = $persister->executeInserts($options);

        if ($postInsertIds) {
            foreach ($postInsertIds as $pair) {
                list($id, $document) = $pair;
                $oid = spl_object_hash($document);
                $class->setIdentifierValue($document, $id);
                $this->documentIdentifiers[$oid] = $id;
                $this->documentStates[$oid] = self::STATE_MANAGED;
                $this->originalDocumentData[$oid][$class->identifier] = $id;
                $this->addToIdentityMap($document);

                if ($hasLifecycleCallbacks || $hasListeners) {
                    if ($hasLifecycleCallbacks) {
                        $class->invokeLifecycleCallbacks(Events::postPersist, $document);
                    }
                    if ($hasListeners) {
                        $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($document, $this->dm));
                    }
                }
                $this->cascadePostPersist($class, $document);
            }
        }
    }

    /**
     * Cascades the postPersist events to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePostPersist(ClassMetadata $class, $document)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    $hasLifecycleCallbacks = isset($entryClass->lifecycleCallbacks[Events::postPersist]);
                    $hasListeners = $this->evm->hasListeners(Events::postPersist);
                    if ($hasLifecycleCallbacks || $hasListeners) {
                        if ($hasLifecycleCallbacks) {
                            $entryClass->invokeLifecycleCallbacks(Events::postPersist, $entry);
                        }
                        if ($hasListeners) {
                            $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($entry, $this->dm));
                        }
                    }
                    $this->cascadePostPersist($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Executes all document updates for documents of the specified type.
     *
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with update()
     */
    private function executeUpdates(ClassMetadata $class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getDocumentPersister($className);

        $hasPreUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::preUpdate]);
        $hasPreUpdateListeners = $this->evm->hasListeners(Events::preUpdate);
        $hasPostUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postUpdate]);
        $hasPostUpdateListeners = $this->evm->hasListeners(Events::postUpdate);

        foreach ($this->documentUpdates as $oid => $document) {
            if (get_class($document) == $className || $document instanceof Proxy && $document instanceof $className) {
                if ( ! $class->isEmbeddedDocument) {
                    if ($hasPreUpdateLifecycleCallbacks) {
                        $class->invokeLifecycleCallbacks(Events::preUpdate, $document);
                        $this->recomputeSingleDocumentChangeSet($class, $document);
                    }

                    if ($hasPreUpdateListeners && isset($this->documentChangeSets[$oid])) {
                        $this->evm->dispatchEvent(Events::preUpdate, new Event\PreUpdateEventArgs(
                            $document, $this->dm, $this->documentChangeSets[$oid])
                        );
                    }
                    $this->cascadePreUpdate($class, $document);
                }

                if ( ! $class->isEmbeddedDocument && isset($this->documentChangeSets[$oid]) && $this->documentChangeSets[$oid]) {
                    $persister->update($document, $options);
                }
                unset($this->documentUpdates[$oid]);

                if ( ! $class->isEmbeddedDocument) {
                    if ($hasPostUpdateLifecycleCallbacks) {
                        $class->invokeLifecycleCallbacks(Events::postUpdate, $document);
                    }
                    if ($hasPostUpdateListeners) {
                        $this->evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($document, $this->dm));
                    }
                    $this->cascadePostUpdateAndPostPersist($class, $document);
                }
            }
        }
    }

    /**
     * Cascades the preUpdate event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePreUpdate(ClassMetadata $class, $document)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryOid = spl_object_hash($entry);
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    if ( ! isset($this->documentChangeSets[$entryOid])) {
                        continue;
                    }
                    if ( ! isset($this->documentInsertions[$entryOid])) {
                        if (isset($entryClass->lifecycleCallbacks[Events::preUpdate])) {
                            $entryClass->invokeLifecycleCallbacks(Events::preUpdate, $entry);
                            $this->recomputeSingleDocumentChangeSet($entryClass, $entry);
                        }
                        if ($this->evm->hasListeners(Events::preUpdate)) {
                            $this->evm->dispatchEvent(Events::preUpdate, new Event\PreUpdateEventArgs(
                                $entry, $this->dm, $this->documentChangeSets[$entryOid])
                            );
                        }
                    }
                    $this->cascadePreUpdate($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Cascades the postUpdate and postPersist events to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePostUpdateAndPostPersist(ClassMetadata $class, $document)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryOid = spl_object_hash($entry);
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    if ( ! isset($this->documentChangeSets[$entryOid])) {
                        continue;
                    }
                    if (isset($this->documentInsertions[$entryOid])) {
                        if (isset($entryClass->lifecycleCallbacks[Events::postPersist])) {
                            $entryClass->invokeLifecycleCallbacks(Events::postPersist, $entry);
                        }
                        if ($this->evm->hasListeners(Events::postPersist)) {
                            $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($entry, $this->dm));
                        }
                    } else {
                        if (isset($entryClass->lifecycleCallbacks[Events::postUpdate])) {
                            $entryClass->invokeLifecycleCallbacks(Events::postUpdate, $entry);
                            $this->recomputeSingleDocumentChangeSet($entryClass, $entry);
                        }
                        if ($this->evm->hasListeners(Events::postUpdate)) {
                            $this->evm->dispatchEvent(Events::postUpdate, new Event\PreUpdateEventArgs(
                                $entry, $this->dm, $this->documentChangeSets[$entryOid])
                            );
                        }
                    }
                    $this->cascadePostUpdateAndPostPersist($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Executes all document deletions for documents of the specified type.
     *
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with remove()
     */
    private function executeDeletions(ClassMetadata $class, array $options = array())
    {
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postRemove]);
        $hasListeners = $this->evm->hasListeners(Events::postRemove);

        $className = $class->name;
        $persister = $this->getDocumentPersister($className);
        $collection = $this->dm->getDocumentCollection($className);
        foreach ($this->documentDeletions as $oid => $document) {
            if (get_class($document) == $className || $document instanceof Proxy && $document instanceof $className) {
                if ( ! $class->isEmbeddedDocument) {
                    $persister->delete($document, $options);
                }
                unset(
                    $this->documentDeletions[$oid],
                    $this->documentIdentifiers[$oid],
                    $this->originalDocumentData[$oid]
                );

                // Clear snapshot information for any referenced PersistentCollection
                // http://www.doctrine-project.org/jira/browse/MODM-95
                foreach ($class->fieldMappings as $fieldMapping) {
                    if (isset($fieldMapping['type']) && $fieldMapping['type'] === 'many') {
                        $value = $class->reflFields[$fieldMapping['fieldName']]->getValue($document);
                        if ($value instanceof PersistentCollection) {
                            $value->clearSnapshot();
                        }
                    }
                }

                // Document with this $oid after deletion treated as NEW, even if the $oid
                // is obtained by a new document because the old one went out of scope.
                $this->documentStates[$oid] = self::STATE_NEW;

                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postRemove, $document);
                }
                if ($hasListeners) {
                    $this->evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($document, $this->dm));
                }
                $this->cascadePostRemove($class, $document);
            }
        }
    }

    /**
     * Gets the commit order.
     *
     * @return array
     */
    private function getCommitOrder(array $documentChangeSet = null)
    {
        if ($documentChangeSet === null) {
            $documentChangeSet = array_merge(
                $this->documentInsertions,
                $this->documentUpserts,
                $this->documentUpdates,
                $this->documentDeletions
            );
        }

        $calc = $this->getCommitOrderCalculator();

        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        // We have to inspect changeSet to be able to correctly build dependencies.
        // It is not possible to use IdentityMap here because post inserted ids
        // are not yet available.
        $newNodes = array();

        foreach ($documentChangeSet as $oid => $document) {
            $className = get_class($document);

            if ($calc->hasClass($className)) {
                continue;
            }

            $class = $this->dm->getClassMetadata($className);
            $calc->addClass($class);

            $newNodes[] = $class;
        }

        // Calculate dependencies for new nodes
        while ($class = array_pop($newNodes)) {
            $this->addDependencies($class, $calc);
        }
        return $calc->getCommitOrder();
    }

    /**
     * Add dependencies recursively through embedded documents. Embedded documents
     * may have references to other documents so those need to be saved first.
     *
     * @param ClassMetadata $class
     * @param CommitOrderCalculator $calc
     */
    private function addDependencies(ClassMetadata $class, $calc)
    {
        foreach ($class->fieldMappings as $mapping) {
            $isOwningReference = isset($mapping['reference']) && $mapping['isOwningSide'];
            $isAssociation = isset($mapping['embedded']) || $isOwningReference;
            if (!$isAssociation || !isset($mapping['targetDocument'])) {
                continue;
            }

            $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if ( ! $calc->hasClass($targetClass->name)) {
                $calc->addClass($targetClass);
            }

            $calc->addDependency($targetClass, $class);

            // If the target class has mapped subclasses, these share the same dependency.
            if ( ! $targetClass->subClasses) {
                continue;
            }

            foreach ($targetClass->subClasses as $subClassName) {
                $targetSubClass = $this->dm->getClassMetadata($subClassName);

                if ( ! $calc->hasClass($subClassName)) {
                    $calc->addClass($targetSubClass);

                    $newNodes[] = $targetSubClass;
                }

                $calc->addDependency($targetSubClass, $class);
            }

            // avoid infinite recursion
            if ($class !== $targetClass) {
                $this->addDependencies($targetClass, $calc);
            }
        }
    }

    /**
     * Schedules an document for insertion into the database.
     * If the document already has an identifier, it will be added to the identity map.
     *
     * @param object $document The document to schedule for insertion.
     */
    public function scheduleForInsert($class, $document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->documentUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty document can not be scheduled for insertion.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed document can not be scheduled for insertion.");
        }
        if (isset($this->documentInsertions[$oid])) {
            throw new \InvalidArgumentException("Document can not be scheduled for insertion twice.");
        }

        $this->documentInsertions[$oid] = $document;

        if (!$class->isEmbeddedDocument && $idValue = $class->getIdentifierValue($document)) {
            $this->documentUpserts[$oid] = $document;
            $this->documentIdentifiers[$oid] = $idValue;
        }

        if (isset($this->documentIdentifiers[$oid])) {
            $this->addToIdentityMap($document);
        }
    }

    /**
     * Checks whether an document is scheduled for insertion.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForInsert($document)
    {
        return isset($this->documentInsertions[spl_object_hash($document)]);
    }

    /**
     * Checks whether an document is scheduled for upsert.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForUpsert($document)
    {
        return isset($this->documentUpserts[spl_object_hash($document)]);
    }

    /**
     * Schedules an document for being updated.
     *
     * @param object $document The document to schedule for being updated.
     */
    public function scheduleForUpdate($document)
    {
        $oid = spl_object_hash($document);
        if ( ! isset($this->documentIdentifiers[$oid])) {
            throw new \InvalidArgumentException("Document has no identity.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("Document is removed.");
        }

        if ( ! isset($this->documentUpdates[$oid]) && ! isset($this->documentInsertions[$oid])) {
            $this->documentUpdates[$oid] = $document;
        }
    }

    /**
     * INTERNAL:
     * Schedules an extra update that will be executed immediately after the
     * regular entity updates within the currently running commit cycle.
     *
     * Extra updates for documents are stored as (entity, changeset) tuples.
     *
     * @ignore
     * @param object $document The entity for which to schedule an extra update.
     * @param array $changeset The changeset of the entity (what to update).
     */
    public function scheduleExtraUpdate($document, array $changeset)
    {
        $oid = spl_object_hash($document);
        if (isset($this->extraUpdates[$oid])) {
            list($ignored, $changeset2) = $this->extraUpdates[$oid];
            $this->extraUpdates[$oid] = array($document, $changeset + $changeset2);
        } else {
            $this->extraUpdates[$oid] = array($document, $changeset);
        }
    }

    /**
     * Checks whether an document is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty documents are only registered
     * at commit time.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForUpdate($document)
    {
        return isset($this->documentUpdates[spl_object_hash($document)]);
    }

    public function isScheduledForDirtyCheck($document)
    {
        $rootDocumentName = $this->dm->getClassMetadata(get_class($document))->rootDocumentName;
        return isset($this->scheduledForDirtyCheck[$rootDocumentName][spl_object_hash($document)]);
    }

    /**
     * INTERNAL:
     * Schedules an document for deletion.
     *
     * @param object $document
     */
    public function scheduleForDelete($document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->documentInsertions[$oid])) {
            if ($this->isInIdentityMap($document)) {
                $this->removeFromIdentityMap($document);
            }
            unset($this->documentInsertions[$oid]);
            return; // document has not been persisted yet, so nothing more to do.
        }

        if ( ! $this->isInIdentityMap($document)) {
            return; // ignore
        }

        $this->removeFromIdentityMap($document);

        if (isset($this->documentUpdates[$oid])) {
            unset($this->documentUpdates[$oid]);
        }
        if ( ! isset($this->documentDeletions[$oid])) {
            $this->documentDeletions[$oid] = $document;
        }
    }

    /**
     * Checks whether an document is registered as removed/deleted with the unit
     * of work.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForDelete($document)
    {
        return isset($this->documentDeletions[spl_object_hash($document)]);
    }

    /**
     * Checks whether an document is scheduled for insertion, update or deletion.
     *
     * @param $document
     * @return boolean
     */
    public function isDocumentScheduled($document)
    {
        $oid = spl_object_hash($document);
        return isset($this->documentInsertions[$oid]) ||
                isset($this->documentUpdates[$oid]) ||
                isset($this->documentDeletions[$oid]);
    }

    /**
     * INTERNAL:
     * Registers an document in the identity map.
     * Note that documents in a hierarchy are registered with the class name of
     * the root document.
     *
     * @ignore
     * @param object $document  The document to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the document in question is already managed.
     */
    public function addToIdentityMap($document)
    {
        $classMetadata = $this->dm->getClassMetadata(get_class($document));
        if ($classMetadata->isEmbeddedDocument) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
            $id = $classMetadata->getPHPIdentifierValue($id);
        }
        if ($id === '') {
            throw new \InvalidArgumentException("The given document has no identity.");
        }
        $className = $classMetadata->rootDocumentName;
        if (isset($this->identityMap[$className][$id])) {
            return false;
        }
        $this->identityMap[$className][$id] = $document;
        if ($document instanceof NotifyPropertyChanged) {
            $document->addPropertyChangedListener($this);
        }
        return true;
    }

    /**
     * Gets the state of an document within the current unit of work.
     *
     * NOTE: This method sees documents that are not MANAGED or REMOVED and have a
     *       populated identifier, whether it is generated or manually assigned, as
     *       DETACHED. This can be incorrect for manually assigned identifiers.
     *
     * @param object $document
     * @param integer $assume The state to assume if the state is not yet known. This is usually
     *                        used to avoid costly state lookups, in the worst case with a database
     *                        lookup.
     * @return int The document state.
     */
    public function getDocumentState($document, $assume = null)
    {
        $oid = spl_object_hash($document);
        if ( ! isset($this->documentStates[$oid])) {
            $class = $this->dm->getClassMetadata(get_class($document));
            if ($class->isEmbeddedDocument) {
                return self::STATE_NEW;
            }
            // State can only be NEW or DETACHED, because MANAGED/REMOVED states are known.
            // Note that you can not remember the NEW or DETACHED state in _documentStates since
            // the UoW does not hold references to such objects and the object hash can be reused.
            // More generally because the state may "change" between NEW/DETACHED without the UoW being aware of it.
            if ($assume === null) {
                $id = $class->getIdentifierValue($document);
                if ( ! $id) {
                    return self::STATE_NEW;
                } else {
                    // Check for a version field, if available, to avoid a db lookup.
                    if ($class->isVersioned) {
                        if ($class->reflFields[$class->versionField]->getValue($document)) {
                            return self::STATE_DETACHED;
                        } else {
                            return self::STATE_NEW;
                        }
                    } else {
                        // Last try before db lookup: check the identity map.
                        if ($this->tryGetById($id, $class->rootDocumentName)) {
                            return self::STATE_DETACHED;
                        } else {
                            // db lookup
                            if ($this->getDocumentPersister(get_class($document))->exists($document)) {
                                return self::STATE_DETACHED;
                            } else {
                                return self::STATE_NEW;
                            }
                        }
                    }
                }
            } else {
                return $assume;
            }
        }
        return $this->documentStates[$oid];
    }

    /**
     * INTERNAL:
     * Removes an document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @ignore
     * @param object $document
     * @return boolean
     */
    public function removeFromIdentityMap($document)
    {
        $oid = spl_object_hash($document);
        $classMetadata = $this->dm->getClassMetadata(get_class($document));
        $id = $this->documentIdentifiers[$oid];
        if ( ! $classMetadata->isEmbeddedDocument) {
            $id = $classMetadata->getPHPIdentifierValue($id);
        }
        if ($id === '') {
            throw new \InvalidArgumentException("The given document has no identity.");
        }
        $className = $classMetadata->rootDocumentName;
        if (isset($this->identityMap[$className][$id])) {
            unset($this->identityMap[$className][$id]);
            $this->documentStates[$oid] = self::STATE_DETACHED;
            return true;
        }

        return false;
    }

    /**
     * INTERNAL:
     * Gets an document in the identity map by its identifier hash.
     *
     * @ignore
     * @param string $id
     * @param string $rootClassName
     * @return object
     */
    public function getById($id, $rootClassName)
    {
        return $this->identityMap[$rootClassName][$id];
    }

    /**
     * INTERNAL:
     * Tries to get an document by its identifier hash. If no document is found for
     * the given hash, FALSE is returned.
     *
     * @ignore
     * @param string $id
     * @param string $rootClassName
     * @return mixed The found document or FALSE.
     */
    public function tryGetById($id, $rootClassName)
    {
        return isset($this->identityMap[$rootClassName][$id]) ?
                $this->identityMap[$rootClassName][$id] : false;
    }

    /**
     * Schedules a document for dirty-checking at commit-time.
     *
     * @param object $document The document to schedule for dirty-checking.
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($document)
    {
        $rootClassName = $this->dm->getClassMetadata(get_class($document))->rootDocumentName;
        $this->scheduledForDirtyCheck[$rootClassName][spl_object_hash($document)] = $document;
    }

    /**
     * Checks whether an document is registered in the identity map of this UnitOfWork.
     *
     * @param object $document
     * @return boolean
     */
    public function isInIdentityMap($document)
    {
        $oid = spl_object_hash($document);
        if ( ! isset($this->documentIdentifiers[$oid])) {
            return false;
        }
        $classMetadata = $this->dm->getClassMetadata(get_class($document));
        $id = $this->documentIdentifiers[$oid];
        if ( ! $classMetadata->isEmbeddedDocument) {
            $id = $classMetadata->getPHPIdentifierValue($id);
        }
        if ($id === '') {
            return false;
        }

        return isset($this->identityMap[$classMetadata->rootDocumentName][$id]);
    }

    /**
     * INTERNAL:
     * Checks whether an identifier hash exists in the identity map.
     *
     * @ignore
     * @param string $id
     * @param string $rootClassName
     * @return boolean
     */
    public function containsId($id, $rootClassName)
    {
        return isset($this->identityMap[$rootClassName][$id]);
    }

    /**
     * Persists an document as part of the current unit of work.
     *
     * @param object $document The document to persist.
     */
    public function persist($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isMappedSuperclass) {
            throw MongoDBException::cannotPersistMappedSuperclass($class->name);
        }
        $visited = array();
        $this->doPersist($document, $visited);
    }

    /**
     * Saves an document as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * NOTE: This method always considers documents that are not yet known to
     * this UnitOfWork as NEW.
     *
     * @param object $document The document to persist.
     * @param array $visited The already visited documents.
     */
    private function doPersist($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // Mark visited

        $class = $this->dm->getClassMetadata(get_class($document));

        $documentState = $this->getDocumentState($document, self::STATE_NEW);
        switch ($documentState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($document);
                }
                break;
            case self::STATE_NEW:
                $this->persistNew($class, $document);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException(
                        "Behavior of persist() for a detached document is not yet defined.");
            case self::STATE_REMOVED:
                if ( ! $class->isEmbeddedDocument) {
                    // Document becomes managed again
                    if ($this->isScheduledForDelete($document)) {
                        unset($this->documentDeletions[$oid]);
                    } else {
                        //FIXME: There's more to think of here...
                        $this->scheduleForInsert($class, $document);
                    }
                    break;
                }
            default:
                throw MongoDBException::invalidDocumentState($documentState);
        }

        $this->cascadePersist($document, $visited);
    }

    /**
     * Deletes an document as part of the current unit of work.
     *
     * @param object $document The document to remove.
     */
    public function remove($document)
    {
        $visited = array();
        $this->doRemove($document, $visited);
    }

    /**
     * Deletes an document as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * @param object $document The document to delete.
     * @param array $visited The map of the already visited documents.
     * @throws InvalidArgumentException If the instance is a detached document.
     */
    private function doRemove($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));
        $documentState = $this->getDocumentState($document);
        switch ($documentState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
                if (isset($class->lifecycleCallbacks[Events::preRemove])) {
                    $class->invokeLifecycleCallbacks(Events::preRemove, $document);
                }
                if ($this->evm->hasListeners(Events::preRemove)) {
                    $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($document, $this->dm));
                }
                $this->scheduleForDelete($document);
                $this->cascadePreRemove($class, $document);
                break;
            case self::STATE_DETACHED:
                throw MongoDBException::detachedDocumentCannotBeRemoved();
            default:
                throw MongoDBException::invalidDocumentState($documentState);
        }

        $this->cascadeRemove($document, $visited);
    }

    /**
     * Cascades the preRemove event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePreRemove(ClassMetadata $class, $document)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    if (isset($entryClass->lifecycleCallbacks[Events::preRemove])) {
                        $entryClass->invokeLifecycleCallbacks(Events::preRemove, $entry);
                    }
                    if ($this->evm->hasListeners(Events::preRemove)) {
                        $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($entry, $this->dm));
                    }
                    $this->cascadePreRemove($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Cascades the postRemove event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePostRemove(ClassMetadata $class, $document)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    if (isset($entryClass->lifecycleCallbacks[Events::postRemove])) {
                        $entryClass->invokeLifecycleCallbacks(Events::postRemove, $entry);
                    }
                    if ($this->evm->hasListeners(Events::postRemove)) {
                        $this->evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($entry, $this->dm));
                    }
                    $this->cascadePostRemove($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Merges the state of the given detached document into this UnitOfWork.
     *
     * @param object $document
     * @return object The managed copy of the document.
     */
    public function merge($document)
    {
        $visited = array();
        return $this->doMerge($document, $visited);
    }

    /**
     * Executes a merge operation on an document.
     *
     * @param object $document
     * @param array $visited
     * @return object The managed copy of the document.
     * @throws InvalidArgumentException If the document instance is NEW.
     */
    private function doMerge($document, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED documents are ignored by the merge operation.
        if ($this->getDocumentState($document, self::STATE_DETACHED) == self::STATE_MANAGED) {
            $managedCopy = $document;
        } else {
            $id = null;
            if (!$class->isEmbeddedDocument) {
                // Try to look the entity up in the identity map.
                $id = $class->getIdentifierValue($document);
            }

            // If there is no ID, it is actually NEW.
            if ( ! $id) {
                $managedCopy = $class->newInstance();
                $this->persistNew($class, $managedCopy);
            } else {
                $managedCopy = $this->tryGetById($id, $class->rootDocumentName);
                if ($managedCopy) {
                    // We have the entity in-memory already, just make sure its not removed.
                    if ($this->getDocumentState($managedCopy) == self::STATE_REMOVED) {
                        throw new InvalidArgumentException('Removed entity detected during merge.'
                                . ' Can not merge with a removed entity.');
                    }
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->dm->find($class->name, $id);
                }

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed entity was not found.
                    $managedCopy = $class->newInstance();
                    $class->setIdentifierValue($managedCopy, $id);
                    $this->persistNew($class, $managedCopy);
                }
            }

            if ($class->isVersioned) {
                $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
                $documentVersion = $class->reflFields[$class->versionField]->getValue($document);
                // Throw exception if versions dont match.
                if ($managedCopyVersion != $documentVersion) {
                    throw LockException::lockFailedVersionMissmatch($documentVersion, $managedCopyVersion);
                }
            }

            // Merge state of $document into existing (managed) entity
            foreach ($class->reflFields as $name => $prop) {
                if ( ! isset($class->fieldMappings[$name]['embedded']) &&  ! isset($class->fieldMappings[$name]['reference'])) {
                    $prop->setValue($managedCopy, $prop->getValue($document));
                } else {
                    $assoc2 = $class->fieldMappings[$name];
                    if ($assoc2['type'] === 'one') {
                        $other = $prop->getValue($document);
                        if ($other === null) {
                            $prop->setValue($managedCopy, null);
                        } else if ($other instanceof Proxy && !$other->__isInitialized__) {
                            // do not merge fields marked lazy that have not been fetched.
                            continue;
                        } else if ( ! isset($assoc2['embedded']) && ! $assoc2['isCascadeMerge']) {
                            if ($this->getDocumentState($other, self::STATE_DETACHED) == self::STATE_MANAGED) {
                                $prop->setValue($managedCopy, $other);
                            } else {
                                $targetDocument = isset($assoc2['targetDocument']) ? $assoc2['targetDocument'] : get_class($other);
                                $targetClass = $this->dm->getClassMetadata($targetDocument);
                                $id = $targetClass->getIdentifierValue($other);
                                $proxy = $this->dm->getProxyFactory()->getProxy($targetDocument, $id);
                                $prop->setValue($managedCopy, $proxy);
                                $this->registerManaged($proxy, $id, array());
                            }
                        }
                    } else {
                        $mergeCol = $prop->getValue($document);
                        if ($mergeCol instanceof PersistentCollection && ! $mergeCol->isInitialized()) {
                            // do not merge fields marked lazy that have not been fetched.
                            // keep the lazy persistent collection of the managed copy.
                            continue;
                        }

                        foreach ($mergeCol as $entry) {
                            $targetDocument = isset($assoc2['targetDocument']) ? $assoc2['targetDocument'] : get_class($entry);
                            $targetClass = $this->dm->getClassMetadata($targetDocument);
                            if ($targetClass->isEmbeddedDocument) {
                                $this->registerManaged($entry, null, array());
                            } else {
                                $id = $targetClass->getIdentifierValue($entry);
                                $this->registerManaged($entry, $id, array());
                            }
                        }

                        if ( ! $mergeCol instanceof PersistentCollection) {
                            if ( ! $mergeCol instanceof Collection) {
                                $mergeCol = new ArrayCollection($mergeCol);
                            }
                            $mergeCol = new PersistentCollection($mergeCol, $this->dm, $this, $this->cmd);
                            $mergeCol->setInitialized(true);
                        } else {
                            $mergeCol->setDocumentManager($this->dm);
                        }
                        $mergeCol->setOwner($managedCopy, $assoc2);
                        $mergeCol->setDirty(true); // mark for dirty checking
                        $prop->setValue($managedCopy, $mergeCol);
                    }
                }
                if ($class->isChangeTrackingNotify()) {
                    // Just treat all properties as changed, there is no other choice.
                    $this->propertyChanged($managedCopy, $name, null, $prop->getValue($managedCopy));
                }
            }
            if ($class->isChangeTrackingDeferredExplicit()) {
                $this->scheduleForDirtyCheck($document);
            }
        }

        if ($prevManagedCopy !== null) {
            $assocField = $assoc->sourceFieldName;
            $prevClass = $this->dm->getClassMetadata(get_class($prevManagedCopy));
            if ($assoc->isOneToOne()) {
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->unwrap()->add($managedCopy);
                if ($assoc->isOneToMany()) {
                    $class->reflFields[$assoc->mappedBy]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        // Mark the managed copy visited as well
        $visited[spl_object_hash($managedCopy)] = true;

        $this->cascadeMerge($document, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * Detaches an document from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $document The document to detach.
     */
    public function detach($document)
    {
        $visited = array();
        $this->doDetach($document, $visited);
    }

    /**
     * Executes a detach operation on the given document.
     *
     * @param object $document
     * @param array $visited
     * @internal This method always considers documents with an assigned identifier as DETACHED.
     */
    private function doDetach($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        switch ($this->getDocumentState($document, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->removeFromIdentityMap($document);
                unset($this->documentInsertions[$oid], $this->documentUpdates[$oid],
                        $this->documentDeletions[$oid], $this->documentIdentifiers[$oid],
                        $this->documentStates[$oid], $this->originalDocumentData[$oid]);
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }

        $this->cascadeDetach($document, $visited);
    }

    /**
     * Refreshes the state of the given document from the database, overwriting
     * any local, unpersisted changes.
     *
     * @param object $document The document to refresh.
     * @throws InvalidArgumentException If the document is not MANAGED.
     */
    public function refresh($document)
    {
        $visited = array();
        $this->doRefresh($document, $visited);
    }

    /**
     * Executes a refresh operation on an document.
     *
     * @param object $document The document to refresh.
     * @param array $visited The already visited documents during cascades.
     * @throws InvalidArgumentException If the document is not MANAGED.
     */
    private function doRefresh($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($this->getDocumentState($document) == self::STATE_MANAGED) {
            $id = $class->getDatabaseIdentifierValue($this->documentIdentifiers[$oid]);
            $this->getDocumentPersister($class->name)->refresh($id, $document);
        } else {
            throw new \InvalidArgumentException("Document is not MANAGED.");
        }

        $this->cascadeRefresh($document, $visited);
    }

    /**
     * Cascades a refresh operation to associated documents.
     *
     * @param object $document
     * @param array $visited
     */
    private function cascadeRefresh($document, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['reference']) && ! $mapping['isCascadeRefresh']) {
                continue;
            }
            if (isset($mapping['embedded'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->cascadeRefresh($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->cascadeRefresh($relatedDocuments, $visited);
                }
            } elseif (isset($mapping['reference'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doRefresh($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doRefresh($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Cascades a detach operation to associated documents.
     *
     * @param object $document
     * @param array $visited
     */
    private function cascadeDetach($document, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['embedded']) && ! $mapping['isCascadeDetach']) {
                continue;
            }
            if (isset($mapping['embedded'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->cascadeDetach($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->cascadeDetach($relatedDocuments, $visited);
                }
            } elseif (isset($mapping['reference'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doDetach($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doDetach($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Cascades a merge operation to associated documents.
     *
     * @param object $document
     * @param object $managedCopy
     * @param array $visited
     */
    private function cascadeMerge($document, $managedCopy, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['embedded']) && ! $mapping['isCascadeMerge']) {
                continue;
            }
            if (isset($mapping['embedded']) || isset($mapping['reference'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doMerge($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doMerge($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Cascades the save operation to associated documents.
     *
     * @param object $document
     * @param array $visited
     * @param array $insertNow
     */
    private function cascadePersist($document, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['embedded']) && ! $mapping['isCascadePersist']) {
                continue;
            }
            if (isset($mapping['embedded']) || isset($mapping['reference'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    if ($relatedDocuments instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedDocuments = $relatedDocuments->unwrap();
                    }
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doPersist($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doPersist($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Cascades the delete operation to associated documents.
     *
     * @param object $document
     * @param array $visited
     */
    private function cascadeRemove($document, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['embedded']) && ! $mapping['isCascadeRemove']) {
                continue;
            }
            if (isset($mapping['embedded'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->cascadeRemove($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->cascadeRemove($relatedDocuments, $visited);
                }
            } elseif (isset($mapping['reference'])) {
                $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doRemove($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doRemove($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Acquire a lock on the given document.
     *
     * @param object $document
     * @param int $lockMode
     * @param int $lockVersion
     */
    public function lock($document, $lockMode, $lockVersion = null)
    {
        if ($this->getDocumentState($document) != self::STATE_MANAGED) {
            throw new \InvalidArgumentException("Document is not MANAGED.");
        }

        $documentName = get_class($document);
        $class = $this->dm->getClassMetadata($documentName);

        if ($lockMode == \Doctrine\ODM\MongoDB\LockMode::OPTIMISTIC) {
            if (!$class->isVersioned) {
                throw LockException::notVersioned($documentName);
            }

            if ($lockVersion != null) {
                $documentVersion = $class->reflFields[$class->versionField]->getValue($document);
                if ($documentVersion != $lockVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $lockVersion, $documentVersion);
                }
            }
        } else if (in_array($lockMode, array(\Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_READ, \Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_WRITE))) {
            $this->getDocumentPersister($class->name)->lock($document, $lockMode);
        }
    }

    /**
     * Releases a lock on the given document.
     *
     * @param object $document
     */
    public function unlock($document)
    {
        if ($this->getDocumentState($document) != self::STATE_MANAGED) {
            throw new \InvalidArgumentException("Document is not MANAGED.");
        }
        $documentName = get_class($document);
        $this->getDocumentPersister($documentName)->unlock($document);
    }

    /**
     * Gets the CommitOrderCalculator used by the UnitOfWork to order commits.
     *
     * @return Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator
     */
    public function getCommitOrderCalculator()
    {
        if ($this->commitOrderCalculator === null) {
            $this->commitOrderCalculator = new CommitOrderCalculator;
        }
        return $this->commitOrderCalculator;
    }

    /**
     * Clears the UnitOfWork.
     *
     * @param string $documentName if given, only documents of this type will get detached
     */
    public function clear($documentName = null)
    {
        if ($documentName === null) {
            $this->identityMap =
            $this->documentIdentifiers =
            $this->originalDocumentData =
            $this->documentChangeSets =
            $this->documentStates =
            $this->scheduledForDirtyCheck =
            $this->documentInsertions =
            $this->documentUpdates =
            $this->documentDeletions =
            $this->collectionUpdates =
            $this->collectionDeletions =
            $this->extraUpdates =
            $this->parentAssociations =
            $this->orphanRemovals = array();

            if ($this->commitOrderCalculator !== null) {
                $this->commitOrderCalculator->clear();
            }
        } else {
            $visited = array();
            foreach ($this->identityMap as $className => $entities) {
                if ($className === $entityName) {
                    foreach ($entities as $entity) {
                        $this->doDetach($entity, $visited, true);
                    }
                }
            }
        }

        if ($this->evm->hasListeners(Events::onClear)) {
            $this->evm->dispatchEvent(Events::onClear, new Event\OnClearEventArgs($this->dm, $documentName));
        }
    }

    /**
     * INTERNAL:
     * Schedules an embedded document for removal. The remove() operation will be
     * invoked on that document at the beginning of the next commit of this
     * UnitOfWork.
     *
     * @ignore
     * @param object $document
     */
    public function scheduleOrphanRemoval($document)
    {
        $this->orphanRemovals[spl_object_hash($document)] = $document;
    }

    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @param PersistentCollection $coll
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll)
    {
        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        $this->collectionDeletions[] = $coll;
    }

    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        return in_array($coll, $this->collectionsDeletions, true);
    }

    /**
     * INTERNAL:
     * Creates an document. Used for reconstitution of documents during hydration.
     *
     * @ignore
     * @param string $className The name of the document class.
     * @param array $data The data for the document.
     * @param array $hints Any hints to account for during reconstitution/lookup of the document.
     * @return object The document instance.
     * @internal Highly performance-sensitive method.
     */
    public function getOrCreateDocument($className, $data, &$hints = array())
    {
        $class = $this->dm->getClassMetadata($className);

        // @TODO figure out how to remove this
        if ($class->discriminatorField) {
            if (isset($data[$class->discriminatorField['name']])) {
                $type = $data[$class->discriminatorField['name']];
                $class = $this->dm->getClassMetadata($class->discriminatorMap[$data[$class->discriminatorField['name']]]);
                unset($data[$class->discriminatorField['name']]);
            }
        }

        $id = $class->getPHPIdentifierValue($data['_id']);
        if (isset($this->identityMap[$class->rootDocumentName][$id])) {
            $document = $this->identityMap[$class->rootDocumentName][$id];
            $oid = spl_object_hash($document);
            if ($document instanceof Proxy && ! $document->__isInitialized__) {
                $document->__isInitialized__ = true;
                $overrideLocalValues = true;
                if ($document instanceof NotifyPropertyChanged) {
                    $document->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = isset($hints[Query::HINT_REFRESH]);
            }
            if ($overrideLocalValues) {
                $data = $this->hydratorFactory->hydrate($document, $data, $hints);
                $this->originalDocumentData[$oid] = $data;
            }
        } else {
            $document = $class->newInstance();
            $this->registerManaged($document, $id, $data);
            $oid = spl_object_hash($document);
            $this->documentStates[$oid] = self::STATE_MANAGED;
            $this->identityMap[$class->rootDocumentName][$id] = $document;
            $data = $this->hydratorFactory->hydrate($document, $data, $hints);
            $this->originalDocumentData[$oid] = $data;
        }
        return $document;
    }

    /**
     * Cascades the preLoad event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     * @param array $data
     */
    private function cascadePreLoad(ClassMetadata $class, $document, $data)
    {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] === 'one') {
                    $value = array($value);
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
                    if (isset($entryClass->lifecycleCallbacks[Events::preLoad])) {
                        $args = array(&$data);
                        $entryClass->invokeLifecycleCallbacks(Events::preLoad, $entry, $args);
                    }
                    if ($this->evm->hasListeners(Events::preLoad)) {
                        $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($entry, $this->dm, $data[$mapping['name']]));
                    }
                    $this->cascadePreLoad($entryClass, $entry, $data[$mapping['name']]);
                }
            }
        }
    }

    /**
     * Initializes (loads) an uninitialized persistent collection of a document.
     *
     * @param PeristentCollection $collection The collection to initialize.
     */
    public function loadCollection(PersistentCollection $collection)
    {
        $this->getDocumentPersister(get_class($collection->getOwner()))->loadCollection($collection);
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * Gets the original data of an document. The original data is the data that was
     * present at the time the document was reconstituted from the database.
     *
     * @param object $document
     * @return array
     */
    public function getOriginalDocumentData($document)
    {
        $oid = spl_object_hash($document);
        if (isset($this->originalDocumentData[$oid])) {
            return $this->originalDocumentData[$oid];
        }
        return array();
    }

    /**
     * @ignore
     */
    public function setOriginalDocumentData($document, array $data)
    {
        $this->originalDocumentData[spl_object_hash($document)] = $data;
    }

    /**
     * INTERNAL:
     * Sets a property value of the original data array of an document.
     *
     * @ignore
     * @param string $oid
     * @param string $property
     * @param mixed $value
     */
    public function setOriginalDocumentProperty($oid, $property, $value)
    {
        $this->originalDocumentData[$oid][$property] = $value;
    }

    /**
     * Gets the identifier of an document.
     * The returned value is always an array of identifier values. If the document
     * has a composite identifier then the identifier values are in the same
     * order as the identifier field names as returned by ClassMetadata#getIdentifierFieldNames().
     *
     * @param object $document
     * @return array The identifier values.
     */
    public function getDocumentIdentifier($document)
    {
        return isset($this->documentIdentifiers[spl_object_hash($document)]) ?
            $this->documentIdentifiers[spl_object_hash($document)] : null;
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return boolean TRUE if this UnitOfWork has pending insertions, FALSE otherwise.
     */
    public function hasPendingInsertions()
    {
        return ! empty($this->documentInsertions);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of documents in the identity map.
     *
     * @return integer
     */
    public function size()
    {
        $count = 0;
        foreach ($this->identityMap as $documentSet) {
            $count += count($documentSet);
        }
        return $count;
    }

    /**
     * INTERNAL:
     * Registers a document as managed.
     *
     * @param object $document The document.
     * @param array $id The identifier values.
     * @param array $data The original document data.
     */
    public function registerManaged($document, $id, array $data)
    {
        $oid = spl_object_hash($document);
        if ($id === null) {
            $this->documentIdentifiers[$oid] = $oid;
        } else {
            $this->documentIdentifiers[$oid] = $id;
        }
        $this->documentStates[$oid] = self::STATE_MANAGED;
        $this->originalDocumentData[$oid] = $data;
        $this->addToIdentityMap($document);
    }

    /**
     * INTERNAL:
     * Clears the property changeset of the document with the given OID.
     *
     * @param string $oid The document's OID.
     */
    public function clearDocumentChangeSet($oid)
    {
        $this->documentChangeSets[$oid] = array();
    }

    /* PropertyChangedListener implementation */

    /**
     * Notifies this UnitOfWork of a property change in an document.
     *
     * @param object $document The document that owns the property.
     * @param string $propertyName The name of the property that changed.
     * @param mixed $oldValue The old value of the property.
     * @param mixed $newValue The new value of the property.
     */
    public function propertyChanged($document, $propertyName, $oldValue, $newValue)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));

        if ( ! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark document for synchronization
        $this->documentChangeSets[$oid][$propertyName] = array($oldValue, $newValue);
        if ( ! isset($this->scheduledForDirtyCheck[$class->rootDocumentName][$oid])) {
            $this->scheduleForDirtyCheck($document);
        }
    }

    /**
     * Gets the currently scheduled document insertions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledDocumentInsertions()
    {
        return $this->documentInsertions;
    }

    /**
     * Gets the currently scheduled document updates in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledDocumentUpdates()
    {
        return $this->documentUpdates;
    }

    /**
     * Gets the currently scheduled document deletions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledDocumentDeletions()
    {
        return $this->documentDeletions;
    }

    /**
     * Get the currently scheduled complete collection deletions
     *
     * @return array
     */
    public function getScheduledCollectionDeletions()
    {
        return $this->collectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @return array
     */
    public function getScheduledCollectionUpdates()
    {
        return $this->collectionUpdates;
    }    

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     * 
     * @param object
     * @return void
     */
    public function initializeObject($obj)
    {
        if ($obj instanceof Proxy) {
            $obj->__load();
        } else if ($obj instanceof PersistentCollection) {
            $obj->initialize();
        }
    }

    private static function objToStr($obj)
    {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj).'@'.spl_object_hash($obj);
    }
}
