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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * A document is in MANAGED state when its persistence is managed by a DocumentManager.
     */
    const STATE_MANAGED = 1;

    /**
     * A document is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by a DocumentManager.
     */
    const STATE_NEW = 2;

    /**
     * A detached document is an instance with a persistent identity that is not
     * (or no longer) associated with a DocumentManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;

    /**
     * A removed document instance is an instance with a persistent identity,
     * associated with a DocumentManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_REMOVED = 4;

    /**
     * The identity map holds references to all managed documents.
     *
     * Documents are grouped by their class name, and then indexed by the
     * serialized string of their database identifier field or, if the class
     * has no identifier, the SPL object hash. Serializing the identifier allows
     * differentiation of values that may be equal (via type juggling) but not
     * identical.
     *
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
     *
     * Documents are grouped by their class name, and then indexed by their SPL
     * object hash. This is only used for documents with a change tracking
     * policy of DEFERRED_EXPLICIT.
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
     * @var DocumentManager
     */
    private $dm;

    /**
     * The calculator used to calculate the order in which changes to
     * documents need to be written to the database.
     *
     * @var Internal\CommitOrderCalculator
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
     * @var Persisters\CollectionPersister
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
     * @todo We might need to clean up this array in clear(), doDetach(), etc.
     * @var array
     */
    private $parentAssociations = array();

    /**
     * Initializes a new UnitOfWork instance, bound to the given DocumentManager.
     *
     * @param DocumentManager $dm
     * @param EventManager $evm
     * @param HydratorFactory $hydratorFactory
     */
    public function __construct(DocumentManager $dm, EventManager $evm, HydratorFactory $hydratorFactory)
    {
        $this->dm = $dm;
        $this->evm = $evm;
        $this->hydratorFactory = $hydratorFactory;
    }

    /**
     * Factory for returning new PersistenceBuilder instances used for preparing data into
     * queries for insert persistence.
     *
     * @return PersistenceBuilder $pb
     */
    public function getPersistenceBuilder()
    {
        if ( ! $this->persistenceBuilder) {
            $this->persistenceBuilder = new PersistenceBuilder($this->dm, $this);
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
            $this->persisters[$documentName] = new Persisters\DocumentPersister($pb, $this->dm, $this->evm, $this, $this->hydratorFactory, $class);
        }
        return $this->persisters[$documentName];
    }

    /**
     * Get the collection persister instance.
     *
     * @return \Doctrine\ODM\MongoDB\Persisters\CollectionPersister
     */
    public function getCollectionPersister()
    {
        if ( ! isset($this->collectionPersister)) {
            $pb = $this->getPersistenceBuilder();
            $this->collectionPersister = new Persisters\CollectionPersister($this->dm, $pb, $this);
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
        } elseif (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } elseif (is_array($document)) {
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
            $this->orphanRemovals)
        ) {
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

        if ($this->documentUpserts) {
            foreach ($commitOrder as $class) {
                if ($class->isEmbeddedDocument) {
                    continue;
                }
                $this->executeUpserts($class, $options);
            }
        }

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
            $this->getCollectionPersister()->delete($collectionToDelete, $options);
        }
        // Collection updates (deleteRows, updateRows, insertRows)
        foreach ($this->collectionUpdates as $collectionToUpdate) {
            $this->getCollectionPersister()->update($collectionToUpdate, $options);
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
            $this->evm->dispatchEvent(Events::postFlush, new Event\PostFlushEventArgs($this->dm));
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
     * Compute changesets of all documents scheduled for insertion.
     *
     * Embedded documents will not be processed.
     */
    private function computeScheduleInsertsChangeSets()
    {
        foreach ($this->documentInsertions as $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if ($class->isEmbeddedDocument) {
                continue;
            }

            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Compute changesets of all documents scheduled for upsert.
     *
     * Embedded documents will not be processed.
     */
    private function computeScheduleUpsertsChangeSets()
    {
        foreach ($this->documentUpserts as $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if ($class->isEmbeddedDocument) {
                continue;
            }

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
     * @throws \InvalidArgumentException If the document is not STATE_MANAGED
     * @return void
     */
    private function computeSingleDocumentChangeSet($document)
    {
        $state = $this->getDocumentState($document);

        if ($state !== self::STATE_MANAGED && $state !== self::STATE_REMOVED) {
            throw new \InvalidArgumentException("Document has to be managed or scheduled for removal for single computation " . self::objToStr($document));
        }

        $class = $this->dm->getClassMetadata(get_class($document));

        if ($state === self::STATE_MANAGED && $class->isChangeTrackingDeferredImplicit()) {
            $this->persist($document);
        }

        // Compute changes for INSERTed and UPSERTed documents first. This must always happen even in this case.
        $this->computeScheduleInsertsChangeSets();
        $this->computeScheduleUpsertsChangeSets();

        // Ignore uninitialized proxy objects
        if ($document instanceof Proxy && ! $document->__isInitialized__) {
            return;
        }

        // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
        $oid = spl_object_hash($document);

        if ( ! isset($this->documentInsertions[$oid])
            && ! isset($this->documentUpserts[$oid])
            && ! isset($this->documentDeletions[$oid])
            && isset($this->documentStates[$oid])
        ) {
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
     * Gets the changeset for a document.
     *
     * @param object $document
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
            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
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
                $coll = new PersistentCollection($value, $this->dm, $this);
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
        if ( ! empty($class->lifecycleCallbacks[Events::preFlush])) {
            $class->invokeLifecycleCallbacks(Events::preFlush, $document);
        }

        $this->computeOrRecomputeChangeSet($class, $document);
    }

    /**
     * Used to do the common work of computeChangeSet and recomputeSingleDocumentChangeSet
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     * @param object $document
     * @param boolean $recompute
     */
    private function computeOrRecomputeChangeSet(ClassMetadata $class, $document, $recompute = false)
    {
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
            if ($isChangeTrackingNotify && ! $recompute) {
                $changeSet = $this->documentChangeSets[$oid];
            } else {
                $changeSet = array();
            }

            foreach ($actualData as $propName => $actualValue) {
                // skip not saved fields
                if (isset($class->fieldMappings[$propName]['notSaved']) && $class->fieldMappings[$propName]['notSaved'] === true) {
                    continue;
                }

                $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;

                // skip if value has not changed
                if ($orgValue === $actualValue) {
                    // but consider dirty GridFSFile instances as changed
                    if ( ! (isset($class->fieldMappings[$propName]['file']) && $actualValue->isDirty())) {
                        continue;
                    }
                }

                // if relationship is a embed-one, schedule orphan removal to trigger cascade remove operations
                if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === 'one') {
                    if ($orgValue !== null) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }

                    $changeSet[$propName] = array($orgValue, $actualValue);
                    continue;
                }

                // if owning side of reference-one relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === 'one' && $class->fieldMappings[$propName]['isOwningSide']) {
                    if ($orgValue !== null && $class->fieldMappings[$propName]['orphanRemoval']) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }

                    $changeSet[$propName] = array($orgValue, $actualValue);
                    continue;
                }

                if ($isChangeTrackingNotify) {
                    continue;
                }

                // ignore inverse side of reference-many relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === 'many' && $class->fieldMappings[$propName]['isInverseSide']) {
                    continue;
                }

                // Persistent collection was exchanged with the "originally"
                // created one. This can only mean it was cloned and replaced
                // on another document.
                if ($actualValue instanceof PersistentCollection) {
                    $owner = $actualValue->getOwner();
                    if ($owner === null) { // cloned
                        $actualValue->setOwner($document, $class->fieldMappings[$propName]);
                    } elseif ($owner !== $document) { // no clone, we have to fix
                        if ( ! $actualValue->isInitialized()) {
                            $actualValue->initialize(); // we have to do this otherwise the cols share state
                        }
                        $newValue = clone $actualValue;
                        $newValue->setOwner($document, $class->fieldMappings[$propName]);
                        $class->reflFields[$propName]->setValue($document, $newValue);
                    }
                }

                // if embed-many or reference-many relationship
                if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'many') {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                    if ($orgValue instanceof PersistentCollection) {
                        $this->collectionDeletions[] = $orgValue;
                    }
                    continue;
                }

                // skip equivalent date values
                if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'date') {
                    $dateType = Type::getType('date');
                    $dbOrgValue = $dateType->convertToDatabaseValue($orgValue);
                    $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

                    if ($dbOrgValue instanceof \MongoDate && $dbActualValue instanceof \MongoDate && $dbOrgValue == $dbActualValue) {
                        continue;
                    }
                }

                // regular field
                $changeSet[$propName] = array($orgValue, $actualValue);
            }
            if ($changeSet) {
                if ($recompute) {
                    $this->documentChangeSets[$oid] = $changeSet + $this->documentChangeSets[$oid];
                } else {
                    $this->documentChangeSets[$oid] = $changeSet;
                }
                $this->originalDocumentData[$oid] = $actualData;
                $this->documentUpdates[$oid] = $document;
            }
        }

        // Look for changes in associations of the document
        foreach ($class->fieldMappings as $mapping) {
            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
            if (isset($mapping['reference']) || isset($mapping['embedded'])) {
                $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
                if ($value !== null) {
                    $this->computeAssociationChanges($document, $mapping, $value);
                    if (isset($mapping['reference'])) {
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
                            if ( ! $isNewDocument) {
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
        $this->computeScheduleUpsertsChangeSets();

        // Compute changes for other MANAGED documents. Change tracking policies take effect here.
        foreach ($this->identityMap as $className => $documents) {
            $class = $this->dm->getClassMetadata($className);
            if ($class->isEmbeddedDocument) {
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
                // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
                $oid = spl_object_hash($document);
                if ( ! isset($this->documentInsertions[$oid])
                    && ! isset($this->documentUpserts[$oid])
                    && ! isset($this->documentDeletions[$oid])
                    && isset($this->documentStates[$oid])
                ) {
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
     * @throws \InvalidArgumentException
     */
    private function computeAssociationChanges($parentDocument, $mapping, $value)
    {
        $isNewParentDocument = isset($this->documentInsertions[spl_object_hash($parentDocument)]);
        $class = $this->dm->getClassMetadata(get_class($parentDocument));
        $topOrExistingDocument = ( ! $isNewParentDocument || ! $class->isEmbeddedDocument);

        if ($value instanceof PersistentCollection && $value->isDirty() && $mapping['isOwningSide']) {
            if ($topOrExistingDocument || strncmp($mapping['strategy'], 'set', 3) === 0) {
                if ( ! in_array($value, $this->collectionUpdates, true)) {
                    $this->collectionUpdates[] = $value;
                }
            }
            $this->visitedCollections[] = $value;
        }

        if ( ! $mapping['isCascadePersist']) {
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

            // Handle "set" strategy for multi-level hierarchy
            $pathKey = $mapping['strategy'] !== 'set' ? $count : $key;
            $path = $mapping['type'] === 'many' ? $mapping['name'] . '.' . $pathKey : $mapping['name'];

            $count++;
            if ($state == self::STATE_NEW) {
                if ( ! $mapping['isCascadePersist']) {
                    throw new \InvalidArgumentException("A new document was found through a relationship that was not"
                        . " configured to cascade persist operations: " . self::objToStr($entry) . "."
                        . " Explicitly persist the new document or configure cascading persist operations"
                        . " on the relationship.");
                }
                $this->persistNew($targetClass, $entry);
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } elseif ($state == self::STATE_MANAGED && $targetClass->isEmbeddedDocument) {
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } elseif ($state == self::STATE_REMOVED) {
                throw new \InvalidArgumentException("Removed document detected during flush: "
                    . self::objToStr($entry) . ". Remove deleted documents from associations.");
            } elseif ($state == self::STATE_DETACHED) {
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
     * @throws \InvalidArgumentException If the passed document is not MANAGED.
     */
    public function recomputeSingleDocumentChangeSet(ClassMetadata $class, $document)
    {
        $oid = spl_object_hash($document);

        if ( ! isset($this->documentStates[$oid]) || $this->documentStates[$oid] != self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Document must be managed.');
        }

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata(get_class($document));
        }

        $this->computeOrRecomputeChangeSet($class, $document, true);
    }

    /**
     * @param $class
     * @param object $document
     */
    private function persistNew($class, $document)
    {
        $oid = spl_object_hash($document);
        if ( ! empty($class->lifecycleCallbacks[Events::prePersist])) {
            $class->invokeLifecycleCallbacks(Events::prePersist, $document);
        }
        if ($this->evm->hasListeners(Events::prePersist)) {
            $this->evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($document, $this->dm));
        }

        $upsert = false;
        if ($class->identifier) {
            $idValue = $class->getIdentifierValue($document);
            $upsert = !$class->isEmbeddedDocument && $idValue !== null;

            if ($class->generatorType !== ClassMetadata::GENERATOR_TYPE_NONE && $idValue === null) {
                $idValue = $class->idGenerator->generate($this->dm, $document);
                $idValue = $class->getPHPIdentifierValue($class->getDatabaseIdentifierValue($idValue));
                $class->setIdentifierValue($document, $idValue);
            }

            $this->documentIdentifiers[$oid] = $idValue;
        }

        $this->documentStates[$oid] = self::STATE_MANAGED;

        if ($upsert) {
            $this->scheduleForUpsert($class, $document);
        } else {
            $this->scheduleForInsert($class, $document);
        }
    }

    /**
     * Executes all document insertions for documents of the specified type.
     *
     * @param ClassMetadata $class
     * @param array $options Array of options to be used with batchInsert()
     */
    private function executeInserts(ClassMetadata $class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getDocumentPersister($className);
        $collection = $this->dm->getDocumentCollection($className);

        $insertedDocuments = array();

        foreach ($this->documentInsertions as $oid => $document) {
            if (get_class($document) === $className) {
                $persister->addInsert($document);
                $insertedDocuments[] = $document;
                unset($this->documentInsertions[$oid]);
            }
        }

        $persister->executeInserts($options);

        foreach ($insertedDocuments as $document) {
            $id = $class->getIdentifierValue($document);

            /* Inline call to UnitOfWork::registerManager(), but only update the
             * identifier in the original document data.
             */
            $oid = spl_object_hash($document);
            $this->documentIdentifiers[$oid] = $id;
            $this->documentStates[$oid] = self::STATE_MANAGED;
            $this->originalDocumentData[$oid][$class->identifier] = $id;
            $this->addToIdentityMap($document);
        }

        $hasPostPersistLifecycleCallbacks = ! empty($class->lifecycleCallbacks[Events::postPersist]);
        $hasPostPersistListeners = $this->evm->hasListeners(Events::postPersist);

        foreach ($insertedDocuments as $document) {
            if ($hasPostPersistLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::postPersist, $document);
            }
            if ($hasPostPersistListeners) {
                $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($document, $this->dm));
            }
            $this->cascadePostPersist($class, $document);
        }
    }

    /**
     * Executes all document upserts for documents of the specified type.
     *
     * @param ClassMetadata $class
     * @param array $options Array of options to be used with batchInsert()
     */
    private function executeUpserts(ClassMetadata $class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getDocumentPersister($className);
        $collection = $this->dm->getDocumentCollection($className);

        $upsertedDocuments = array();

        foreach ($this->documentUpserts as $oid => $document) {
            if (get_class($document) === $className) {
                $persister->addUpsert($document);
                $upsertedDocuments[] = $document;
                unset($this->documentUpserts[$oid]);
            }
        }

        $persister->executeUpserts($options);

        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postPersist]);
        $hasListeners = $this->evm->hasListeners(Events::postPersist);

        foreach ($upsertedDocuments as $document) {
            if ($hasLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::postPersist, $document);
            }
            if ($hasListeners) {
                $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($document, $this->dm));
            }
            $this->cascadePostPersist($class, $document);
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
        $hasPostPersistListeners = $this->evm->hasListeners(Events::postPersist);

        foreach ($class->fieldMappings as $mapping) {
            if (empty($mapping['embedded'])) {
                continue;
            }

            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);

            if ($value === null) {
                continue;
            }

            if ($mapping['type'] === 'one') {
                $value = array($value);
            }

            if (isset($mapping['targetDocument'])) {
                $embeddedClass = $this->dm->getClassMetadata($mapping['targetDocument']);
            }

            foreach ($value as $embeddedDocument) {
                if ( ! isset($mapping['targetDocument'])) {
                    $embeddedClass = $this->dm->getClassMetadata(get_class($embeddedDocument));
                }

                if ( ! empty($embeddedClass->lifecycleCallbacks[Events::postPersist])) {
                    $embeddedClass->invokeLifecycleCallbacks(Events::postPersist, $embeddedDocument);
                }
                if ($hasPostPersistListeners) {
                    $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($embeddedDocument, $this->dm));
                }
                $this->cascadePostPersist($embeddedClass, $embeddedDocument);
            }
        }
    }

    /**
     * Executes all document updates for documents of the specified type.
     *
     * @param Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with update()
     */
    private function executeUpdates(ClassMetadata $class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getDocumentPersister($className);

        $hasPreUpdateLifecycleCallbacks = ! empty($class->lifecycleCallbacks[Events::preUpdate]);
        $hasPreUpdateListeners = $this->evm->hasListeners(Events::preUpdate);
        $hasPostUpdateLifecycleCallbacks = ! empty($class->lifecycleCallbacks[Events::postUpdate]);
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
        $hasPreUpdateListeners = $this->evm->hasListeners(Events::preUpdate);

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
                        if ( ! empty($entryClass->lifecycleCallbacks[Events::preUpdate])) {
                            $entryClass->invokeLifecycleCallbacks(Events::preUpdate, $entry);
                            $this->recomputeSingleDocumentChangeSet($entryClass, $entry);
                        }
                        if ($hasPreUpdateListeners) {
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
        $hasPostPersistListeners = $this->evm->hasListeners(Events::postPersist);
        $hasPostUpdateListeners = $this->evm->hasListeners(Events::postUpdate);

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
                        if ( ! empty($entryClass->lifecycleCallbacks[Events::postPersist])) {
                            $entryClass->invokeLifecycleCallbacks(Events::postPersist, $entry);
                        }
                        if ($hasPostPersistListeners) {
                            $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($entry, $this->dm));
                        }
                    } else {
                        if ( ! empty($entryClass->lifecycleCallbacks[Events::postUpdate])) {
                            $entryClass->invokeLifecycleCallbacks(Events::postUpdate, $entry);
                            $this->recomputeSingleDocumentChangeSet($entryClass, $entry);
                        }
                        if ($hasPostUpdateListeners) {
                            $this->evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($entry, $this->dm));
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
     * @param ClassMetadata $class
     * @param array $options Array of options to be used with remove()
     */
    private function executeDeletions(ClassMetadata $class, array $options = array())
    {
        $hasPostRemoveLifecycleCallbacks = ! empty($class->lifecycleCallbacks[Events::postRemove]);
        $hasPostRemoveListeners = $this->evm->hasListeners(Events::postRemove);

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

                if ($hasPostRemoveLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postRemove, $document);
                }
                if ($hasPostRemoveListeners) {
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
        // commit order graph yet (don't have a node).
        // We have to inspect changeSet to be able to correctly build dependencies.
        // It is not possible to use IdentityMap here because post inserted ids
        // are not yet available.
        $newNodes = array();

        foreach ($documentChangeSet as $document) {
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
            foreach ($class->associationMappings as $assoc) {
                if ( ! ($assoc['isOwningSide'] && isset($assoc['targetDocument']))) {
                    continue;
                }

                $targetClass = $this->dm->getClassMetadata($assoc['targetDocument']);

                if ( ! $calc->hasClass($targetClass->name)) {
                    $calc->addClass($targetClass);

                    $newNodes[] = $targetClass;
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
            }
        }

        return $calc->getCommitOrder();
    }

    /**
     * Schedules a document for insertion into the database.
     * If the document already has an identifier, it will be added to the
     * identity map.
     *
     * @param ClassMetadata $class
     * @param object $document The document to schedule for insertion.
     * @throws \InvalidArgumentException
     */
    public function scheduleForInsert(ClassMetadata $class, $document)
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

        if (isset($this->documentIdentifiers[$oid])) {
            $this->addToIdentityMap($document);
        }
    }

    /**
     * Schedules a document for upsert into the database and adds it to the
     * identity map
     *
     * @param ClassMetadata $class
     * @param object $document The document to schedule for upsert.
     * @throws \InvalidArgumentException
     */
    public function scheduleForUpsert(ClassMetadata $class, $document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->documentUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty document can not be scheduled for upsert.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed document can not be scheduled for upsert.");
        }
        if (isset($this->documentUpserts[$oid])) {
            throw new \InvalidArgumentException("Document can not be scheduled for upsert twice.");
        }

        $this->documentUpserts[$oid] = $document;
        $this->documentIdentifiers[$oid] = $class->getIdentifierValue($document);
        $this->addToIdentityMap($document);
    }

    /**
     * Checks whether a document is scheduled for insertion.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForInsert($document)
    {
        return isset($this->documentInsertions[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is scheduled for upsert.
     *
     * @param object $document
     * @return boolean
     */
    public function isScheduledForUpsert($document)
    {
        return isset($this->documentUpserts[spl_object_hash($document)]);
    }

    /**
     * Schedules a document for being updated.
     *
     * @param object $document The document to schedule for being updated.
     * @throws \InvalidArgumentException
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

        if ( ! isset($this->documentUpdates[$oid]) && ! isset($this->documentInsertions[$oid]) && ! isset($this->documentUpserts[$oid])) {
            $this->documentUpdates[$oid] = $document;
        }
    }

    /**
     * INTERNAL:
     * Schedules an extra update that will be executed immediately after the
     * regular document updates within the currently running commit cycle.
     *
     * Extra updates for documents are stored as (document, changeset) tuples.
     *
     * @ignore
     * @param object $document The document for which to schedule an extra update.
     * @param array $changeset The changeset of the document (what to update).
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
     * Checks whether a document is registered as dirty in the unit of work.
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
        $class = $this->dm->getClassMetadata(get_class($document));
        return isset($this->scheduledForDirtyCheck[$class->name][spl_object_hash($document)]);
    }

    /**
     * INTERNAL:
     * Schedules a document for deletion.
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
        $this->documentStates[$oid] = self::STATE_REMOVED;

        if (isset($this->documentUpdates[$oid])) {
            unset($this->documentUpdates[$oid]);
        }
        if ( ! isset($this->documentDeletions[$oid])) {
            $this->documentDeletions[$oid] = $document;
        }
    }

    /**
     * Checks whether a document is registered as removed/deleted with the unit
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
     * Checks whether a document is scheduled for insertion, update or deletion.
     *
     * @param $document
     * @return boolean
     */
    public function isDocumentScheduled($document)
    {
        $oid = spl_object_hash($document);
        return isset($this->documentInsertions[$oid]) ||
            isset($this->documentUpserts[$oid]) ||
            isset($this->documentUpdates[$oid]) ||
            isset($this->documentDeletions[$oid]);
    }

    /**
     * INTERNAL:
     * Registers a document in the identity map.
     *
     * Note that documents in a hierarchy are registered with the class name of
     * the root document. Identifiers are serialized before being used as array
     * keys to allow differentiation of equal, but not identical, values.
     *
     * @ignore
     * @param object $document  The document to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the document in question is already managed.
     */
    public function addToIdentityMap($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));

        if ( ! $class->identifier) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
            $id = serialize($class->getDatabaseIdentifierValue($id));
        }

        if (isset($this->identityMap[$class->name][$id])) {
            return false;
        }

        $this->identityMap[$class->name][$id] = $document;

        if ($document instanceof NotifyPropertyChanged) {
            $document->addPropertyChangedListener($this);
        }

        return true;
    }

    /**
     * Gets the state of a document with regard to the current unit of work.
     *
     * @param object   $document
     * @param int|null $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                         This parameter can be set to improve performance of document state detection
     *                         by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                         is either known or does not matter for the caller of the method.
     * @return int The document state.
     */
    public function getDocumentState($document, $assume = null)
    {
        $oid = spl_object_hash($document);

        if (isset($this->documentStates[$oid])) {
            return $this->documentStates[$oid];
        }

        $class = $this->dm->getClassMetadata(get_class($document));

        if ($class->isEmbeddedDocument) {
            return self::STATE_NEW;
        }

        if ($assume !== null) {
            return $assume;
        }

        /* State can only be NEW or DETACHED, because MANAGED/REMOVED states are
         * known. Note that you cannot remember the NEW or DETACHED state in
         * _documentStates since the UoW does not hold references to such
         * objects and the object hash can be reused. More generally, because
         * the state may "change" between NEW/DETACHED without the UoW being
         * aware of it.
         */
        $id = $class->getIdentifierObject($document);

        if ($id === null) {
            return self::STATE_NEW;
        }

        // Check for a version field, if available, to avoid a DB lookup.
        if ($class->isVersioned) {
            return ($class->getFieldValue($document, $class->versionField))
                ? self::STATE_DETACHED
                : self::STATE_NEW;
        }

        // Last try before DB lookup: check the identity map.
        if ($this->tryGetById($id, $class)) {
            return self::STATE_DETACHED;
        }

        // DB lookup
        if ($this->getDocumentPersister($class->name)->exists($document)) {
            return self::STATE_DETACHED;
        }

        return self::STATE_NEW;
    }

    /**
     * INTERNAL:
     * Removes a document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @ignore
     * @param object $document
     * @throws \InvalidArgumentException
     * @return boolean
     */
    public function removeFromIdentityMap($document)
    {
        $oid = spl_object_hash($document);

        // Check if id is registered first
        if ( ! isset($this->documentIdentifiers[$oid])) {
            return false;
        }

        $class = $this->dm->getClassMetadata(get_class($document));

        if ( ! $class->identifier) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
            $id = serialize($class->getDatabaseIdentifierValue($id));
        }

        if (isset($this->identityMap[$class->name][$id])) {
            unset($this->identityMap[$class->name][$id]);
            $this->documentStates[$oid] = self::STATE_DETACHED;
            return true;
        }

        return false;
    }

    /**
     * INTERNAL:
     * Gets a document in the identity map by its identifier hash.
     *
     * @ignore
     * @param mixed         $id    Document identifier
     * @param ClassMetadata $class Document class
     * @return object
     * @throws InvalidArgumentException if the class does not have an identifier
     */
    public function getById($id, ClassMetadata $class)
    {
        if ( ! $class->identifier) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not have an identifier', $class->name));
        }

        $serializedId = serialize($class->getDatabaseIdentifierValue($id));

        return $this->identityMap[$class->name][$serializedId];
    }

    /**
     * INTERNAL:
     * Tries to get a document by its identifier hash. If no document is found
     * for the given hash, FALSE is returned.
     *
     * @ignore
     * @param mixed         $id    Document identifier
     * @param ClassMetadata $class Document class
     * @return mixed The found document or FALSE.
     * @throws InvalidArgumentException if the class does not have an identifier
     */
    public function tryGetById($id, ClassMetadata $class)
    {
        if ( ! $class->identifier) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not have an identifier', $class->name));
        }

        $serializedId = serialize($class->getDatabaseIdentifierValue($id));

        return isset($this->identityMap[$class->name][$serializedId]) ?
            $this->identityMap[$class->name][$serializedId] : false;
    }

    /**
     * Schedules a document for dirty-checking at commit-time.
     *
     * @param object $document The document to schedule for dirty-checking.
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $this->scheduledForDirtyCheck[$class->name][spl_object_hash($document)] = $document;
    }

    /**
     * Checks whether a document is registered in the identity map.
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

        $class = $this->dm->getClassMetadata(get_class($document));

        if ( ! $class->identifier) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
            $id = serialize($class->getDatabaseIdentifierValue($id));
        }

        return isset($this->identityMap[$class->name][$id]);
    }

    /**
     * INTERNAL:
     * Checks whether an identifier exists in the identity map.
     *
     * @ignore
     * @param string $id
     * @param string $rootClassName
     * @return boolean
     */
    public function containsId($id, $rootClassName)
    {
        return isset($this->identityMap[$rootClassName][serialize($id)]);
    }

    /**
     * Persists a document as part of the current unit of work.
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
     * Saves a document as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * NOTE: This method always considers documents that are not yet known to
     * this UnitOfWork as NEW.
     *
     * @param object $document The document to persist.
     * @param array $visited The already visited documents.
     * @throws \InvalidArgumentException
     * @throws MongoDBException
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
                break;
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
     * Deletes a document as part of the current unit of work.
     *
     * @param object $document The document to remove.
     */
    public function remove($document)
    {
        $visited = array();
        $this->doRemove($document, $visited);
    }

    /**
     * Deletes a document as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * @param object $document The document to delete.
     * @param array $visited The map of the already visited documents.
     * @throws MongoDBException
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
                if ( ! empty($class->lifecycleCallbacks[Events::preRemove])) {
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
        $hasPreRemoveListeners = $this->evm->hasListeners(Events::preRemove);

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
                    if ( ! empty($entryClass->lifecycleCallbacks[Events::preRemove])) {
                        $entryClass->invokeLifecycleCallbacks(Events::preRemove, $entry);
                    }
                    if ($hasPreRemoveListeners) {
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
        $hasPostRemoveListeners = $this->evm->hasListeners(Events::postRemove);

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
                    if ( ! empty($entryClass->lifecycleCallbacks[Events::postRemove])) {
                        $entryClass->invokeLifecycleCallbacks(Events::postRemove, $entry);
                    }
                    if ($hasPostRemoveListeners) {
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
     * Executes a merge operation on a document.
     *
     * @param object      $document
     * @param array       $visited
     * @param object|null $prevManagedCopy
     * @param array|null  $assoc
     *
     * @return object The managed copy of the document.
     *
     * @throws InvalidArgumentException If the document instance is NEW.
     * @throws LockException If the entity uses optimistic locking through a
     *                       version attribute and the version check against the
     *                       managed copy fails.
     */
    private function doMerge($document, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $oid = spl_object_hash($document);

        if (isset($visited[$oid])) {
            return $visited[$oid]; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));

        /* First we assume DETACHED, although it can still be NEW but we can
         * avoid an extra DB round trip this way. If it is not MANAGED but has
         * an identity, we need to fetch it from the DB anyway in order to
         * merge. MANAGED documents are ignored by the merge operation.
         */
        $managedCopy = $document;

        if ($this->getDocumentState($document, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            if ($document instanceof Proxy && ! $document->__isInitialized()) {
                $document->__load();
            }

            // Try to look the document up in the identity map.
            $id = $class->isEmbeddedDocument ? null : $class->getIdentifierObject($document);

            if ($id === null) {
                // If there is no identifier, it is actually NEW.
                $managedCopy = $class->newInstance();
                $this->persistNew($class, $managedCopy);
            } else {
                $managedCopy = $this->tryGetById($id, $class);

                if ($managedCopy) {
                    // We have the document in memory already, just make sure it is not removed.
                    if ($this->getDocumentState($managedCopy) === self::STATE_REMOVED) {
                        throw new \InvalidArgumentException('Removed entity detected during merge. Cannot merge with a removed entity.');
                    }
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->dm->find($class->name, $id);
                }

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW
                    $managedCopy = $class->newInstance();
                    $class->setIdentifierValue($managedCopy, $id);
                    $this->persistNew($class, $managedCopy);
                } else {
                    if ($managedCopy instanceof Proxy && ! $managedCopy->__isInitialized__) {
                        $managedCopy->__load();
                    }
                }
            }

            if ($class->isVersioned) {
                $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
                $documentVersion = $class->reflFields[$class->versionField]->getValue($document);

                // Throw exception if versions don't match
                if ($managedCopyVersion != $documentVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $documentVersion, $managedCopyVersion);
                }
            }

            // Merge state of $document into existing (managed) document
            foreach ($class->reflClass->getProperties() as $prop) {
                $name = $prop->name;
                $prop->setAccessible(true);
                if ( ! isset($class->associationMappings[$name])) {
                    if ( ! $class->isIdentifier($name)) {
                        $prop->setValue($managedCopy, $prop->getValue($document));
                    }
                } else {
                    $assoc2 = $class->associationMappings[$name];

                    if ($assoc2['type'] === 'one') {
                        $other = $prop->getValue($document);

                        if ($other === null) {
                            $prop->setValue($managedCopy, null);
                        } elseif ($other instanceof Proxy && ! $other->__isInitialized__) {
                            // Do not merge fields marked lazy that have not been fetched
                            continue;
                        } elseif ( ! $assoc2['isCascadeMerge']) {
                            if ($this->getDocumentState($other) === self::STATE_DETACHED) {
                                $targetDocument = isset($assoc2['targetDocument']) ? $assoc2['targetDocument'] : get_class($other);
                                $targetClass = $this->dm->getClassMetadata($targetDocument);
                                $relatedId = $targetClass->getIdentifierObject($other);

                                if ($targetClass->subClasses) {
                                    $other = $this->dm->find($targetClass->name, $relatedId);
                                } else {
                                    $other = $this->dm->getProxyFactory()->getProxy($assoc2['targetDocument'], $relatedId);
                                    $this->registerManaged($other, $relatedId, array());
                                }
                            }

                            $prop->setValue($managedCopy, $other);
                        }
                    } else {
                        $mergeCol = $prop->getValue($document);

                        if ($mergeCol instanceof PersistentCollection && ! $mergeCol->isInitialized()) {
                            /* Do not merge fields marked lazy that have not
                             * been fetched. Keep the lazy persistent collection
                             * of the managed copy.
                             */
                            continue;
                        }

                        $managedCol = $prop->getValue($managedCopy);

                        if ( ! $managedCol) {
                            $managedCol = new PersistentCollection(new ArrayCollection(), $this->dm, $this);
                            $managedCol->setOwner($managedCopy, $assoc2);
                            $prop->setValue($managedCopy, $managedCol);
                            $this->originalDocumentData[$oid][$name] = $managedCol;
                        }

                        /* Note: do not process association's target documents.
                         * They will be handled during the cascade. Initialize
                         * and, if necessary, clear $managedCol for now.
                         */
                        if ($assoc2['isCascadeMerge']) {
                            $managedCol->initialize();

                            // If $managedCol differs from the merged collection, clear and set dirty
                            if ( ! $managedCol->isEmpty() && $managedCol !== $mergeCol) {
                                $managedCol->unwrap()->clear();
                                $managedCol->setDirty(true);

                                if ($assoc2['isOwningSide'] && $class->isChangeTrackingNotify()) {
                                    $this->scheduleForDirtyCheck($managedCopy);
                                }
                            }
                        }
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
            $assocField = $assoc['fieldName'];
            $prevClass = $this->dm->getClassMetadata(get_class($prevManagedCopy));

            if ($assoc['type'] === 'one') {
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->add($managedCopy);

                if ($assoc['type'] === 'many' && isset($assoc['mappedBy'])) {
                    $class->reflFields[$assoc['mappedBy']]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        // Mark the managed copy visited as well
        $visited[spl_object_hash($managedCopy)] = true;

        $this->cascadeMerge($document, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * Detaches a document from the persistence management. It's persistence will
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
                    $this->documentStates[$oid], $this->originalDocumentData[$oid],
                    $this->parentAssociations[$oid]);
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
     * @throws \InvalidArgumentException If the document is not MANAGED.
     */
    public function refresh($document)
    {
        $visited = array();
        $this->doRefresh($document, $visited);
    }

    /**
     * Executes a refresh operation on a document.
     *
     * @param object $document The document to refresh.
     * @param array $visited The already visited documents during cascades.
     * @throws \InvalidArgumentException If the document is not MANAGED.
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
            if ( ! $mapping['isCascadeRefresh']) {
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
            if ( ! $mapping['isCascadeDetach']) {
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

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadeMerge']; }
        );

        foreach ($associationMappings as $assoc) {
            $relatedDocuments = $class->reflFields[$assoc['fieldName']]->getValue($document);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments === $class->reflFields[$assoc['fieldName']]->getValue($managedCopy)) {
                    // Collections are the same, so there is nothing to do
                    continue;
                }

                if ($relatedDocuments instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedDocuments = $relatedDocuments->unwrap();
                }

                foreach ($relatedDocuments as $relatedDocument) {
                    $this->doMerge($relatedDocument, $visited, $managedCopy, $assoc);
                }
            } elseif ($relatedDocuments !== null) {
                $this->doMerge($relatedDocuments, $visited, $managedCopy, $assoc);
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

        foreach ($class->associationMappings as $fieldName => $mapping) {
            if ( ! $mapping['isCascadePersist']) {
                continue;
            }

            $relatedDocuments = $class->reflFields[$fieldName]->getValue($document);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
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
            if ( ! $mapping['isCascadeRemove']) {
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
     * @throws LockException
     * @throws \InvalidArgumentException
     */
    public function lock($document, $lockMode, $lockVersion = null)
    {
        if ($this->getDocumentState($document) != self::STATE_MANAGED) {
            throw new \InvalidArgumentException("Document is not MANAGED.");
        }

        $documentName = get_class($document);
        $class = $this->dm->getClassMetadata($documentName);

        if ($lockMode == LockMode::OPTIMISTIC) {
            if ( ! $class->isVersioned) {
                throw LockException::notVersioned($documentName);
            }

            if ($lockVersion != null) {
                $documentVersion = $class->reflFields[$class->versionField]->getValue($document);
                if ($documentVersion != $lockVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $lockVersion, $documentVersion);
                }
            }
        } elseif (in_array($lockMode, array(LockMode::PESSIMISTIC_READ, LockMode::PESSIMISTIC_WRITE))) {
            $this->getDocumentPersister($class->name)->lock($document, $lockMode);
        }
    }

    /**
     * Releases a lock on the given document.
     *
     * @param object $document
     * @throws \InvalidArgumentException
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
     * @return \Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator
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
     * @param string|null $documentName if given, only documents of this type will get detached.
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
            $this->documentUpserts =
            $this->documentUpdates =
            $this->documentDeletions =
            $this->extraUpdates =
            $this->collectionUpdates =
            $this->collectionDeletions =
            $this->parentAssociations =
            $this->orphanRemovals = array();

            if ($this->commitOrderCalculator !== null) {
                $this->commitOrderCalculator->clear();
            }
        } else {
            $visited = array();
            foreach ($this->identityMap as $className => $documents) {
                if ($className === $documentName) {
                    foreach ($documents as $document) {
                        $this->doDetach($document, $visited, true);
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

    /**
     * Checks whether a PersistentCollection is scheduled for deletion.
     *
     * @param PersistentCollection $coll
     * @return boolean
     */
    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        return in_array($coll, $this->collectionDeletions, true);
    }

    /**
     * Checks whether a PersistentCollection is scheduled for update.
     *
     * @param PersistentCollection $coll
     * @return boolean
     */
    public function isCollectionScheduledForUpdate(PersistentCollection $coll)
    {
        return in_array($coll, $this->collectionUpdates, true);
    }

    /**
     * Gets the class name for an association (embed or reference) with respect
     * to any discriminator value.
     *
     * @param array $mapping Field mapping for the association
     * @param array $data    Data for the embedded document or reference
     */
    public function getClassNameForAssociation(array $mapping, array $data)
    {
        $discriminatorField = isset($mapping['discriminatorField']) ? $mapping['discriminatorField'] : null;

        if (isset($discriminatorField, $data[$discriminatorField])) {
            $discriminatorValue = $data[$discriminatorField];

            return isset($mapping['discriminatorMap'][$discriminatorValue])
                ? $mapping['discriminatorMap'][$discriminatorValue]
                : $discriminatorValue;
        }

        $class = $this->dm->getClassMetadata($mapping['targetDocument']);

        if (isset($class->discriminatorField, $data[$class->discriminatorField])) {
            $discriminatorValue = $data[$class->discriminatorField];

            return isset($class->discriminatorMap[$discriminatorValue])
                ? $class->discriminatorMap[$discriminatorValue]
                : $discriminatorValue;
        }

        return $mapping['targetDocument'];
    }

    /**
     * INTERNAL:
     * Creates a document. Used for reconstitution of documents during hydration.
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
        if (isset($class->discriminatorField, $data[$class->discriminatorField])) {
            $discriminatorValue = $data[$class->discriminatorField];

            $className = isset($class->discriminatorMap[$discriminatorValue])
                ? $class->discriminatorMap[$discriminatorValue]
                : $discriminatorValue;

            $class = $this->dm->getClassMetadata($className);

            unset($data[$class->discriminatorField]);
        }

        $id = $class->getDatabaseIdentifierValue($data['_id']);
        $serializedId = serialize($id);

        if (isset($this->identityMap[$class->name][$serializedId])) {
            $document = $this->identityMap[$class->name][$serializedId];
            $oid = spl_object_hash($document);
            if ($document instanceof Proxy && ! $document->__isInitialized__) {
                $document->__isInitialized__ = true;
                $overrideLocalValues = true;
                if ($document instanceof NotifyPropertyChanged) {
                    $document->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = ! empty($hints[Query::HINT_REFRESH]);
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
            $this->identityMap[$class->name][$serializedId] = $document;
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
        $hasPreLoadListeners = $this->evm->hasListeners(Events::preLoad);

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
                    if ( ! empty($entryClass->lifecycleCallbacks[Events::preLoad])) {
                        $args = array(&$data);
                        $entryClass->invokeLifecycleCallbacks(Events::preLoad, $entry, $args);
                    }
                    if ($hasPreLoadListeners) {
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
     * @param PersistentCollection $collection The collection to initialize.
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
     * Gets the original data of a document. The original data is the data that was
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
     * Sets a property value of the original data array of a document.
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
     * Gets the identifier of a document.
     *
     * @param object $document
     * @return mixed The identifier value
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
     * TODO: This method assumes that $id is a valid PHP identifier for the
     * document class. If the class expects its database identifier to be a
     * MongoId, and an incompatible $id is registered (e.g. an integer), the
     * document identifiers map will become inconsistent with the identity map.
     * In the future, we may want to round-trip $id through a PHP and database
     * conversion and throw an exception if it's inconsistent.
     *
     * @param object $document The document.
     * @param array $id The identifier values.
     * @param array $data The original document data.
     */
    public function registerManaged($document, $id, array $data)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));

        if ( ! $class->identifier || $id === null) {
            $this->documentIdentifiers[$oid] = $oid;
        } else {
            $this->documentIdentifiers[$oid] = $class->getPHPIdentifierValue($id);
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
     * Notifies this UnitOfWork of a property change in a document.
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
        if ( ! isset($this->scheduledForDirtyCheck[$class->name][$oid])) {
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
     * Gets the currently scheduled document upserts in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledDocumentUpserts()
    {
        return $this->documentUpserts;
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
        } elseif ($obj instanceof PersistentCollection) {
            $obj->initialize();
        }
    }

    private static function objToStr($obj)
    {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj) . '@' . spl_object_hash($obj);
    }
}
