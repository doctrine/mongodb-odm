<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSet;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputationRequest;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputer;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Persisters\CollectionPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Registry\DocumentRegistry;
use Doctrine\ODM\MongoDB\Registry\PersistenceState;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\ODM\MongoDB\Utility\LifecycleEventManager;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use InvalidArgumentException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionProperty;
use SplObjectStorage;
use Throwable;
use UnexpectedValueException;

use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function assert;
use function call_user_func;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;
use function preg_match;
use function serialize;
use function spl_object_id;
use function sprintf;
use function trigger_deprecation;

use const PHP_VERSION_ID;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @phpstan-import-type AssociationFieldMapping from ClassMetadata
 * @phpstan-import-type LegacyChangeSetArray from ChangeSet
 * @phpstan-type Hints array<int, mixed>
 * @phpstan-type CommitOptions array{
 *      fsync?: bool,
 *      safe?: int,
 *      w?: int,
 *      withTransaction?: bool,
 *      writeConcern?: WriteConcern
 * }
 */
final class UnitOfWork implements PropertyChangedListener
{
    /**
     * A document is in MANAGED state when its persistence is managed by a DocumentManager.
     *
     * @deprecated Use {@see PersistenceState::Managed} instead.
     */
    public const STATE_MANAGED = 1;

    /**
     * A document is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by a DocumentManager.
     *
     * @deprecated Use {@see PersistenceState::New} instead.
     */
    public const STATE_NEW = 2;

    /**
     * A detached document is an instance with a persistent identity that is not
     * (or no longer) associated with a DocumentManager (and a UnitOfWork).
     *
     * @deprecated Use {@see PersistenceState::Detached} instead.
     */
    public const STATE_DETACHED = 3;

    /**
     * A removed document instance is an instance with a persistent identity,
     * associated with a DocumentManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     *
     * @deprecated Use {@see PersistenceState::Removed} instead.
     */
    public const STATE_REMOVED = 4;

    /** @internal */
    public const DEPRECATED_WRITE_OPTIONS = ['fsync', 'safe', 'w'];
    private const TRANSACTION_OPTIONS     = [
        'maxCommitTimeMS' => 1,
        'readConcern' => 1,
        'readPreference' => 1,
        'writeConcern' => 1,
    ];

    /**
     * Map of document changes, keyed by the document instance itself.
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var SplObjectStorage<object, ChangeSet>
     */
    private SplObjectStorage $documentChangeSets;

    /**
     * Map of documents that are scheduled for dirty checking at commit time.
     *
     * Documents are grouped by their class name, and then indexed by their SPL
     * object hash. This is only used for documents with a change tracking
     * policy of DEFERRED_EXPLICIT.
     *
     * @var array<class-string, array<int, object>>
     */
    private array $scheduledForSynchronization = [];

    /**
     * A list of all pending document insertions.
     *
     * @var array<int, object>
     */
    private array $scheduledDocumentInsertions = [];

    /**
     * A list of all pending document updates.
     *
     * @var array<int, object>
     */
    private array $scheduledDocumentUpdates = [];

    /**
     * A list of all pending document upserts.
     *
     * @var array<int, object>
     */
    private array $scheduledDocumentUpserts = [];

    /**
     * A list of all pending document deletions.
     *
     * @var array<int, object>
     */
    private array $scheduledDocumentDeletions = [];

    /**
     * All pending collection deletions.
     *
     * @var array<int, PersistentCollectionInterface<array-key, object>>
     */
    private array $scheduledCollectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @var array<int, PersistentCollectionInterface<array-key, object>>
     */
    private array $scheduledCollectionUpdates = [];

    /**
     * A list of documents related to collections scheduled for update or deletion
     *
     * @var array<int, array<int, PersistentCollectionInterface<array-key, object>>>
     */
    private array $hasScheduledCollections = [];

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array<int, array<PersistentCollectionInterface<array-key, object>>>
     */
    private array $visitedCollections = [];

    /**
     * The DocumentManager that "owns" this UnitOfWork instance.
     */
    private DocumentManager $dm;

    /**
     * The DocumentManager's DocumentRegistry, cached here for direct access
     * to the operations that have no public equivalent on DocumentManager.
     */
    private DocumentRegistry $documentRegistry;

    /**
     * The EventManager used for dispatching events.
     */
    private EventManager $evm;

    /**
     * Additional documents that are scheduled for removal.
     *
     * @var array<int, object>
     */
    private array $orphanRemovals = [];

    /**
     * The HydratorFactory used for hydrating array Mongo documents to Doctrine object documents.
     */
    private HydratorFactory $hydratorFactory;

    /**
     * The document persister instances used to persist document instances.
     *
     * @var array<class-string, Persisters\DocumentPersister>
     * @phpstan-ignore missingType.generics
     */
    private array $persisters = [];

    /**
     * The collection persister instance used to persist changes to collections.
     */
    private ?CollectionPersister $collectionPersister = null;

    /**
     * The persistence builder instance used in DocumentPersisters.
     */
    private ?PersistenceBuilder $persistenceBuilder = null;

    private LifecycleEventManager $lifecycleEventManager;

    private ReflectionService $reflectionService;

    private int $commitsInProgress = 0;

    private readonly ChangeSetComputer $changeSetComputer;

    /**
     * Initializes a new UnitOfWork instance, bound to the given DocumentManager.
     */
    public function __construct(DocumentManager $dm, EventManager $evm, HydratorFactory $hydratorFactory)
    {
        $this->dm                    = $dm;
        $this->documentRegistry      = $dm->getDocumentRegistry();
        $this->evm                   = $evm;
        $this->hydratorFactory       = $hydratorFactory;
        $this->lifecycleEventManager = new LifecycleEventManager($dm, $this, $evm);
        $this->reflectionService     = new RuntimeReflectionService();
        $this->changeSetComputer     = new ChangeSetComputer();
        $this->documentChangeSets    = new SplObjectStorage();
    }

    /**
     * Factory for returning new PersistenceBuilder instances used for preparing data into
     * queries for insert persistence.
     *
     * @internal
     */
    public function getPersistenceBuilder(): PersistenceBuilder
    {
        if (! $this->persistenceBuilder) {
            $this->persistenceBuilder = new PersistenceBuilder($this->dm, $this);
        }

        return $this->persistenceBuilder;
    }

    /**
     * Get the document persister instance for the given document name
     *
     * @param class-string<T> $documentName
     *
     * @return Persisters\DocumentPersister<T>
     *
     * @template T of object
     */
    public function getDocumentPersister(string $documentName): Persisters\DocumentPersister
    {
        if (! isset($this->persisters[$documentName])) {
            $class                           = $this->dm->getClassMetadata($documentName);
            $pb                              = $this->getPersistenceBuilder();
            $this->persisters[$documentName] = new Persisters\DocumentPersister($pb, $this->dm, $this, $this->hydratorFactory, $class);
        }

        return $this->persisters[$documentName];
    }

    /**
     * Get the collection persister instance.
     */
    public function getCollectionPersister(): CollectionPersister
    {
        if (! isset($this->collectionPersister)) {
            $pb                        = $this->getPersistenceBuilder();
            $this->collectionPersister = new Persisters\CollectionPersister($this->dm, $pb, $this);
        }

        return $this->collectionPersister;
    }

    /**
     * Set the document persister instance to use for the given document name
     *
     * @internal
     *
     * @param class-string<T> $documentName
     * @phpstan-param Persisters\DocumentPersister<T> $persister
     *
     * @template T of object
     */
    public function setDocumentPersister(string $documentName, Persisters\DocumentPersister $persister): void
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
     * @param array $options Array of options to be used with batchInsert(), update() and remove()
     * @phpstan-param CommitOptions $options
     */
    public function commit(array $options = []): void
    {
        foreach (self::DEPRECATED_WRITE_OPTIONS as $deprecatedOption) {
            if (! array_key_exists($deprecatedOption, $options)) {
                continue;
            }

            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.6',
                'The "%s" commit option is deprecated.',
                $deprecatedOption,
            );
        }

        // Raise preFlush
        $this->evm->dispatchEvent(Events::preFlush, new Event\PreFlushEventArgs($this->dm));

        // Compute changes done since last commit.
        $this->computeChangeSets();

        if (
            ! ($this->scheduledDocumentInsertions ||
            $this->scheduledDocumentUpserts ||
            $this->scheduledDocumentDeletions ||
            $this->scheduledDocumentUpdates ||
            $this->scheduledCollectionUpdates ||
            $this->scheduledCollectionDeletions ||
            $this->orphanRemovals)
        ) {
            return; // Nothing to do.
        }

        if ($this->commitsInProgress > 0) {
            throw MongoDBException::commitInProgress();
        }

        $this->commitsInProgress++;
        try {
            if ($this->orphanRemovals) {
                foreach ($this->orphanRemovals as $removal) {
                    $this->remove($removal);
                }
            }

            $this->evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->dm));

            if ($this->useTransaction($options)) {
                $session = $this->dm->getClient()->startSession();

                $this->lifecycleEventManager->enableTransactionalMode($session);

                $this->withTransaction(
                    $session,
                    function (Session $session) use ($options): void {
                        $this->doCommit(['session' => $session] + $this->stripTransactionOptions($options));
                    },
                    $this->getTransactionOptions($options),
                );
            } else {
                $this->doCommit($options);
            }

            // Raise postFlush
            $this->evm->dispatchEvent(Events::postFlush, new Event\PostFlushEventArgs($this->dm));

            // Clear up
            foreach ($this->visitedCollections as $collections) {
                foreach ($collections as $coll) {
                    $coll->takeSnapshot();
                }
            }

            $this->documentChangeSets = new SplObjectStorage();

            $this->scheduledDocumentInsertions  =
            $this->scheduledDocumentUpserts     =
            $this->scheduledDocumentUpdates     =
            $this->scheduledDocumentDeletions   =
            $this->scheduledCollectionUpdates   =
            $this->scheduledCollectionDeletions =
            $this->visitedCollections           =
            $this->scheduledForSynchronization  =
            $this->orphanRemovals               =
            $this->hasScheduledCollections      = [];
        } finally {
            $this->commitsInProgress--;
            $this->lifecycleEventManager->clearTransactionalState();
        }
    }

    /**
     * Groups a list of scheduled documents by their class.
     *
     * @param array<int, object> $documents
     *
     * @phpstan-return array<class-string, array{0: ClassMetadata<object>, 1: array<int, object>}>
     */
    private function getClassesForCommitAction(array $documents, bool $includeEmbedded = false): array
    {
        if (empty($documents)) {
            return [];
        }

        $divided = [];
        $embeds  = [];
        foreach ($documents as $oid => $d) {
            $className = $d::class;
            if (isset($embeds[$className])) {
                continue;
            }

            if (isset($divided[$className])) {
                $divided[$className][1][$oid] = $d;
                continue;
            }

            $class = $this->dm->getClassMetadata($className);
            if ($class->isEmbeddedDocument && ! $includeEmbedded) {
                $embeds[$className] = true;
                continue;
            }

            if ($class->isView()) {
                continue;
            }

            if (empty($divided[$class->name])) {
                $divided[$class->name] = [$class, [$oid => $d]];
            } else {
                $divided[$class->name][1][$oid] = $d;
            }
        }

        return $divided;
    }

    /**
     * Compute changesets of all documents scheduled for insertion.
     *
     * Embedded documents will not be processed.
     */
    private function computeScheduleInsertsChangeSets(): void
    {
        foreach ($this->scheduledDocumentInsertions as $document) {
            $class = $this->dm->getClassMetadata($document::class);
            if ($class->isEmbeddedDocument || $class->isView()) {
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
    private function computeScheduleUpsertsChangeSets(): void
    {
        foreach ($this->scheduledDocumentUpserts as $document) {
            $class = $this->dm->getClassMetadata($document::class);
            if ($class->isEmbeddedDocument || $class->isView()) {
                continue;
            }

            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Gets the changeset for a document.
     *
     * @return array{property: array{0: mixed, 1: mixed}}
     * @phpstan-return LegacyChangeSetArray
     */
    public function getDocumentChangeSet(object $document): array
    {
        return $this->getChangeSet($document)->toArray();
    }

    /**
     * Gets the changeset for a document as a {@see ChangeSet} value object.
     *
     * Always returns an instance detached from the one (if any) stored
     * internally: mutating the returned instance (e.g. via
     * {@see ChangeSet::recordChange()}) has no effect on the UnitOfWork's own
     * bookkeeping.
     */
    public function getChangeSet(object $document): ChangeSet
    {
        $changeSet = $this->documentChangeSets[$document] ?? null;

        return $changeSet !== null ? clone $changeSet : new ChangeSet($document, []);
    }

    /**
     * Sets the changeset for a document.
     *
     * @internal
     *
     * @param LegacyChangeSetArray $changeset
     */
    public function setDocumentChangeSet(object $document, array $changeset): void
    {
        $originalData = [];
        $newValues    = [];
        foreach ($changeset as $field => [$oldValue, $newValue]) {
            $originalData[$field] = $oldValue;
            $newValues[$field]    = $newValue;
        }

        $this->documentChangeSets[$document] = new ChangeSet($document, $originalData, $newValues);
    }

    /**
     * Get a documents actual data, flattening all the objects to arrays.
     *
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getDocumentActualData(object $document): array
    {
        $class      = $this->dm->getClassMetadata($document::class);
        $actualData = [];
        foreach ($class->propertyAccessors as $name => $refProp) {
            $mapping = $class->fieldMappings[$name];
            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }

            $value = $refProp->getValue($document);
            if (
                (isset($mapping['association']) && $mapping['type'] === ClassMetadata::MANY)
                && $value !== null && ! ($value instanceof PersistentCollectionInterface)
            ) {
                // If $actualData[$name] is not a Collection then use an ArrayCollection.
                if (! $value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // Inject PersistentCollection
                $coll = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create($this->dm, $mapping, $value);
                $coll->setOwner($document, $mapping);
                $coll->setDirty(! $value->isEmpty());
                $class->propertyAccessors[$name]->setValue($document, $coll);
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
     * {@link ManagedObjectState::$originalData}
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
     * {@link scheduledDocumentUpdates}
     * If the document is already fully MANAGED (has been fetched from the database before)
     * and any changes to its properties are detected, then a reference to the document is stored
     * there to mark it for an update.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    public function computeChangeSet(ClassMetadata $class, object $document): void
    {
        if (! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata($document::class);
        }

        // Fire PreFlush lifecycle callbacks
        if (! empty($class->lifecycleCallbacks[Events::preFlush])) {
            $class->invokeLifecycleCallbacks(Events::preFlush, $document, [new Event\PreFlushEventArgs($this->dm)]);
        }

        $this->applyChangeSet($class, $document);
    }

    /**
     * Used to do the common work of computeChangeSet and recomputeSingleDocumentChangeSet:
     * computes the field-level changeset via the ChangeSetComputer, applies it
     * (original-data snapshot, scheduling for update/orphan-removal/collection-deletion,
     * merging onto an already-stored changeset), then walks associations.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    private function applyChangeSet(ClassMetadata $class, object $document, bool $recompute = false): void
    {
        if ($class->isView()) {
            return;
        }

        $objectState = $this->documentRegistry->getOrCreateObjectState($document);

        if ($objectState->originalData !== null && $class->isReadOnly) {
            return;
        }

        $actualData = $this->getDocumentActualData($document);
        $actualData = $this->fixActualDataCollectionOwnership($class, $document, $actualData, $objectState->originalData);

        $request = new ChangeSetComputationRequest(
            $class,
            $document,
            $objectState->originalData,
            $actualData,
            $this->documentChangeSets[$document] ?? null,
            $class->isChangeTrackingNotify(),
            $recompute,
        );

        $result = $this->changeSetComputer->computeChangeSet($request, $this->isCollectionScheduledForDeletion(...));

        if ($result->isNewDocument) {
            // FIXME: this stamps $actualData as the document's "original" (i.e. persisted)
            // snapshot before anything has actually been INSERTed. If the insert never
            // happens (flush fails, the document is later removed from the identity map,
            // etc.), the snapshot is left describing data that was never written to MongoDB,
            // and a later diff against it will silently miss changes made in the meantime.
            $objectState->originalData           = $actualData;
            $this->documentChangeSets[$document] = $result->changeSet;
        } elseif (! $result->changeSet->isEmpty()) {
            // FIXME: same premature snapshot problem as above, but for UPDATEs: this makes
            // $actualData the new baseline as soon as a diff is computed, before the
            // corresponding update is (or possibly ever is) sent to MongoDB.
            // DocumentPersister::update()/executeUpsert() never touch originalData themselves,
            // so this is the only place it gets refreshed for an existing document — if the
            // write later fails (LockException, connection error, the flush being aborted by
            // an event listener) the snapshot is left describing data that was never actually
            // persisted, and a subsequent diff against it will miss the real remaining changes.
            $objectState->originalData = $actualData;
            $this->scheduleForUpdate($document);

            foreach ($result->orphansToRemove as $orphan) {
                $this->scheduleOrphanRemoval($orphan);
            }

            foreach ($result->collectionsToDelete as $collection) {
                $this->scheduleCollectionDeletion($collection);
            }

            $this->documentChangeSets[$document] = $result->changeSet;
        }

        $this->walkAssociationChanges($class, $document, $result->isNewDocument);
    }

    /**
     * Looks for changes in the associations of the document, recursing into
     * new/managed related documents and bubbling a changed child changeset up
     * into the parent's own changeset for the association field.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    private function walkAssociationChanges(ClassMetadata $class, object $document, bool $isNewDocument): void
    {
        // `notSaved` associations exist only in memory and are never persisted, so they
        // can neither carry a change of their own nor need their targets visited here.
        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => empty($assoc['notSaved']),
        );

        foreach ($associationMappings as $mapping) {
            $value = $class->propertyAccessors[$mapping['fieldName']]->getValue($document);

            if ($value === null) {
                continue;
            }

            $this->computeAssociationChanges($document, $mapping, $value);

            if (isset($mapping['reference'])) {
                // A reference's target is a separate, independently persisted document:
                // the changeset just computed for it above is all it needs. Unlike an
                // embed, it never has to be reflected back onto this document's own
                // changeset, since the reference itself (an id) doesn't change.
                continue;
            }

            $this->bubbleUpEmbeddedChildChanges($document, $mapping, $value, $isNewDocument);
        }
    }

    /**
     * An embedded document's changeset doesn't automatically surface on its parent:
     * the object instance stored in the parent's field is unchanged, only the embed's
     * own fields differ. So the first time we find a child (or, for embed-many, any
     * child in the collection) with a non-empty changeset, we synthesize a change for
     * the parent's association field — purely so persisters see that field as needing
     * to be rewritten — and mark the parent for update.
     *
     * @param PersistentCollectionInterface<array-key, object>|object $value the embed's current value: a single document, or its owning collection
     * @phpstan-param T $parentDocument
     * @phpstan-param AssociationFieldMapping $mapping
     *
     * @template T of object
     */
    private function bubbleUpEmbeddedChildChanges(object $parentDocument, array $mapping, $value, bool $isNewParentDocument): void
    {
        $children = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value->unwrap();

        foreach ($children as $child) {
            if (! isset($this->documentChangeSets[$child]) || $this->documentChangeSets[$child]->isEmpty()) {
                continue;
            }

            if (! isset($this->documentChangeSets[$parentDocument])) {
                $originalData                              = $this->documentRegistry->getOrCreateObjectState($parentDocument)->originalData;
                $this->documentChangeSets[$parentDocument] = new ChangeSet($parentDocument, $originalData ?? []);
            }

            if (! $this->documentChangeSets[$parentDocument]->hasChangedField($mapping['fieldName'])) {
                // instance of $value is the same as it was previously otherwise there would be
                // change set already in place
                $this->documentChangeSets[$parentDocument]->recordChange($mapping['fieldName'], $value);
            }

            if (! $isNewParentDocument) {
                $this->scheduleForUpdate($parentDocument);
            }

            return;
        }
    }

    /**
     * Computes all the changes that have been done to documents and collections
     * since the last commit and stores these changes in the _documentChangeSet map
     * temporarily for access by the persisters, until the UoW commit is finished.
     */
    public function computeChangeSets(): void
    {
        $this->computeScheduleInsertsChangeSets();
        $this->computeScheduleUpsertsChangeSets();

        // Compute changes for other MANAGED documents. Change tracking policies take effect here.
        foreach ($this->documentRegistry->getIdentityMap() as $className => $documents) {
            $class = $this->dm->getClassMetadata($className);
            if ($class->isEmbeddedDocument || $class->isView()) {
                /* we do not want to compute changes to embedded documents up front
                 * in case embedded document was replaced and its changeset
                 * would corrupt data. Embedded documents' change set will
                 * be calculated by reachability from owning document.
                 */
                continue;
            }

            // If change tracking is explicit or happens through notification, then only compute
            // changes on document of that type that are explicitly marked for synchronization.
            $documentsToProcess = $class->isChangeTrackingDeferredImplicit()
                ? $documents
                : ($this->scheduledForSynchronization[$className] ?? []);

            foreach ($documentsToProcess as $document) {
                // Ignore uninitialized proxy objects
                if ($this->isUninitializedObject($document)) {
                    continue;
                }

                // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
                $oid = spl_object_id($document);
                if (
                    isset($this->scheduledDocumentInsertions[$oid])
                    || isset($this->scheduledDocumentUpserts[$oid])
                    || isset($this->scheduledDocumentDeletions[$oid])
                    || $this->documentRegistry->getObjectState($document) === null
                ) {
                    continue;
                }

                $this->computeChangeSet($class, $document);
            }
        }
    }

    /**
     * Computes the changes of an association. This does two largely independent
     * things: first, if the association's own value is a dirty owning-side (or
     * embedded) collection, it schedules that collection itself for a write and
     * for any orphan removal its dirtiness implies; second, it walks every
     * document currently in the association (one, for a to-one) looking for new
     * or moved documents to cascade-persist or re-parent, since a document
     * assigned into an association doesn't otherwise get discovered by the
     * UnitOfWork on its own ("persistence by reachability").
     *
     * @param mixed $value The value of the association.
     * @phpstan-param AssociationFieldMapping $assoc
     *
     * @throws InvalidArgumentException
     */
    private function computeAssociationChanges(object $parentDocument, array $assoc, $value): void
    {
        $isNewParentDocument = isset($this->scheduledDocumentInsertions[spl_object_id($parentDocument)]);
        $class               = $this->dm->getClassMetadata($parentDocument::class);

        // Uninitialized proxies/collections have nothing loaded to walk — and forcing
        // them to load here would defeat the point of them being lazy in the first place.
        if ($value instanceof InternalProxy && ! $value->__isInitialized()) {
            return;
        }

        if ($value instanceof GhostObjectInterface && ! $value->isProxyInitialized()) {
            return;
        }

        if ($value instanceof PersistentCollectionInterface) {
            $this->scheduleDirtyOwningCollection($assoc, $value, $isNewParentDocument, $class);
        }

        // Look through the documents, and in any of their associations,
        // for transient (new) documents, recursively. ("Persistence by reachability")
        // Unwrap. Uninitialized collections will simply be empty.
        $unwrappedValue = $assoc['type'] === ClassMetadata::ONE ? [$value] : $value->unwrap();

        $count = 0;
        foreach ($unwrappedValue as $key => $entry) {
            if (! is_object($entry)) {
                throw new InvalidArgumentException(
                    sprintf('Expected object, found "%s" in %s::%s', $entry, $parentDocument::class, $assoc['name']),
                );
            }

            $targetClass = $this->dm->getClassMetadata($entry::class);

            $state = $this->getDocumentState($entry, self::STATE_NEW);

            // Handle "set" strategy for multi-level hierarchy
            $pathKey = ! isset($assoc['strategy']) || CollectionHelper::isList($assoc['strategy']) ? $count : $key;
            $path    = $assoc['type'] === ClassMetadata::MANY ? $assoc['name'] . '.' . $pathKey : $assoc['name'];

            $count++;

            switch ($state) {
                case self::STATE_NEW:
                    // A transient document reached through this association is only ever
                    // allowed here because the mapping opted into cascading persists;
                    // otherwise it's a mistake the caller needs to fix explicitly, since
                    // silently persisting it would be a surprising side effect.
                    if (! $assoc['isCascadePersist']) {
                        throw new InvalidArgumentException('A new document was found through a relationship that was not'
                            . ' configured to cascade persist operations: ' . $this->objToStr($entry) . '.'
                            . ' Explicitly persist the new document or configure cascading persist operations'
                            . ' on the relationship.');
                    }

                    $this->persistNew($targetClass, $entry);
                    $this->documentRegistry->setParentAssociation($entry, $assoc, $parentDocument, $path);
                    $this->computeChangeSet($targetClass, $entry);
                    break;

                case self::STATE_MANAGED:
                    if ($targetClass->isEmbeddedDocument) {
                        // Unlike a reference, an embedded document can only ever belong to one
                        // parent. If this instance is already registered under a different
                        // parent (e.g. the same embed was assigned into a second property, or
                        // moved from one document to another), sharing it would corrupt both
                        // parents' changesets and orphan-removal bookkeeping. So it is cloned
                        // into an independent copy and re-persisted under the new parent instead.
                        $knownParent = $this->documentRegistry->getParentAssociation($entry)?->parent;
                        if ($knownParent && $knownParent !== $parentDocument) {
                            $entry = clone $entry;
                            if ($assoc['type'] === ClassMetadata::ONE) {
                                $class->setFieldValue($parentDocument, $assoc['fieldName'], $entry);
                                $this->documentRegistry->setOriginalDocumentProperty($parentDocument, $assoc['fieldName'], $entry);
                                if (isset($this->documentChangeSets[$parentDocument]) && $this->documentChangeSets[$parentDocument]->hasChangedField($assoc['fieldName'])) {
                                    $this->documentChangeSets[$parentDocument]->recordChange($assoc['fieldName'], $entry);
                                }
                            } else {
                                // must use unwrapped value to not trigger orphan removal
                                $unwrappedValue[$key] = $entry;
                            }

                            $this->persistNew($targetClass, $entry);
                        }

                        $this->documentRegistry->setParentAssociation($entry, $assoc, $parentDocument, $path);
                        $this->computeChangeSet($targetClass, $entry);
                    }

                    break;

                case self::STATE_REMOVED:
                    // Consume the $value as array (it's either an array or an ArrayAccess)
                    // and remove the element from Collection.
                    if ($assoc['type'] === ClassMetadata::MANY) {
                        unset($value[$key]);
                    }

                    break;

                case self::STATE_DETACHED:
                    // Can actually not happen right now as we assume STATE_NEW,
                    // so the exception will be raised from the DBAL layer (constraint violation).
                    throw new InvalidArgumentException('A detached document was found through a '
                        . 'relationship during cascading a persist operation.');

                default:
                    // MANAGED associated documents are already taken into account
                    // during changeset calculation anyway, since they are in the identity map.
            }
        }
    }

    /**
     * A collection is scheduled for its own write/orphan-removal pass only when it
     * is genuinely dirty, still attached to an owner, and on the owning (writable)
     * side of the association — an inverse-side collection is never itself written.
     * Embedded parents that are themselves still unpersisted normally don't need
     * their collections scheduled separately (they'll be written wholesale with
     * the parent), except under a "set" write strategy, which always rewrites the
     * field's whole array and so must be scheduled regardless.
     *
     * @phpstan-param AssociationFieldMapping $assoc
     * @phpstan-param PersistentCollectionInterface<array-key, object> $value
     * @phpstan-param ClassMetadata<object> $class
     */
    private function scheduleDirtyOwningCollection(array $assoc, PersistentCollectionInterface $value, bool $isNewParentDocument, ClassMetadata $class): void
    {
        if (! $value->isDirty() || $value->getOwner() === null || (! $assoc['isOwningSide'] && ! isset($assoc['embedded']))) {
            return;
        }

        $topOrExistingDocument = ! $isNewParentDocument || ! $class->isEmbeddedDocument;
        if ($topOrExistingDocument || CollectionHelper::usesSet($assoc['strategy'])) {
            $this->scheduleCollectionUpdate($value);
        }

        $topmostOwner                                             = $this->getOwningDocument($value->getOwner());
        $this->visitedCollections[spl_object_id($topmostOwner)][] = $value;

        if (empty($assoc['orphanRemoval']) && ! isset($assoc['embedded'])) {
            return;
        }

        $value->initialize();
        foreach ($value->getDeletedDocuments() as $orphan) {
            $this->scheduleOrphanRemoval($orphan);
        }
    }

    /**
     * Computes the changeset of an individual document, independently of the
     * computeChangeSets() routine that is used at the beginning of a UnitOfWork#commit().
     *
     * The passed document must be a managed document. If the document already has a change set
     * because this method is invoked during a commit cycle then the change sets are added.
     * whereby changes detected in this method prevail.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @throws InvalidArgumentException If the passed document is not MANAGED.
     *
     * @template T of object
     */
    public function recomputeSingleDocumentChangeSet(ClassMetadata $class, object $document): void
    {
        // Ignore uninitialized proxy objects
        if ($this->isUninitializedObject($document)) {
            return;
        }

        if ($this->documentRegistry->getObjectState($document)?->state !== PersistenceState::Managed) {
            throw new InvalidArgumentException('Document must be managed.');
        }

        if (! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata($document::class);
        }

        $this->applyChangeSet($class, $document, true);
    }

    /**
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @throws InvalidArgumentException If there is something wrong with document's identifier.
     *
     * @template T of object
     */
    private function persistNew(ClassMetadata $class, object $document): void
    {
        $this->lifecycleEventManager->prePersist($class, $document);
        $oid         = spl_object_id($document);
        $objectState = $this->documentRegistry->getOrCreateObjectState($document);
        $upsert      = false;
        if ($class->identifier) {
            $idValue = $class->getIdentifierValue($document);
            $upsert  = ! $class->isEmbeddedDocument && ! $class->timeSeriesOptions && $idValue !== null;

            if ($class->generatorType === ClassMetadata::GENERATOR_TYPE_NONE && $idValue === null) {
                throw new InvalidArgumentException(sprintf(
                    '%s uses NONE identifier generation strategy but no identifier was provided when persisting.',
                    $document::class,
                ));
            }

            if ($class->getIdentifierMapping()['type'] === Type::ID && $idValue !== null && $class->generatorType === ClassMetadata::GENERATOR_TYPE_AUTO && ! preg_match('#^[0-9a-f]{24}$#', (string) $idValue)) {
                throw new InvalidArgumentException(sprintf(
                    '%s uses AUTO identifier generation strategy but provided identifier is not a valid ObjectId.',
                    $document::class,
                ));
            }

            if ($class->generatorType !== ClassMetadata::GENERATOR_TYPE_NONE && $idValue === null && $class->idGenerator !== null) {
                $idValue = $class->idGenerator->generate($this->dm, $document);
                $idValue = $class->getPHPIdentifierValue($class->getDatabaseIdentifierValue($idValue));
                $class->setIdentifierValue($document, $idValue);
            }

            $objectState->identifier = $idValue;
        } else {
            // this is for embedded documents without identifiers
            $objectState->identifier = $oid;
        }

        $objectState->state = PersistenceState::Managed;

        if ($upsert) {
            $this->scheduleForUpsert($class, $document);
        } else {
            $this->scheduleForInsert($class, $document);
        }
    }

    /**
     * Executes all document insertions for documents of the specified type.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T[] $documents
     * @phpstan-param CommitOptions $options
     *
     * @template T of object
     */
    private function executeInserts(ClassMetadata $class, array $documents, array $options = []): void
    {
        $persister = $this->getDocumentPersister($class->name);

        foreach ($documents as $document) {
            $persister->addInsert($document);
        }

        $persister->executeInserts($options);

        foreach ($documents as $document) {
            $this->lifecycleEventManager->postPersist($class, $document, $options['session'] ?? null);
        }
    }

    /**
     * Executes all document upserts for documents of the specified type.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T[] $documents
     * @phpstan-param CommitOptions $options
     *
     * @template T of object
     */
    private function executeUpserts(ClassMetadata $class, array $documents, array $options = []): void
    {
        $persister = $this->getDocumentPersister($class->name);

        foreach ($documents as $document) {
            $persister->addUpsert($document);
        }

        $persister->executeUpserts($options);

        foreach ($documents as $document) {
            $this->lifecycleEventManager->postPersist($class, $document, $options['session'] ?? null);
        }
    }

    /**
     * Executes all document updates for documents of the specified type.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T[] $documents
     * @phpstan-param CommitOptions $options
     *
     * @template T of object
     */
    private function executeUpdates(ClassMetadata $class, array $documents, array $options = []): void
    {
        if ($class->isReadOnly) {
            return;
        }

        $className = $class->name;
        $persister = $this->getDocumentPersister($className);

        foreach ($documents as $oid => $document) {
            $this->lifecycleEventManager->preUpdate($class, $document, $options['session'] ?? null);

            if (! $this->getChangeSet($document)->isEmpty() || $this->hasScheduledCollections($document)) {
                $persister->update($document, $options);
            }

            $this->lifecycleEventManager->postUpdate($class, $document, $options['session'] ?? null);
        }
    }

    /**
     * Executes all document deletions for documents of the specified type.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T[] $documents
     * @phpstan-param CommitOptions $options
     *
     * @template T of object
     */
    private function executeDeletions(ClassMetadata $class, array $documents, array $options = []): void
    {
        $persister = $this->getDocumentPersister($class->name);

        foreach ($documents as $document) {
            if (! $class->isEmbeddedDocument) {
                $persister->delete($document, $options);
            }

            $this->documentRegistry->stopTracking($class, $document);

            // Clear snapshot information for any referenced PersistentCollection
            // http://www.doctrine-project.org/jira/browse/MODM-95
            foreach ($class->associationMappings as $fieldMapping) {
                if (! isset($fieldMapping['type']) || $fieldMapping['type'] !== ClassMetadata::MANY) {
                    continue;
                }

                $value = $class->propertyAccessors[$fieldMapping['fieldName']]->getValue($document);
                if (! ($value instanceof PersistentCollectionInterface)) {
                    continue;
                }

                $value->clearSnapshot();
            }

            $this->lifecycleEventManager->postRemove($class, $document, $options['session'] ?? null);
        }
    }

    /**
     * Schedules a document for insertion into the database.
     * If the document already has an identifier, it will be added to the
     * identity map.
     *
     * @internal
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @throws InvalidArgumentException
     *
     * @template T of object
     */
    public function scheduleForInsert(ClassMetadata $class, object $document): void
    {
        $oid = spl_object_id($document);

        if (isset($this->scheduledDocumentUpdates[$oid])) {
            throw new InvalidArgumentException('Dirty document can not be scheduled for insertion.');
        }

        if (isset($this->scheduledDocumentDeletions[$oid])) {
            throw new InvalidArgumentException('Removed document can not be scheduled for insertion.');
        }

        if (isset($this->scheduledDocumentInsertions[$oid])) {
            throw new InvalidArgumentException('Document can not be scheduled for insertion twice.');
        }

        $this->scheduledDocumentInsertions[$oid] = $document;

        if ($this->documentRegistry->getObjectState($document)?->identifier === null) {
            return;
        }

        $this->addToIdentityMap($document);
    }

    /**
     * Schedules a document for upsert into the database and adds it to the
     * identity map
     *
     * @internal
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @throws InvalidArgumentException
     *
     * @template T of object
     */
    public function scheduleForUpsert(ClassMetadata $class, object $document): void
    {
        $oid = spl_object_id($document);

        if ($class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Embedded document can not be scheduled for upsert.');
        }

        if (isset($this->scheduledDocumentUpdates[$oid])) {
            throw new InvalidArgumentException('Dirty document can not be scheduled for upsert.');
        }

        if (isset($this->scheduledDocumentDeletions[$oid])) {
            throw new InvalidArgumentException('Removed document can not be scheduled for upsert.');
        }

        if (isset($this->scheduledDocumentUpserts[$oid])) {
            throw new InvalidArgumentException('Document can not be scheduled for upsert twice.');
        }

        $this->scheduledDocumentUpserts[$oid]                                  = $document;
        $this->documentRegistry->getOrCreateObjectState($document)->identifier = $class->getIdentifierValue($document);
        $this->addToIdentityMap($document);
    }

    /**
     * Checks whether a document is scheduled for insertion.
     */
    public function isScheduledForInsert(object $document): bool
    {
        return isset($this->scheduledDocumentInsertions[spl_object_id($document)]);
    }

    /**
     * Checks whether a document is scheduled for upsert.
     */
    public function isScheduledForUpsert(object $document): bool
    {
        return isset($this->scheduledDocumentUpserts[spl_object_id($document)]);
    }

    /**
     * Schedules a document for being updated.
     *
     * @internal
     *
     * @throws InvalidArgumentException
     */
    public function scheduleForUpdate(object $document): void
    {
        $oid = spl_object_id($document);
        if ($this->documentRegistry->getObjectState($document)?->identifier === null) {
            throw new InvalidArgumentException('Document has no identity.');
        }

        if (isset($this->scheduledDocumentDeletions[$oid])) {
            throw new InvalidArgumentException('Document is removed.');
        }

        if (
            isset($this->scheduledDocumentUpdates[$oid])
            || isset($this->scheduledDocumentInsertions[$oid])
            || isset($this->scheduledDocumentUpserts[$oid])
        ) {
            return;
        }

        $this->scheduledDocumentUpdates[$oid] = $document;
    }

    /**
     * Checks whether a document is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty documents are only registered
     * at commit time.
     */
    public function isScheduledForUpdate(object $document): bool
    {
        return isset($this->scheduledDocumentUpdates[spl_object_id($document)]);
    }

    /**
     * Checks whether a document is registered to be checked in the unit of work.
     */
    public function isScheduledForSynchronization(object $document): bool
    {
        $class = $this->dm->getClassMetadata($document::class);

        return isset($this->scheduledForSynchronization[$class->name][spl_object_id($document)]);
    }

    /**
     * Schedules a document for deletion.
     *
     * @internal
     */
    public function scheduleForDelete(object $document, bool $isView = false): void
    {
        $oid   = spl_object_id($document);
        $class = $this->dm->getClassMetadata($document::class);

        if (isset($this->scheduledDocumentInsertions[$oid])) {
            if ($this->documentRegistry->isInIdentityMap($class, $document)) {
                $this->documentRegistry->removeFromIdentityMap($class, $document);
            }

            unset($this->scheduledDocumentInsertions[$oid]);

            return; // document has not been persisted yet, so nothing more to do.
        }

        if (! $this->documentRegistry->isInIdentityMap($class, $document)) {
            return; // ignore
        }

        $this->documentRegistry->removeFromIdentityMap($class, $document);
        $this->documentRegistry->getOrCreateObjectState($document)->state = PersistenceState::Removed;

        if (isset($this->scheduledDocumentUpdates[$oid])) {
            unset($this->scheduledDocumentUpdates[$oid]);
        }

        if (isset($this->scheduledDocumentUpserts[$oid])) {
            unset($this->scheduledDocumentUpserts[$oid]);
        }

        if (isset($this->scheduledDocumentDeletions[$oid])) {
            return;
        }

        if ($isView) {
            return;
        }

        $this->scheduledDocumentDeletions[$oid] = $document;
    }

    /**
     * Checks whether a document is registered as removed/deleted with the unit
     * of work.
     */
    public function isScheduledForDelete(object $document): bool
    {
        return isset($this->scheduledDocumentDeletions[spl_object_id($document)]);
    }

    /**
     * Checks whether a document is scheduled for insertion, update or deletion.
     *
     * @internal
     */
    public function isDocumentScheduled(object $document): bool
    {
        $oid = spl_object_id($document);

        return isset($this->scheduledDocumentInsertions[$oid]) ||
            isset($this->scheduledDocumentUpserts[$oid]) ||
            isset($this->scheduledDocumentUpdates[$oid]) ||
            isset($this->scheduledDocumentDeletions[$oid]);
    }

    /**
     * Registers a document in the identity map.
     *
     * Note that documents in a hierarchy are registered with the class name of
     * the root document. Identifiers are serialized before being used as array
     * keys to allow differentiation of equal, but not identical, values.
     *
     * @internal
     */
    public function addToIdentityMap(object $document): bool
    {
        $class = $this->dm->getClassMetadata($document::class);

        if (! $this->documentRegistry->addToIdentityMap($class, $document)) {
            return false;
        }

        $this->registerPropertyChangedListener($document);

        return true;
    }

    /**
     * Registers this UnitOfWork as a listener for NOTIFY-tracked property
     * changes on a document that has just become managed.
     */
    private function registerPropertyChangedListener(object $document): void
    {
        if ($document instanceof NotifyPropertyChanged && ! $this->isUninitializedObject($document)) {
            $document->addPropertyChangedListener($this);
        }
    }

    /**
     * Gets the state of a document with regard to the current unit of work.
     *
     * @param int|null $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                         This parameter can be set to improve performance of document state detection
     *                         by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                         is either known or does not matter for the caller of the method.
     */
    public function getDocumentState(object $document, ?int $assume = null): int
    {
        $objectState = $this->documentRegistry->getObjectState($document);

        if ($objectState !== null) {
            return $this->persistenceStateToInt($objectState->state);
        }

        $class = $this->dm->getClassMetadata($document::class);

        if ($class->isEmbeddedDocument) {
            return self::STATE_NEW;
        }

        if ($assume !== null) {
            return $assume;
        }

        /* State can only be NEW or DETACHED, because MANAGED/REMOVED states are
         * known. Note that you cannot remember the NEW or DETACHED state on
         * the DocumentManager's object state since it does not hold references
         * to such objects and the object hash can be reused. More generally,
         * because the state may "change" between NEW/DETACHED without the
         * DocumentManager being aware of it.
         */
        $id = $class->getIdentifierObject($document);

        if ($id === null) {
            return self::STATE_NEW;
        }

        // Check for a version field, if available, to avoid a DB lookup.
        if ($class->isVersioned && $class->versionField !== null) {
            return $class->getFieldValue($document, $class->versionField)
                ? self::STATE_DETACHED
                : self::STATE_NEW;
        }

        // Last try before DB lookup: check the identity map.
        if ($this->documentRegistry->tryGetById($id, $class)) {
            return self::STATE_DETACHED;
        }

        // DB lookup
        if ($this->getDocumentPersister($class->name)->exists($document)) {
            return self::STATE_DETACHED;
        }

        return self::STATE_NEW;
    }

    /** @return self::STATE_* */
    private function persistenceStateToInt(PersistenceState $state): int
    {
        return match ($state) {
            PersistenceState::Managed => self::STATE_MANAGED,
            PersistenceState::New => self::STATE_NEW,
            PersistenceState::Detached => self::STATE_DETACHED,
            PersistenceState::Removed => self::STATE_REMOVED,
        };
    }

    /**
     * Tries to get a document by its identifier hash. If no document is found
     * for the given hash, FALSE is returned.
     *
     * @deprecated Use {@see DocumentRegistry::tryGetById()} instead.
     *
     * @param mixed $id Document identifier
     * @phpstan-param ClassMetadata<T> $class
     *
     * @return mixed The found document or FALSE.
     * @phpstan-return T|false
     *
     * @throws InvalidArgumentException If the class does not have an identifier.
     *
     * @template T of object
     */
    public function tryGetById($id, ClassMetadata $class)
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.18',
            '%s is deprecated, call %s::tryGetById() instead.',
            __METHOD__,
            DocumentRegistry::class,
        );

        if (! $class->identifier) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not have an identifier', $class->name));
        }

        return $this->documentRegistry->tryGetById($id, $class);
    }

    /**
     * Schedules a document for dirty-checking at commit-time.
     *
     * @internal
     */
    public function scheduleForSynchronization(object $document): void
    {
        $class                                                                     = $this->dm->getClassMetadata($document::class);
        $this->scheduledForSynchronization[$class->name][spl_object_id($document)] = $document;
    }

    /**
     * Persists a document as part of the current unit of work.
     *
     * @internal
     *
     * @throws MongoDBException If trying to persist MappedSuperclass.
     * @throws InvalidArgumentException If there is something wrong with document's identifier.
     */
    public function persist(object $document): void
    {
        $class = $this->dm->getClassMetadata($document::class);
        if ($class->isMappedSuperclass || $class->isQueryResultDocument) {
            throw MongoDBException::cannotPersistMappedSuperclass($class->name);
        }

        $visited = [];
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
     * @param array<int, object> $visited
     *
     * @throws InvalidArgumentException
     * @throws MongoDBException
     */
    private function doPersist(object $document, array &$visited): void
    {
        $oid = spl_object_id($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // Mark visited

        $class = $this->dm->getClassMetadata($document::class);

        $documentState = $this->getDocumentState($document, self::STATE_NEW);
        switch ($documentState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit() && ! $class->isView()) {
                    $this->scheduleForSynchronization($document);
                }

                break;
            case self::STATE_NEW:
                if ($class->isFile) {
                    throw MongoDBException::cannotPersistGridFSFile($class->name);
                }

                if ($class->isView()) {
                    return;
                }

                $this->persistNew($class, $document);
                break;

            case self::STATE_REMOVED:
                // Document becomes managed again
                unset($this->scheduledDocumentDeletions[$oid]);

                $this->persistNew($class, $document);
                break;

            case self::STATE_DETACHED:
                throw new InvalidArgumentException(
                    'Behavior of persist() for a detached document is not yet defined.',
                );

            default:
                throw MongoDBException::invalidDocumentState($documentState);
        }

        $this->cascadePersist($document, $visited);
    }

    /**
     * Deletes a document as part of the current unit of work.
     *
     * @internal
     */
    public function remove(object $document): void
    {
        $visited = [];
        $this->doRemove($document, $visited);
    }

    /**
     * Deletes a document as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * @param array<int, object> $visited
     *
     * @throws MongoDBException
     */
    private function doRemove(object $document, array &$visited): void
    {
        $oid = spl_object_id($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        /* Cascade first, because scheduleForDelete() removes the entity from
         * the identity map, which can cause problems when a lazy Proxy has to
         * be initialized for the cascade operation.
         */
        $this->cascadeRemove($document, $visited);

        $class         = $this->dm->getClassMetadata($document::class);
        $documentState = $this->getDocumentState($document);
        switch ($documentState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
                $this->lifecycleEventManager->preRemove($class, $document);
                $this->scheduleForDelete($document, $class->isView());
                break;
            case self::STATE_DETACHED:
                throw MongoDBException::detachedDocumentCannotBeRemoved();

            default:
                throw MongoDBException::invalidDocumentState($documentState);
        }
    }

    /**
     * Merges the state of the given detached document into this UnitOfWork.
     *
     * @internal
     */
    public function merge(object $document): object
    {
        $visited = [];

        return $this->doMerge($document, $visited);
    }

    /**
     * Executes a merge operation on a document.
     *
     * @param array<int, object> $visited
     * @phpstan-param AssociationFieldMapping|null $assoc
     *
     * @throws InvalidArgumentException If the entity instance is NEW.
     * @throws LockException If the document uses optimistic locking through a
     *                       version attribute and the version check against the
     *                       managed copy fails.
     */
    private function doMerge(object $document, array &$visited, ?object $prevManagedCopy = null, ?array $assoc = null): object
    {
        $oid = spl_object_id($document);

        if (isset($visited[$oid])) {
            return $visited[$oid]; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata($document::class);

        /* First we assume DETACHED, although it can still be NEW but we can
         * avoid an extra DB round trip this way. If it is not MANAGED but has
         * an identity, we need to fetch it from the DB anyway in order to
         * merge. MANAGED documents are ignored by the merge operation.
         */
        $managedCopy = $document;

        if ($this->getDocumentState($document, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            if ($this->isUninitializedObject($document)) {
                $this->initializeObject($document);
            }

            $identifier = $class->getIdentifier();
            // We always have one element in the identifier array but it might be null
            $id          = $identifier[0] !== null ? $class->getIdentifierObject($document) : null;
            $managedCopy = null;

            // Try to fetch document from the database
            if (! $class->isEmbeddedDocument && $id !== null) {
                $managedCopy = $this->dm->find($class->name, $id);

                // Managed copy may be removed in which case we can't merge
                if ($managedCopy && $this->getDocumentState($managedCopy) === self::STATE_REMOVED) {
                    throw new InvalidArgumentException('Removed entity detected during merge. Cannot merge with a removed entity.');
                }

                if ($managedCopy && $this->isUninitializedObject($managedCopy)) {
                    $this->initializeObject($managedCopy);
                }
            }

            if ($managedCopy === null) {
                // Create a new managed instance
                $managedCopy = $class->newInstance();
                if ($id !== null) {
                    $class->setIdentifierValue($managedCopy, $id);
                }

                $this->persistNew($class, $managedCopy);
            }

            if ($class->isVersioned) {
                $managedCopyVersion = $class->propertyAccessors[$class->versionField]->getValue($managedCopy);
                $documentVersion    = $class->propertyAccessors[$class->versionField]->getValue($document);

                // Throw exception if versions don't match
                if ($managedCopyVersion !== $documentVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $documentVersion, $managedCopyVersion);
                }
            }

            // Merge state of $document into existing (managed) document
            foreach ($class->reflClass->getProperties() as $nativeReflection) {
                if ($nativeReflection->isStatic()) {
                    continue;
                }

                $name = $nativeReflection->name;
                $prop = $this->reflectionService->getAccessibleProperty($class->name, $name);
                assert($prop instanceof ReflectionProperty);

                if (! $prop->isInitialized($document)) {
                    continue;
                }

                if (! isset($class->associationMappings[$name])) {
                    if (! $class->isIdentifier($name)) {
                        $prop->setValue($managedCopy, $prop->getValue($document));
                    }
                } else {
                    $assoc2 = $class->associationMappings[$name];

                    if ($assoc2['type'] === ClassMetadata::ONE) {
                        $other = $prop->getValue($document);

                        if ($other === null) {
                            $prop->setValue($managedCopy, null);
                        } elseif ($this->isUninitializedObject($other)) {
                            // Do not merge fields marked lazy that have not been fetched
                            continue;
                        } elseif (! $assoc2['isCascadeMerge']) {
                            if ($this->getDocumentState($other) === self::STATE_DETACHED) {
                                $targetDocument = $assoc2['targetDocument'] ?? $other::class;
                                $targetClass    = $this->dm->getClassMetadata($targetDocument);
                                $relatedId      = $targetClass->getIdentifierObject($other);

                                $current = $prop->getValue($managedCopy);
                                if ($current !== null) {
                                    $this->documentRegistry->removeFromIdentityMap($this->dm->getClassMetadata($current::class), $current);
                                }

                                if ($targetClass->subClasses) {
                                    $other = $this->dm->find($targetClass->name, $relatedId);
                                } else {
                                    $other = $this
                                        ->dm
                                        ->getProxyFactory()
                                        ->getProxy($targetClass, $relatedId);
                                    $this->registerManaged($other, $relatedId, [$targetClass->identifier => $relatedId]);
                                }
                            }

                            $prop->setValue($managedCopy, $other);
                        }
                    } else {
                        $mergeCol = $prop->getValue($document);

                        if ($mergeCol instanceof PersistentCollectionInterface && ! $mergeCol->isInitialized() && ! $assoc2['isCascadeMerge']) {
                            /* Do not merge fields marked lazy that have not
                             * been fetched. Keep the lazy persistent collection
                             * of the managed copy.
                             */
                            continue;
                        }

                        $managedCol = $prop->getValue($managedCopy);

                        if (! $managedCol) {
                            $managedCol = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create($this->dm, $assoc2, null);
                            $managedCol->setOwner($managedCopy, $assoc2);
                            $prop->setValue($managedCopy, $managedCol);
                            $this->documentRegistry->getOrCreateObjectState($document)->originalData[$name] = $managedCol;
                        }

                        /* Note: do not process association's target documents.
                         * They will be handled during the cascade. Initialize
                         * and, if necessary, clear $managedCol for now.
                         */
                        if ($assoc2['isCascadeMerge']) {
                            $managedCol->initialize();

                            // If $managedCol differs from the merged collection, clear and set dirty
                            if (! $managedCol->isEmpty() && $managedCol !== $mergeCol) {
                                $managedCol->unwrap()->clear();
                                $managedCol->setDirty(true);

                                if ($assoc2['isOwningSide'] && $class->isChangeTrackingNotify()) {
                                    $this->scheduleForSynchronization($managedCopy);
                                }
                            }
                        }
                    }
                }

                if (! $class->isChangeTrackingNotify()) {
                    continue;
                }

                // Just treat all properties as changed, there is no other choice.
                $this->propertyChanged($managedCopy, $name, null, $prop->getValue($managedCopy));
            }

            if ($class->isChangeTrackingDeferredExplicit()) {
                $this->scheduleForSynchronization($document);
            }
        }

        if ($prevManagedCopy !== null) {
            $assocField = $assoc['fieldName'];
            $prevClass  = $this->dm->getClassMetadata($prevManagedCopy::class);

            if ($assoc['type'] === ClassMetadata::ONE) {
                $prevClass->propertyAccessors[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->propertyAccessors[$assocField]->getValue($prevManagedCopy)->add($managedCopy);

                if ($assoc['type'] === ClassMetadata::MANY && isset($assoc['mappedBy'])) {
                    $class->propertyAccessors[$assoc['mappedBy']]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        // Mark the managed copy visited as well
        $visited[spl_object_id($managedCopy)] = $managedCopy;

        $this->cascadeMerge($document, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * Detaches a document from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @internal
     */
    public function detach(object $document): void
    {
        $visited = [];
        $this->doDetach($document, $visited);
    }

    /**
     * Executes a detach operation on the given document.
     *
     * @param array<int, object> $visited
     */
    private function doDetach(object $document, array &$visited): void
    {
        $oid = spl_object_id($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        switch ($this->getDocumentState($document, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->documentRegistry->stopTracking($this->dm->getClassMetadata($document::class), $document);
                unset(
                    $this->scheduledDocumentInsertions[$oid],
                    $this->scheduledDocumentUpdates[$oid],
                    $this->scheduledDocumentDeletions[$oid],
                    $this->scheduledDocumentUpserts[$oid],
                    $this->hasScheduledCollections[$oid],
                );
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
     * @internal
     *
     * @throws InvalidArgumentException If the document is not MANAGED.
     */
    public function refresh(object $document): void
    {
        $visited = [];
        $this->doRefresh($document, $visited);
    }

    /**
     * Executes a refresh operation on a document.
     *
     * @param array<int, object> $visited
     *
     * @throws InvalidArgumentException If the document is not MANAGED.
     */
    private function doRefresh(object $document, array &$visited): void
    {
        $oid = spl_object_id($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata($document::class);

        if (! $class->isEmbeddedDocument) {
            if ($this->getDocumentState($document) !== self::STATE_MANAGED) {
                throw new InvalidArgumentException('Document is not MANAGED.');
            }

            $this->getDocumentPersister($class->name)->refresh($document);
        }

        $this->cascadeRefresh($document, $visited);
    }

    /**
     * Cascades a refresh operation to associated documents.
     *
     * @param array<int, object> $visited
     */
    private function cascadeRefresh(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadeRefresh'],
        );

        foreach ($associationMappings as $mapping) {
            $relatedDocuments = $class->propertyAccessors[$mapping['fieldName']]->getValue($document);
            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments instanceof PersistentCollectionInterface) {
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

    /**
     * Cascades a detach operation to associated documents.
     *
     * @param array<int, object> $visited
     */
    private function cascadeDetach(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);
        foreach ($class->fieldMappings as $mapping) {
            if (! $mapping['isCascadeDetach']) {
                continue;
            }

            $relatedDocuments = $class->propertyAccessors[$mapping['fieldName']]->getValue($document);
            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments instanceof PersistentCollectionInterface) {
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

    /**
     * Cascades a merge operation to associated documents.
     *
     * @param array<int, object> $visited
     */
    private function cascadeMerge(object $document, object $managedCopy, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadeMerge'],
        );

        foreach ($associationMappings as $assoc) {
            $relatedDocuments = $class->propertyAccessors[$assoc['fieldName']]->getValue($document);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments === $class->propertyAccessors[$assoc['fieldName']]->getValue($managedCopy)) {
                    // Collections are the same, so there is nothing to do
                    continue;
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
     * @param array<int, object> $visited
     */
    private function cascadePersist(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadePersist'],
        );

        foreach ($associationMappings as $fieldName => $mapping) {
            $relatedDocuments = $class->propertyAccessors[$fieldName]->getValue($document);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments instanceof PersistentCollectionInterface) {
                    if ($relatedDocuments->getOwner() !== $document) {
                        $relatedDocuments = $this->fixPersistentCollectionOwnership($relatedDocuments, $document, $class, $mapping['fieldName']);
                    }

                    // Unwrap so that foreach() does not initialize
                    $relatedDocuments = $relatedDocuments->unwrap();
                }

                $count = 0;
                foreach ($relatedDocuments as $relatedKey => $relatedDocument) {
                    if (! empty($mapping['embedded'])) {
                        $knownParent = $this->documentRegistry->getParentAssociation($relatedDocument)?->parent;
                        if ($knownParent && $knownParent !== $document) {
                            $relatedDocument               = clone $relatedDocument;
                            $relatedDocuments[$relatedKey] = $relatedDocument;
                        }

                        $pathKey = CollectionHelper::isList($mapping['strategy']) ? $count++ : $relatedKey;
                        $this->documentRegistry->setParentAssociation($relatedDocument, $mapping, $document, $mapping['fieldName'] . '.' . $pathKey);
                    }

                    $this->doPersist($relatedDocument, $visited);
                }
            } elseif ($relatedDocuments !== null) {
                if (! empty($mapping['embedded'])) {
                    $knownParent = $this->documentRegistry->getParentAssociation($relatedDocuments)?->parent;
                    if ($knownParent && $knownParent !== $document) {
                        $relatedDocuments = clone $relatedDocuments;
                        $class->setFieldValue($document, $mapping['fieldName'], $relatedDocuments);
                    }

                    $this->documentRegistry->setParentAssociation($relatedDocuments, $mapping, $document, $mapping['fieldName']);
                }

                $this->doPersist($relatedDocuments, $visited);
            }
        }
    }

    /**
     * Cascades the delete operation to associated documents.
     *
     * @param array<int, object> $visited
     */
    private function cascadeRemove(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);
        foreach ($class->fieldMappings as $mapping) {
            if (! $mapping['isCascadeRemove'] && ( ! isset($mapping['orphanRemoval']) || ! $mapping['orphanRemoval'])) {
                continue;
            }

            $this->initializeObject($document);

            $relatedDocuments = $class->propertyAccessors[$mapping['fieldName']]->getValue($document);
            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                // If its a PersistentCollection initialization is intended! No unwrap!
                foreach ($relatedDocuments as $relatedDocument) {
                    $this->doRemove($relatedDocument, $visited);
                }
            } elseif ($relatedDocuments !== null) {
                $this->doRemove($relatedDocuments, $visited);
            }
        }
    }

    /**
     * Acquire a lock on the given document.
     *
     * @internal
     *
     * @throws LockException
     * @throws InvalidArgumentException
     */
    public function lock(object $document, int $lockMode, ?int $lockVersion = null): void
    {
        if ($this->getDocumentState($document) !== self::STATE_MANAGED) {
            throw new InvalidArgumentException('Document is not MANAGED.');
        }

        $documentName = $document::class;
        $class        = $this->dm->getClassMetadata($documentName);

        if ($lockMode === LockMode::OPTIMISTIC) {
            if (! $class->isVersioned) {
                throw LockException::notVersioned($documentName);
            }

            if ($lockVersion !== null) {
                $documentVersion = $class->propertyAccessors[$class->versionField]->getValue($document);
                if ($documentVersion !== $lockVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $lockVersion, $documentVersion);
                }
            }
        } elseif (in_array($lockMode, [LockMode::PESSIMISTIC_READ, LockMode::PESSIMISTIC_WRITE])) {
            $this->getDocumentPersister($class->name)->lock($document, $lockMode);
        }
    }

    /**
     * Releases a lock on the given document.
     *
     * @internal
     *
     * @throws InvalidArgumentException
     */
    public function unlock(object $document): void
    {
        if ($this->getDocumentState($document) !== self::STATE_MANAGED) {
            throw new InvalidArgumentException('Document is not MANAGED.');
        }

        $documentName = $document::class;
        $this->getDocumentPersister($documentName)->unlock($document);
    }

    /**
     * Clears the UnitOfWork.
     *
     * @internal
     */
    public function clear(?string $documentName = null): void
    {
        if ($documentName === null) {
            $this->documentChangeSets = new SplObjectStorage();

            $this->scheduledForSynchronization  =
            $this->scheduledDocumentInsertions  =
            $this->scheduledDocumentUpserts     =
            $this->scheduledDocumentUpdates     =
            $this->scheduledDocumentDeletions   =
            $this->scheduledCollectionUpdates   =
            $this->scheduledCollectionDeletions =
            $this->orphanRemovals               =
            $this->hasScheduledCollections      = [];

            $this->documentRegistry->clear();

            $event = new Event\OnClearEventArgs($this->dm);
        } else {
            $visited = [];
            foreach ($this->documentRegistry->getIdentityMap() as $className => $documents) {
                if ($className !== $documentName) {
                    continue;
                }

                foreach ($documents as $document) {
                    $this->doDetach($document, $visited);
                }
            }

            $event = new Event\OnClearEventArgs($this->dm, $documentName);
        }

        $this->evm->dispatchEvent(Events::onClear, $event);
    }

    /**
     * Schedules an embedded document for removal. The remove() operation will be
     * invoked on that document at the beginning of the next commit of this
     * UnitOfWork.
     *
     * @internal
     */
    public function scheduleOrphanRemoval(object $document): void
    {
        $this->orphanRemovals[spl_object_id($document)] = $document;
    }

    /**
     * Unschedules an embedded or referenced object for removal.
     *
     * @internal
     */
    public function unscheduleOrphanRemoval(object $document): void
    {
        $oid = spl_object_id($document);
        unset($this->orphanRemovals[$oid]);
    }

    /**
     * Applies {@see self::fixPersistentCollectionOwnership()} to every field of
     * $actualData up front, so ChangeSetComputer never has to (it has no
     * DocumentRegistry/scheduling access to do so itself). A document with no
     * original-data snapshot yet (i.e. new) has every field fixed, matching
     * the INSERT changeset's own "every field is new" treatment; an
     * already-managed document is limited to the fields that would actually
     * reach a to-many association's ownership check during diffing: not a
     * notSaved/GridFS-excluded field, and not the inverse side of a reference
     * (which carries no data of its own).
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     * @phpstan-param array<string, mixed> $actualData
     * @phpstan-param array<string, mixed>|null $originalData
     *
     * @phpstan-return array<string, mixed>
     *
     * @template T of object
     */
    private function fixActualDataCollectionOwnership(ClassMetadata $class, object $document, array $actualData, array|null $originalData): array
    {
        foreach ($actualData as $propName => $actualValue) {
            if (! $actualValue instanceof PersistentCollectionInterface || $actualValue->getOwner() === $document) {
                continue;
            }

            if ($originalData !== null && ! $this->isCollectionOwnershipFixApplicable($class, $propName)) {
                continue;
            }

            $actualData[$propName] = $this->fixPersistentCollectionOwnership($actualValue, $document, $class, $propName);
        }

        return $actualData;
    }

    /** @phpstan-param ClassMetadata<object> $class */
    private function isCollectionOwnershipFixApplicable(ClassMetadata $class, string $propName): bool
    {
        if (($class->fieldMappings[$propName]['notSaved'] ?? false) === true) {
            return false;
        }

        if ($class->isFile) {
            return false;
        }

        return ! (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']);
    }

    /**
     * Fixes PersistentCollection state if it wasn't used exactly as we had in mind:
     *  1) sets owner if it was cloned
     *  2) clones collection, sets owner, updates document's property and, if necessary, updates originalData
     *  3) NOP if state is OK
     * Returned collection should be used from now on (only important with 2nd point)
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     * @phpstan-param T $document
     * @phpstan-param ClassMetadata<T> $class
     *
     * @phpstan-return PersistentCollectionInterface<array-key, object>
     *
     * @template T of object
     */
    private function fixPersistentCollectionOwnership(PersistentCollectionInterface $coll, object $document, ClassMetadata $class, string $propName): PersistentCollectionInterface
    {
        $owner = $coll->getOwner();
        if ($owner === null) { // cloned
            $coll->setOwner($document, $class->fieldMappings[$propName]);
        } elseif ($owner !== $document) { // no clone, we have to fix
            $this->initializeObject($coll); // we have to do this otherwise the cols share state
            $newValue = clone $coll;
            $newValue->setOwner($document, $class->fieldMappings[$propName]);
            $class->propertyAccessors[$propName]->setValue($document, $newValue);
            if ($this->isScheduledForUpdate($document)) {
                // @todo following line should be superfluous once collections are stored in change sets
                $this->documentRegistry->setOriginalDocumentProperty($document, $propName, $newValue);
            }

            return $newValue;
        }

        return $coll;
    }

    /**
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function scheduleCollectionDeletion(PersistentCollectionInterface $coll): void
    {
        $oid = spl_object_id($coll);
        unset($this->scheduledCollectionUpdates[$oid]);
        if (isset($this->scheduledCollectionDeletions[$oid])) {
            return;
        }

        $this->scheduledCollectionDeletions[$oid] = $coll;
        $this->scheduleCollectionOwner($coll);
    }

    /**
     * Checks whether a PersistentCollection is scheduled for deletion.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function isCollectionScheduledForDeletion(PersistentCollectionInterface $coll): bool
    {
        return isset($this->scheduledCollectionDeletions[spl_object_id($coll)]);
    }

    /**
     * Unschedules a collection from being deleted when this UnitOfWork commits.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function unscheduleCollectionDeletion(PersistentCollectionInterface $coll): void
    {
        if ($coll->getOwner() === null) {
            return;
        }

        $oid = spl_object_id($coll);
        if (! isset($this->scheduledCollectionDeletions[$oid])) {
            return;
        }

        $topmostOwner = $this->getOwningDocument($coll->getOwner());
        unset($this->scheduledCollectionDeletions[$oid]);
        unset($this->hasScheduledCollections[spl_object_id($topmostOwner)][$oid]);
    }

    /**
     * Schedules a collection for update when this UnitOfWork commits.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function scheduleCollectionUpdate(PersistentCollectionInterface $coll): void
    {
        $mapping = $coll->getMapping();
        if (CollectionHelper::usesSet($mapping['strategy'])) {
            /* There is no need to $unset collection if it will be $set later
             * This is NOP if collection is not scheduled for deletion
             */
            $this->unscheduleCollectionDeletion($coll);
        }

        $oid = spl_object_id($coll);
        if (isset($this->scheduledCollectionUpdates[$oid])) {
            return;
        }

        $this->scheduledCollectionUpdates[$oid] = $coll;
        $this->scheduleCollectionOwner($coll);
    }

    /**
     * Unschedules a collection from being updated when this UnitOfWork commits.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function unscheduleCollectionUpdate(PersistentCollectionInterface $coll): void
    {
        if ($coll->getOwner() === null) {
            return;
        }

        $oid = spl_object_id($coll);
        if (! isset($this->scheduledCollectionUpdates[$oid])) {
            return;
        }

        $topmostOwner = $this->getOwningDocument($coll->getOwner());
        unset($this->scheduledCollectionUpdates[$oid]);
        unset($this->hasScheduledCollections[spl_object_id($topmostOwner)][$oid]);
    }

    /**
     * Checks whether a PersistentCollection is scheduled for update.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function isCollectionScheduledForUpdate(PersistentCollectionInterface $coll): bool
    {
        return isset($this->scheduledCollectionUpdates[spl_object_id($coll)]);
    }

    /**
     * Gets PersistentCollections that have been visited during computing change
     * set of $document
     *
     * @internal
     *
     * @return PersistentCollectionInterface[]
     * @phpstan-return array<PersistentCollectionInterface<array-key, object>>
     */
    public function getVisitedCollections(object $document): array
    {
        $oid = spl_object_id($document);

        return $this->visitedCollections[$oid] ?? [];
    }

    /**
     * Gets PersistentCollections that are scheduled to update and related to $document
     *
     * @internal
     *
     * @return array<int, PersistentCollectionInterface<array-key, object>>
     */
    public function getScheduledCollections(object $document): array
    {
        $oid = spl_object_id($document);

        return $this->hasScheduledCollections[$oid] ?? [];
    }

    /**
     * Checks whether the document is related to a PersistentCollection
     * scheduled for update or deletion.
     *
     * @internal
     */
    public function hasScheduledCollections(object $document): bool
    {
        return isset($this->hasScheduledCollections[spl_object_id($document)]);
    }

    /**
     * Marks the PersistentCollection's top-level owner as having a relation to
     * a collection scheduled for update or deletion.
     *
     * If the owner is not scheduled for any lifecycle action, it will be
     * scheduled for update to ensure that versioning takes place if necessary.
     *
     * If the collection is nested within atomic collection, it is immediately
     * unscheduled and atomic one is scheduled for update instead. This makes
     * calculating update data way easier.
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    private function scheduleCollectionOwner(PersistentCollectionInterface $coll): void
    {
        if ($coll->getOwner() === null) {
            return;
        }

        $document                                                                      = $this->getOwningDocument($coll->getOwner());
        $this->hasScheduledCollections[spl_object_id($document)][spl_object_id($coll)] = $coll;

        if ($document !== $coll->getOwner()) {
            $parent  = $coll->getOwner();
            $mapping = [];
            while (($parentAssoc = $this->documentRegistry->getParentAssociation($parent)) !== null) {
                $mapping = $parentAssoc->mapping;
                $parent  = $parentAssoc->parent;
            }

            if (CollectionHelper::isAtomic($mapping['strategy'])) {
                $class            = $this->dm->getClassMetadata($document::class);
                $atomicCollection = $class->getFieldValue($document, $mapping['fieldName']);
                $this->scheduleCollectionUpdate($atomicCollection);
                $this->unscheduleCollectionDeletion($coll);
                $this->unscheduleCollectionUpdate($coll);
            }
        }

        if ($this->isDocumentScheduled($document)) {
            return;
        }

        $this->scheduleForUpdate($document);
    }

    /**
     * Get the top-most owning document of a given document
     *
     * If a top-level document is provided, that same document will be returned.
     * For an embedded document, we will walk through parent associations until
     * we find a top-level document.
     *
     * @throws UnexpectedValueException When a top-level document could not be found.
     */
    public function getOwningDocument(object $document): object
    {
        $class = $this->dm->getClassMetadata($document::class);
        while ($class->isEmbeddedDocument) {
            $parentAssociation = $this->documentRegistry->getParentAssociation($document);

            if (! $parentAssociation) {
                throw new UnexpectedValueException('Could not determine parent association for ' . $document::class);
            }

            $parentDocument = $parentAssociation->parent;
            if (! $parentDocument) {
                throw new UnexpectedValueException('Could not determine parent association for ' . $document::class);
            }

            $document = $parentDocument;
            $class    = $this->dm->getClassMetadata($document::class);
        }

        return $document;
    }

    /**
     * Creates a document. Used for reconstitution of documents during hydration.
     *
     * @param class-string<T>      $className
     * @param array<string, mixed> $data
     * @param T|null               $document
     * @phpstan-param Hints                $hints
     *
     * @return T
     *
     * @template T of object
     */
    public function getOrCreateDocument(string $className, array $data, array &$hints = [], ?object $document = null): object
    {
        $class = $this->dm->getClassMetadata($className);

        // @TODO figure out how to remove this
        $discriminatorValue = null;
        if (isset($class->discriminatorField, $data[$class->discriminatorField])) {
            $discriminatorValue = $data[$class->discriminatorField];
        } elseif (isset($class->defaultDiscriminatorValue)) {
            $discriminatorValue = $class->defaultDiscriminatorValue;
        }

        if ($discriminatorValue !== null) {
            /** @var class-string<T> $className */
            $className =  $class->discriminatorMap[$discriminatorValue] ?? $discriminatorValue;

            $class = $this->dm->getClassMetadata($className);

            unset($data[$class->discriminatorField]);
        }

        if (! empty($hints[Query::HINT_READ_ONLY])) {
            /** @phpstan-var T $document */
            $document = $class->newInstance();
            $this->hydratorFactory->hydrate($document, $data, $hints);

            return $document;
        }

        $isManagedObject = false;
        $serializedId    = null;
        $id              = null;
        if (! $class->isQueryResultDocument) {
            $id              = $class->getDatabaseIdentifierValue($data['_id']);
            $serializedId    = serialize($id);
            $isManagedObject = isset($this->documentRegistry->getIdentityMap()[$class->name][$serializedId]);
        }

        if ($isManagedObject) {
            /** @phpstan-var T $document */
            $document = $this->documentRegistry->getIdentityMap()[$class->name][$serializedId];
            if ($this->isUninitializedObject($document)) {
                if ($this->dm->getConfiguration()->isNativeLazyObjectEnabled()) {
                    $class->reflClass->markLazyObjectAsInitialized($document);
                } elseif ($document instanceof InternalProxy) {
                    $document->__setInitialized(true);
                } elseif ($document instanceof GhostObjectInterface) {
                    $document->setProxyInitializer(null);
                } else {
                    throw new \RuntimeException(sprintf('Expected uninitialized proxy or ghost object from class "%s"', $document::class));
                }

                $overrideLocalValues = true;
                if ($document instanceof NotifyPropertyChanged) {
                    $document->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = ! empty($hints[Query::HINT_REFRESH]);
            }

            if ($overrideLocalValues) {
                $data                                                                    = $this->hydratorFactory->hydrate($document, $data, $hints);
                $this->documentRegistry->getOrCreateObjectState($document)->originalData = $data;
            }
        } else {
            if ($document === null) {
                /** @phpstan-var T $document */
                $document = $class->newInstance();
            }

            if (! $class->isQueryResultDocument) {
                $this->registerManaged($document, $id, $data);
            }

            $data = $this->hydratorFactory->hydrate($document, $data, $hints);

            if (! $class->isQueryResultDocument && ! $class->isView()) {
                $this->documentRegistry->getOrCreateObjectState($document)->originalData = $data;
            }
        }

        return $document;
    }

    /**
     * Initializes (loads) an uninitialized persistent collection of a document.
     *
     * @internal
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $collection
     */
    public function loadCollection(PersistentCollectionInterface $collection): void
    {
        if ($collection->getOwner() === null) {
            throw PersistentCollectionException::ownerRequiredToLoadCollection();
        }

        $this->getDocumentPersister(get_class($collection->getOwner()))->loadCollection($collection);
        $this->lifecycleEventManager->postCollectionLoad($collection);
    }

    /**
     * Gets the original data of a document. The original data is the data that was
     * present at the time the document was reconstituted from the database.
     *
     * @deprecated Use {@see DocumentRegistry::getOriginalDocumentData()} instead.
     *
     * @return array<string, mixed>
     */
    public function getOriginalDocumentData(object $document): array
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.18',
            '%s is deprecated, call %s::getOriginalDocumentData() instead.',
            __METHOD__,
            DocumentRegistry::class,
        );

        return $this->documentRegistry->getOriginalDocumentData($document);
    }

    /**
     * @deprecated Use {@see DocumentRegistry::setOriginalDocumentData()} instead.
     *
     * @param array<string, mixed> $data
     */
    public function setOriginalDocumentData(object $document, array $data): void
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.18',
            '%s is deprecated, call %s::setOriginalDocumentData() instead.',
            __METHOD__,
            DocumentRegistry::class,
        );

        $this->documentRegistry->setOriginalDocumentData($document, $data);
        unset($this->documentChangeSets[$document]);
    }

    /**
     * Gets the identifier of a document.
     *
     * @deprecated Use {@see DocumentRegistry::getDocumentIdentifier()} instead.
     *
     * @return mixed The identifier value
     */
    public function getDocumentIdentifier(object $document)
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.18',
            '%s is deprecated, call %s::getDocumentIdentifier() instead.',
            __METHOD__,
            DocumentRegistry::class,
        );

        return $this->documentRegistry->getDocumentIdentifier($document);
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @internal
     *
     * @return bool TRUE if this UnitOfWork has pending insertions, FALSE otherwise.
     */
    public function hasPendingInsertions(): bool
    {
        return ! empty($this->scheduledDocumentInsertions);
    }

    /**
     * Registers a document as managed.
     *
     * TODO: This method assumes that $id is a valid PHP identifier for the
     * document class. If the class expects its database identifier to be an
     * ObjectId, and an incompatible $id is registered (e.g. an integer), the
     * document identifiers map will become inconsistent with the identity map.
     * In the future, we may want to round-trip $id through a PHP and database
     * conversion and throw an exception if it's inconsistent.
     *
     * @internal
     *
     * @param mixed                $id   The identifier values.
     * @param array<string, mixed> $data
     */
    public function registerManaged(object $document, $id, array $data): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        if ($class->identifier && $id !== null) {
            $class->setIdentifierValue($document, $class->getPHPIdentifierValue($id));
        }

        if (! $this->documentRegistry->track($class, $document, PersistenceState::Managed, $data)) {
            return;
        }

        $this->registerPropertyChangedListener($document);
    }

    /**
     * Clears the property changeset of the given document.
     *
     * @internal
     */
    public function clearDocumentChangeSet(object $document): void
    {
        unset($this->documentChangeSets[$document]);
    }

    /* PropertyChangedListener implementation */

    /**
     * Notifies this UnitOfWork of a property change in a document.
     *
     * @param object $sender       The document that owns the property.
     * @param string $propertyName The name of the property that changed.
     * @param mixed  $oldValue     The old value of the property.
     * @param mixed  $newValue     The new value of the property.
     */
    public function propertyChanged($sender, $propertyName, $oldValue, $newValue): void
    {
        $oid   = spl_object_id($sender);
        $class = $this->dm->getClassMetadata($sender::class);

        if (! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark document for synchronization
        if (! isset($this->documentChangeSets[$sender])) {
            $this->documentChangeSets[$sender] = new ChangeSet($sender, $this->documentRegistry->getOrCreateObjectState($sender)->originalData ?? []);
        }

        $this->documentChangeSets[$sender]->recordChange($propertyName, $newValue);
        if (isset($this->scheduledForSynchronization[$class->name][$oid])) {
            return;
        }

        $this->scheduleForSynchronization($sender);
    }

    /**
     * Gets the currently scheduled document insertions in this UnitOfWork.
     *
     * @return array<int, object>
     */
    public function getScheduledDocumentInsertions(): array
    {
        return $this->scheduledDocumentInsertions;
    }

    /**
     * Gets the currently scheduled document upserts in this UnitOfWork.
     *
     * @return array<int, object>
     */
    public function getScheduledDocumentUpserts(): array
    {
        return $this->scheduledDocumentUpserts;
    }

    /**
     * Gets the currently scheduled document updates in this UnitOfWork.
     *
     * @return array<int, object>
     */
    public function getScheduledDocumentUpdates(): array
    {
        return $this->scheduledDocumentUpdates;
    }

    /**
     * Gets the currently scheduled document deletions in this UnitOfWork.
     *
     * @return array<int, object>
     */
    public function getScheduledDocumentDeletions(): array
    {
        return $this->scheduledDocumentDeletions;
    }

    /**
     * Get the currently scheduled complete collection deletions
     *
     * @internal
     *
     * @return array<int, PersistentCollectionInterface<array-key, object>>
     */
    public function getScheduledCollectionDeletions(): array
    {
        return $this->scheduledCollectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @internal
     *
     * @return array<int, PersistentCollectionInterface<array-key, object>>
     */
    public function getScheduledCollectionUpdates(): array
    {
        return $this->scheduledCollectionUpdates;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * @internal
     */
    public function initializeObject(object $obj): void
    {
        if ($obj instanceof InternalProxy && $obj->__isInitialized() === false) {
            $obj->__load();
        } elseif ($obj instanceof GhostObjectInterface && $obj->isProxyInitialized() === false) {
            $obj->initializeProxy();
        } elseif ($obj instanceof PersistentCollectionInterface) {
            $obj->initialize();
        } elseif (PHP_VERSION_ID >= 80400) {
            $this->dm->getClassMetadata($obj::class)->reflClass->initializeLazyObject($obj);
        }
    }

    /**
     * Helper method to check whether a lazy loading proxy or persistent collection has been initialized.
     *
     * @internal
     */
    public function isUninitializedObject(object $obj): bool
    {
        return match (true) {
            $obj instanceof InternalProxy => ! $obj->__isInitialized(),
            $obj instanceof GhostObjectInterface => ! $obj->isProxyInitialized(),
            $obj instanceof PersistentCollectionInterface => ! $obj->isInitialized(),
            $this->dm->getConfiguration()->isNativeLazyObjectEnabled() => $this->dm->getClassMetadata($obj::class)->reflClass->isUninitializedLazyObject($obj),
            default => false
        };
    }

    /**
     * @internal
     *
     * @phpstan-param CommitOptions $options
     *
     * @phpstan-return CommitOptions
     */
    public function stripTransactionOptions(array $options): array
    {
        return array_diff_key(
            $options,
            self::TRANSACTION_OPTIONS,
        );
    }

    private function objToStr(object $obj): string
    {
        return method_exists($obj, '__toString') ? (string) $obj : $obj::class . '@' . spl_object_id($obj);
    }

    /** @phpstan-param CommitOptions $options */
    private function doCommit(array $options): void
    {
        foreach ($this->getClassesForCommitAction($this->scheduledDocumentUpserts) as $classAndDocuments) {
            [$class, $documents] = $classAndDocuments;
            $this->executeUpserts($class, $documents, $options);
        }

        foreach ($this->getClassesForCommitAction($this->scheduledDocumentInsertions) as $classAndDocuments) {
            [$class, $documents] = $classAndDocuments;
            $this->executeInserts($class, $documents, $options);
        }

        foreach ($this->getClassesForCommitAction($this->scheduledDocumentUpdates) as $classAndDocuments) {
            [$class, $documents] = $classAndDocuments;
            $this->executeUpdates($class, $documents, $options);
        }

        foreach ($this->getClassesForCommitAction($this->scheduledDocumentDeletions, true) as $classAndDocuments) {
            [$class, $documents] = $classAndDocuments;
            $this->executeDeletions($class, $documents, $options);
        }
    }

    /** @phpstan-param CommitOptions $options */
    private function useTransaction(array $options): bool
    {
        if (isset($options['withTransaction'])) {
            return $options['withTransaction'];
        }

        return $this->dm->getConfiguration()->isTransactionalFlushEnabled();
    }

    /**
     * @phpstan-param CommitOptions $options
     *
     * @phpstan-return CommitOptions
     */
    private function getTransactionOptions(array $options): array
    {
        return array_intersect_key(
            array_merge(
                $this->dm->getConfiguration()->getDefaultCommitOptions(),
                $options,
            ),
            self::TRANSACTION_OPTIONS,
        );
    }

    /**
     * This following method was taken from the MongoDB Library and adapted to not use the default 120 seconds timeout.
     * The code within this method is licensed under the Apache License. Copyright belongs to MongoDB, Inc.
     *
     * @see https://github.com/mongodb/mongo-php-library/blob/1.17.0/src/Operation/WithTransaction.php
     * @see https://github.com/mongodb/specifications/blob/master/source/transactions-convenient-api/transactions-convenient-api.rst#pseudo-code
     *
     * @phpstan-param CommitOptions $transactionOptions
     */
    private function withTransaction(Session $session, callable $callback, array $transactionOptions = []): void
    {
        $numAttempts = 0;

        while (true) {
            $session->startTransaction($transactionOptions);

            try {
                $numAttempts++;
                call_user_func($callback, $session);
            } catch (Throwable $e) {
                if ($session->isInTransaction()) {
                    $session->abortTransaction();
                }

                if (
                    $e instanceof RuntimeException &&
                    $e->hasErrorLabel('TransientTransactionError') &&
                    ! $this->shouldAbortWithTransaction($numAttempts)
                ) {
                    continue;
                }

                throw $e;
            }

            if (! $session->isInTransaction()) {
                // Assume callback intentionally ended the transaction
                return;
            }

            while (true) {
                try {
                    $session->commitTransaction();
                } catch (RuntimeException $e) {
                    if (
                        $e->getCode() !== 50 /* MaxTimeMSExpired */ &&
                        $e->hasErrorLabel('UnknownTransactionCommitResult') &&
                        ! $this->shouldAbortWithTransaction($numAttempts)
                    ) {
                        // Retry committing the transaction
                        continue;
                    }

                    if (
                        $e->hasErrorLabel('TransientTransactionError') &&
                        ! $this->shouldAbortWithTransaction($numAttempts)
                    ) {
                        // Restart the transaction, invoking the callback again
                        continue 2;
                    }

                    throw $e;
                }

                // Commit was successful
                break;
            }

            // Transaction was successful
            break;
        }
    }

    private function shouldAbortWithTransaction(int $numAttempts): bool
    {
        return $numAttempts >= 2;
    }
}
