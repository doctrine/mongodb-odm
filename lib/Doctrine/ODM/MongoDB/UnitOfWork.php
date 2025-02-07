<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Persisters\CollectionPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\DateType;
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
use Throwable;
use UnexpectedValueException;

use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function assert;
use function call_user_func;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;
use function preg_match;
use function serialize;
use function spl_object_hash;
use function sprintf;
use function trigger_deprecation;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @phpstan-import-type AssociationFieldMapping from ClassMetadata
 * @phpstan-type ChangeSet array{
 *      0: mixed,
 *      1: mixed
 * }
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
     */
    public const STATE_MANAGED = 1;

    /**
     * A document is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by a DocumentManager.
     */
    public const STATE_NEW = 2;

    /**
     * A detached document is an instance with a persistent identity that is not
     * (or no longer) associated with a DocumentManager (and a UnitOfWork).
     */
    public const STATE_DETACHED = 3;

    /**
     * A removed document instance is an instance with a persistent identity,
     * associated with a DocumentManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
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
     * @var array<class-string, array<string, object>>
     */
    private array $identityMap = [];

    /**
     * Map of all identifiers of managed documents.
     * Keys are object ids (spl_object_hash).
     *
     * @var array<string, mixed>
     */
    private array $documentIdentifiers = [];

    /**
     * Map of the original document data of managed documents.
     * Keys are object ids (spl_object_hash). This is used for calculating changesets
     * at commit time.
     *
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *           A value will only really be copied if the value in the document is modified
     *           by the user.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $originalDocumentData = [];

    /**
     * Map of document changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array<string, array<string, ChangeSet>>
     */
    private array $documentChangeSets = [];

    /**
     * The (cached) states of any known documents.
     * Keys are object ids (spl_object_hash).
     *
     * @var array<string, self::STATE_*>
     */
    private array $documentStates = [];

    /**
     * Map of documents that are scheduled for dirty checking at commit time.
     *
     * Documents are grouped by their class name, and then indexed by their SPL
     * object hash. This is only used for documents with a change tracking
     * policy of DEFERRED_EXPLICIT.
     *
     * @var array<class-string, array<string, object>>
     */
    private array $scheduledForSynchronization = [];

    /**
     * A list of all pending document insertions.
     *
     * @var array<string, object>
     */
    private array $scheduledDocumentInsertions = [];

    /**
     * A list of all pending document updates.
     *
     * @var array<string, object>
     */
    private array $scheduledDocumentUpdates = [];

    /**
     * A list of all pending document upserts.
     *
     * @var array<string, object>
     */
    private array $scheduledDocumentUpserts = [];

    /**
     * A list of all pending document deletions.
     *
     * @var array<string, object>
     */
    private array $scheduledDocumentDeletions = [];

    /**
     * All pending collection deletions.
     *
     * @var array<string, PersistentCollectionInterface<array-key, object>>
     */
    private array $scheduledCollectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @var array<string, PersistentCollectionInterface<array-key, object>>
     */
    private array $scheduledCollectionUpdates = [];

    /**
     * A list of documents related to collections scheduled for update or deletion
     *
     * @var array<string, array<string, PersistentCollectionInterface<array-key, object>>>
     */
    private array $hasScheduledCollections = [];

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array<string, array<PersistentCollectionInterface<array-key, object>>>
     */
    private array $visitedCollections = [];

    /**
     * The DocumentManager that "owns" this UnitOfWork instance.
     */
    private DocumentManager $dm;

    /**
     * The EventManager used for dispatching events.
     */
    private EventManager $evm;

    /**
     * Additional documents that are scheduled for removal.
     *
     * @var array<string, object>
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

    /**
     * Array of parent associations between embedded documents.
     *
     * @var array<string, array{0: AssociationFieldMapping, 1: object|null, 2: string}>
     */
    private array $parentAssociations = [];

    private LifecycleEventManager $lifecycleEventManager;

    private ReflectionService $reflectionService;

    /**
     * Array of embedded documents known to UnitOfWork. We need to hold them to prevent spl_object_hash
     * collisions in case already managed object is lost due to GC (so now it won't). Embedded documents
     * found during doDetach are removed from the registry, to empty it altogether clear() can be utilized.
     *
     * @var array<string, object>
     */
    private array $embeddedDocumentsRegistry = [];

    private int $commitsInProgress = 0;

    /**
     * Initializes a new UnitOfWork instance, bound to the given DocumentManager.
     */
    public function __construct(DocumentManager $dm, EventManager $evm, HydratorFactory $hydratorFactory)
    {
        $this->dm                    = $dm;
        $this->evm                   = $evm;
        $this->hydratorFactory       = $hydratorFactory;
        $this->lifecycleEventManager = new LifecycleEventManager($dm, $this, $evm);
        $this->reflectionService     = new RuntimeReflectionService();
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
     * Sets the parent association for a given embedded document.
     *
     * @internal
     *
     * @phpstan-param FieldMapping $mapping
     */
    public function setParentAssociation(object $document, array $mapping, ?object $parent, string $propertyPath): void
    {
        $oid                                   = spl_object_hash($document);
        $this->embeddedDocumentsRegistry[$oid] = $document;
        $this->parentAssociations[$oid]        = [$mapping, $parent, $propertyPath];
    }

    /**
     * Gets the parent association for a given embedded document.
     *
     *     <code>
     *     list($mapping, $parent, $propertyPath) = $this->getParentAssociation($embeddedDocument);
     *     </code>
     *
     * @phpstan-return array{0: AssociationFieldMapping, 1: object|null, 2: string}|null
     */
    public function getParentAssociation(object $document): ?array
    {
        $oid = spl_object_hash($document);

        return $this->parentAssociations[$oid] ?? null;
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

        $this->commitsInProgress++;
        if ($this->commitsInProgress > 1) {
            throw MongoDBException::commitInProgress();
        }

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

            $this->scheduledDocumentInsertions  =
            $this->scheduledDocumentUpserts     =
            $this->scheduledDocumentUpdates     =
            $this->scheduledDocumentDeletions   =
            $this->documentChangeSets           =
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
     * @param array<string, object> $documents
     *
     * @phpstan-return array<class-string, array{0: ClassMetadata<object>, 1: array<string, object>}>
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
     * @return array array('property' => array(0 => mixed, 1 => mixed))
     * @phpstan-return array<string, ChangeSet>
     */
    public function getDocumentChangeSet(object $document): array
    {
        $oid = spl_object_hash($document);

        return $this->documentChangeSets[$oid] ?? [];
    }

    /**
     * Sets the changeset for a document.
     *
     * @internal
     *
     * @param array<string, ChangeSet> $changeset
     */
    public function setDocumentChangeSet(object $document, array $changeset): void
    {
        $this->documentChangeSets[spl_object_hash($document)] = $changeset;
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
        foreach ($class->reflFields as $name => $refProp) {
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

        $this->computeOrRecomputeChangeSet($class, $document);
    }

    /**
     * Used to do the common work of computeChangeSet and recomputeSingleDocumentChangeSet
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    private function computeOrRecomputeChangeSet(ClassMetadata $class, object $document, bool $recompute = false): void
    {
        if ($class->isView()) {
            return;
        }

        $oid           = spl_object_hash($document);
        $actualData    = $this->getDocumentActualData($document);
        $isNewDocument = ! isset($this->originalDocumentData[$oid]);
        if ($isNewDocument) {
            // Document is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->originalDocumentData[$oid] = $actualData;
            $changeSet                        = [];
            foreach ($actualData as $propName => $actualValue) {
                /* At this PersistentCollection shouldn't be here, probably it
                 * was cloned and its ownership must be fixed
                 */
                if ($actualValue instanceof PersistentCollectionInterface && $actualValue->getOwner() !== $document) {
                    $actualData[$propName] = $this->fixPersistentCollectionOwnership($actualValue, $document, $class, $propName);
                    $actualValue           = $actualData[$propName];
                }

                // ignore inverse side of reference relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                    continue;
                }

                $changeSet[$propName] = [null, $actualValue];
            }

            $this->documentChangeSets[$oid] = $changeSet;
        } else {
            if ($class->isReadOnly) {
                return;
            }

            // Document is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData           = $this->originalDocumentData[$oid];
            $isChangeTrackingNotify = $class->isChangeTrackingNotify();
            if ($isChangeTrackingNotify && ! $recompute && isset($this->documentChangeSets[$oid])) {
                $changeSet = $this->documentChangeSets[$oid];
            } else {
                $changeSet = [];
            }

            $gridFSMetadataProperty = null;

            if ($class->isFile) {
                try {
                    $gridFSMetadata         = $class->getFieldMappingByDbFieldName('metadata');
                    $gridFSMetadataProperty = $gridFSMetadata['fieldName'];
                } catch (MappingException) {
                }
            }

            foreach ($actualData as $propName => $actualValue) {
                // skip not saved fields
                if (
                    (isset($class->fieldMappings[$propName]['notSaved']) && $class->fieldMappings[$propName]['notSaved'] === true) ||
                    ($class->isFile && $propName !== $gridFSMetadataProperty)
                ) {
                    continue;
                }

                $orgValue = $originalData[$propName] ?? null;

                // skip if value has not changed
                if ($orgValue === $actualValue) {
                    if (! $actualValue instanceof PersistentCollectionInterface) {
                        continue;
                    }

                    if (! $actualValue->isDirty() && ! $this->isCollectionScheduledForDeletion($actualValue)) {
                        // consider dirty collections as changed as well
                        continue;
                    }
                }

                // if relationship is a embed-one, schedule orphan removal to trigger cascade remove operations
                if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::ONE) {
                    if ($orgValue !== null) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }

                    $changeSet[$propName] = [$orgValue, $actualValue];
                    continue;
                }

                // if owning side of reference-one relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::ONE && $class->fieldMappings[$propName]['isOwningSide']) {
                    if ($orgValue !== null && $class->fieldMappings[$propName]['orphanRemoval']) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }

                    $changeSet[$propName] = [$orgValue, $actualValue];
                    continue;
                }

                if ($isChangeTrackingNotify) {
                    continue;
                }

                // ignore inverse side of reference relationship
                if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['isInverseSide']) {
                    continue;
                }

                // Persistent collection was exchanged with the "originally"
                // created one. This can only mean it was cloned and replaced
                // on another document.
                if ($actualValue instanceof PersistentCollectionInterface && $actualValue->getOwner() !== $document) {
                    $actualValue = $this->fixPersistentCollectionOwnership($actualValue, $document, $class, $propName);
                }

                // if embed-many or reference-many relationship
                if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::MANY) {
                    $changeSet[$propName] = [$orgValue, $actualValue];
                    /* If original collection was exchanged with a non-empty value
                     * and $set will be issued, there is no need to $unset it first
                     */
                    if ($actualValue && $actualValue->isDirty() && CollectionHelper::usesSet($class->fieldMappings[$propName]['strategy'])) {
                        continue;
                    }

                    if ($orgValue !== $actualValue && $orgValue instanceof PersistentCollectionInterface) {
                        $this->scheduleCollectionDeletion($orgValue);
                    }

                    continue;
                }

                // skip equivalent date values
                if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'date') {
                    $dateType = Type::getType('date');
                    assert($dateType instanceof DateType);
                    $dbOrgValue    = $dateType->convertToDatabaseValue($orgValue);
                    $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

                    // We rely on loose comparison to compare every field (including microseconds)
                    // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
                    if ($dbOrgValue == $dbActualValue) {
                        continue;
                    }
                }

                // regular field
                $changeSet[$propName] = [$orgValue, $actualValue];
            }

            if ($changeSet) {
                $this->documentChangeSets[$oid] = isset($this->documentChangeSets[$oid])
                    ? $changeSet + $this->documentChangeSets[$oid]
                    : $changeSet;

                $this->originalDocumentData[$oid] = $actualData;
                $this->scheduleForUpdate($document);
            }
        }

        // Look for changes in associations of the document
        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => empty($assoc['notSaved']),
        );

        foreach ($associationMappings as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);

            if ($value === null) {
                continue;
            }

            $this->computeAssociationChanges($document, $mapping, $value);

            if (isset($mapping['reference'])) {
                continue;
            }

            $values = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value->unwrap();

            foreach ($values as $obj) {
                $oid2 = spl_object_hash($obj);

                if (! isset($this->documentChangeSets[$oid2])) {
                    continue;
                }

                if (empty($this->documentChangeSets[$oid][$mapping['fieldName']])) {
                    // instance of $value is the same as it was previously otherwise there would be
                    // change set already in place
                    $this->documentChangeSets[$oid][$mapping['fieldName']] = [$value, $value];
                }

                if (! $isNewDocument) {
                    $this->scheduleForUpdate($document);
                }

                break;
            }
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
        foreach ($this->identityMap as $className => $documents) {
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
            switch (true) {
                case $class->isChangeTrackingDeferredImplicit():
                    $documentsToProcess = $documents;
                    break;

                case isset($this->scheduledForSynchronization[$className]):
                    $documentsToProcess = $this->scheduledForSynchronization[$className];
                    break;

                default:
                    $documentsToProcess = [];
            }

            foreach ($documentsToProcess as $document) {
                // Ignore uninitialized proxy objects
                if ($this->isUninitializedObject($document)) {
                    continue;
                }

                // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
                $oid = spl_object_hash($document);
                if (
                    isset($this->scheduledDocumentInsertions[$oid])
                    || isset($this->scheduledDocumentUpserts[$oid])
                    || isset($this->scheduledDocumentDeletions[$oid])
                    || ! isset($this->documentStates[$oid])
                ) {
                    continue;
                }

                $this->computeChangeSet($class, $document);
            }
        }
    }

    /**
     * Computes the changes of an association.
     *
     * @param mixed $value The value of the association.
     * @phpstan-param AssociationFieldMapping $assoc
     *
     * @throws InvalidArgumentException
     */
    private function computeAssociationChanges(object $parentDocument, array $assoc, $value): void
    {
        $isNewParentDocument   = isset($this->scheduledDocumentInsertions[spl_object_hash($parentDocument)]);
        $class                 = $this->dm->getClassMetadata($parentDocument::class);
        $topOrExistingDocument = ( ! $isNewParentDocument || ! $class->isEmbeddedDocument);

        if ($value instanceof InternalProxy && ! $value->__isInitialized()) {
            return;
        }

        if ($value instanceof GhostObjectInterface && ! $value->isProxyInitialized()) {
            return;
        }

        if ($value instanceof PersistentCollectionInterface && $value->isDirty() && $value->getOwner() !== null && ($assoc['isOwningSide'] || isset($assoc['embedded']))) {
            if ($topOrExistingDocument || CollectionHelper::usesSet($assoc['strategy'])) {
                $this->scheduleCollectionUpdate($value);
            }

            $topmostOwner                                               = $this->getOwningDocument($value->getOwner());
            $this->visitedCollections[spl_object_hash($topmostOwner)][] = $value;
            if (! empty($assoc['orphanRemoval']) || isset($assoc['embedded'])) {
                $value->initialize();
                foreach ($value->getDeletedDocuments() as $orphan) {
                    $this->scheduleOrphanRemoval($orphan);
                }
            }
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
                    if (! $assoc['isCascadePersist']) {
                        throw new InvalidArgumentException('A new document was found through a relationship that was not'
                            . ' configured to cascade persist operations: ' . $this->objToStr($entry) . '.'
                            . ' Explicitly persist the new document or configure cascading persist operations'
                            . ' on the relationship.');
                    }

                    $this->persistNew($targetClass, $entry);
                    $this->setParentAssociation($entry, $assoc, $parentDocument, $path);
                    $this->computeChangeSet($targetClass, $entry);
                    break;

                case self::STATE_MANAGED:
                    if ($targetClass->isEmbeddedDocument) {
                        [, $knownParent] = $this->getParentAssociation($entry);
                        if ($knownParent && $knownParent !== $parentDocument) {
                            $entry = clone $entry;
                            if ($assoc['type'] === ClassMetadata::ONE) {
                                $class->setFieldValue($parentDocument, $assoc['fieldName'], $entry);
                                $this->setOriginalDocumentProperty(spl_object_hash($parentDocument), $assoc['fieldName'], $entry);
                                $poid = spl_object_hash($parentDocument);
                                if (isset($this->documentChangeSets[$poid][$assoc['fieldName']])) {
                                    $this->documentChangeSets[$poid][$assoc['fieldName']][1] = $entry;
                                }
                            } else {
                                // must use unwrapped value to not trigger orphan removal
                                $unwrappedValue[$key] = $entry;
                            }

                            $this->persistNew($targetClass, $entry);
                        }

                        $this->setParentAssociation($entry, $assoc, $parentDocument, $path);
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

        $oid = spl_object_hash($document);

        if (! isset($this->documentStates[$oid]) || $this->documentStates[$oid] !== self::STATE_MANAGED) {
            throw new InvalidArgumentException('Document must be managed.');
        }

        if (! $class->isInheritanceTypeNone()) {
            $class = $this->dm->getClassMetadata($document::class);
        }

        $this->computeOrRecomputeChangeSet($class, $document, true);
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
        $oid    = spl_object_hash($document);
        $upsert = false;
        if ($class->identifier) {
            $idValue = $class->getIdentifierValue($document);
            $upsert  = ! $class->isEmbeddedDocument && ! $class->timeSeriesOptions && $idValue !== null;

            if ($class->generatorType === ClassMetadata::GENERATOR_TYPE_NONE && $idValue === null) {
                throw new InvalidArgumentException(sprintf(
                    '%s uses NONE identifier generation strategy but no identifier was provided when persisting.',
                    $document::class,
                ));
            }

            if ($class->generatorType === ClassMetadata::GENERATOR_TYPE_AUTO && $idValue !== null && ! preg_match('#^[0-9a-f]{24}$#', (string) $idValue)) {
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

            $this->documentIdentifiers[$oid] = $idValue;
        } else {
            // this is for embedded documents without identifiers
            $this->documentIdentifiers[$oid] = $oid;
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

            if (! empty($this->documentChangeSets[$oid]) || $this->hasScheduledCollections($document)) {
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

        foreach ($documents as $oid => $document) {
            if (! $class->isEmbeddedDocument) {
                $persister->delete($document, $options);
            }

            unset(
                $this->documentIdentifiers[$oid],
                $this->originalDocumentData[$oid],
            );

            // Clear snapshot information for any referenced PersistentCollection
            // http://www.doctrine-project.org/jira/browse/MODM-95
            foreach ($class->associationMappings as $fieldMapping) {
                if (! isset($fieldMapping['type']) || $fieldMapping['type'] !== ClassMetadata::MANY) {
                    continue;
                }

                $value = $class->reflFields[$fieldMapping['fieldName']]->getValue($document);
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
        $oid = spl_object_hash($document);

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

        if (! isset($this->documentIdentifiers[$oid])) {
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
        $oid = spl_object_hash($document);

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

        $this->scheduledDocumentUpserts[$oid] = $document;
        $this->documentIdentifiers[$oid]      = $class->getIdentifierValue($document);
        $this->addToIdentityMap($document);
    }

    /**
     * Checks whether a document is scheduled for insertion.
     */
    public function isScheduledForInsert(object $document): bool
    {
        return isset($this->scheduledDocumentInsertions[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is scheduled for upsert.
     */
    public function isScheduledForUpsert(object $document): bool
    {
        return isset($this->scheduledDocumentUpserts[spl_object_hash($document)]);
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
        $oid = spl_object_hash($document);
        if (! isset($this->documentIdentifiers[$oid])) {
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
        return isset($this->scheduledDocumentUpdates[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is registered to be checked in the unit of work.
     */
    public function isScheduledForSynchronization(object $document): bool
    {
        $class = $this->dm->getClassMetadata($document::class);

        return isset($this->scheduledForSynchronization[$class->name][spl_object_hash($document)]);
    }

    /**
     * Schedules a document for deletion.
     *
     * @internal
     */
    public function scheduleForDelete(object $document, bool $isView = false): void
    {
        $oid = spl_object_hash($document);

        if (isset($this->scheduledDocumentInsertions[$oid])) {
            if ($this->isInIdentityMap($document)) {
                $this->removeFromIdentityMap($document);
            }

            unset($this->scheduledDocumentInsertions[$oid]);

            return; // document has not been persisted yet, so nothing more to do.
        }

        if (! $this->isInIdentityMap($document)) {
            return; // ignore
        }

        $this->removeFromIdentityMap($document);
        $this->documentStates[$oid] = self::STATE_REMOVED;

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
        return isset($this->scheduledDocumentDeletions[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is scheduled for insertion, update or deletion.
     *
     * @internal
     */
    public function isDocumentScheduled(object $document): bool
    {
        $oid = spl_object_hash($document);

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
        $id    = $this->getIdForIdentityMap($document);

        if (isset($this->identityMap[$class->name][$id])) {
            return false;
        }

        $this->identityMap[$class->name][$id] = $document;

        if ($document instanceof NotifyPropertyChanged && ! $this->isUninitializedObject($document)) {
            $document->addPropertyChangedListener($this);
        }

        return true;
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
        $oid = spl_object_hash($document);

        if (isset($this->documentStates[$oid])) {
            return $this->documentStates[$oid];
        }

        $class = $this->dm->getClassMetadata($document::class);

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
        if ($class->isVersioned && $class->versionField !== null) {
            return $class->getFieldValue($document, $class->versionField)
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
     * Removes a document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @internal
     *
     * @throws InvalidArgumentException
     */
    public function removeFromIdentityMap(object $document): bool
    {
        $oid = spl_object_hash($document);

        // Check if id is registered first
        if (! isset($this->documentIdentifiers[$oid])) {
            return false;
        }

        $class = $this->dm->getClassMetadata($document::class);
        $id    = $this->getIdForIdentityMap($document);

        if (isset($this->identityMap[$class->name][$id])) {
            unset($this->identityMap[$class->name][$id]);
            $this->documentStates[$oid] = self::STATE_DETACHED;

            return true;
        }

        return false;
    }

    /**
     * Gets a document in the identity map by its identifier hash.
     *
     * @internal
     *
     * @param mixed $id Document identifier
     * @phpstan-param ClassMetadata<T> $class
     *
     * @phpstan-return T
     *
     * @throws InvalidArgumentException If the class does not have an identifier.
     *
     * @template T of object
     */
    public function getById($id, ClassMetadata $class): object
    {
        if (! $class->identifier) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not have an identifier', $class->name));
        }

        $serializedId = serialize($class->getDatabaseIdentifierValue($id));

        return $this->identityMap[$class->name][$serializedId];
    }

    /**
     * Tries to get a document by its identifier hash. If no document is found
     * for the given hash, FALSE is returned.
     *
     * @internal
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
     *
     * @ phpstan-suppress InvalidReturnStatement, InvalidReturnType because of the inability of defining a generic property map
     */
    public function tryGetById($id, ClassMetadata $class)
    {
        if (! $class->identifier) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not have an identifier', $class->name));
        }

        $serializedId = serialize($class->getDatabaseIdentifierValue($id));

        return $this->identityMap[$class->name][$serializedId] ?? false;
    }

    /**
     * Schedules a document for dirty-checking at commit-time.
     *
     * @internal
     */
    public function scheduleForSynchronization(object $document): void
    {
        $class                                                                       = $this->dm->getClassMetadata($document::class);
        $this->scheduledForSynchronization[$class->name][spl_object_hash($document)] = $document;
    }

    /**
     * Checks whether a document is registered in the identity map.
     *
     * @internal
     */
    public function isInIdentityMap(object $document): bool
    {
        $oid = spl_object_hash($document);

        if (! isset($this->documentIdentifiers[$oid])) {
            return false;
        }

        $class = $this->dm->getClassMetadata($document::class);
        $id    = $this->getIdForIdentityMap($document);

        return isset($this->identityMap[$class->name][$id]);
    }

    private function getIdForIdentityMap(object $document): string
    {
        $class = $this->dm->getClassMetadata($document::class);

        if (! $class->identifier) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
            $id = serialize($class->getDatabaseIdentifierValue($id));
        }

        return $id;
    }

    /**
     * Checks whether an identifier exists in the identity map.
     *
     * @internal
     *
     * @param mixed $id
     */
    public function containsId($id, string $rootClassName): bool
    {
        return isset($this->identityMap[$rootClassName][serialize($id)]);
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
     * @param array<string, object> $visited
     *
     * @throws InvalidArgumentException
     * @throws MongoDBException
     */
    private function doPersist(object $document, array &$visited): void
    {
        $oid = spl_object_hash($document);
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

                $this->documentStates[$oid] = self::STATE_MANAGED;
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
     * @param array<string, object> $visited
     *
     * @throws MongoDBException
     */
    private function doRemove(object $document, array &$visited): void
    {
        $oid = spl_object_hash($document);
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
     * @param array<string, object> $visited
     * @phpstan-param AssociationFieldMapping|null $assoc
     *
     * @throws InvalidArgumentException If the entity instance is NEW.
     * @throws LockException If the document uses optimistic locking through a
     *                       version attribute and the version check against the
     *                       managed copy fails.
     */
    private function doMerge(object $document, array &$visited, ?object $prevManagedCopy = null, ?array $assoc = null): object
    {
        $oid = spl_object_hash($document);

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
                $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
                $documentVersion    = $class->reflFields[$class->versionField]->getValue($document);

                // Throw exception if versions don't match
                if ($managedCopyVersion !== $documentVersion) {
                    throw LockException::lockFailedVersionMissmatch($document, $documentVersion, $managedCopyVersion);
                }
            }

            // Merge state of $document into existing (managed) document
            foreach ($class->reflClass->getProperties() as $nativeReflection) {
                $name = $nativeReflection->name;
                $prop = $this->reflectionService->getAccessibleProperty($class->name, $name);
                assert($prop instanceof ReflectionProperty);

                if (method_exists($prop, 'isInitialized') && ! $prop->isInitialized($document)) {
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
                                    $this->removeFromIdentityMap($current);
                                }

                                if ($targetClass->subClasses) {
                                    $other = $this->dm->find($targetClass->name, $relatedId);
                                } else {
                                    $other = $this
                                        ->dm
                                        ->getProxyFactory()
                                        ->getProxy($targetClass, $relatedId);
                                    $this->registerManaged($other, $relatedId, []);
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
                            $this->originalDocumentData[$oid][$name] = $managedCol;
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
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->add($managedCopy);

                if ($assoc['type'] === ClassMetadata::MANY && isset($assoc['mappedBy'])) {
                    $class->reflFields[$assoc['mappedBy']]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        // Mark the managed copy visited as well
        $visited[spl_object_hash($managedCopy)] = $managedCopy;

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
     * @param array<string, object> $visited
     */
    private function doDetach(object $document, array &$visited): void
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        switch ($this->getDocumentState($document, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->removeFromIdentityMap($document);
                unset(
                    $this->scheduledDocumentInsertions[$oid],
                    $this->scheduledDocumentUpdates[$oid],
                    $this->scheduledDocumentDeletions[$oid],
                    $this->documentIdentifiers[$oid],
                    $this->documentStates[$oid],
                    $this->originalDocumentData[$oid],
                    $this->parentAssociations[$oid],
                    $this->scheduledDocumentUpserts[$oid],
                    $this->hasScheduledCollections[$oid],
                    $this->embeddedDocumentsRegistry[$oid],
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
     * @param array<string, object> $visited
     *
     * @throws InvalidArgumentException If the document is not MANAGED.
     */
    private function doRefresh(object $document, array &$visited): void
    {
        $oid = spl_object_hash($document);
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
     * @param array<string, object> $visited
     */
    private function cascadeRefresh(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadeRefresh'],
        );

        foreach ($associationMappings as $mapping) {
            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
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
     * @param array<string, object> $visited
     */
    private function cascadeDetach(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);
        foreach ($class->fieldMappings as $mapping) {
            if (! $mapping['isCascadeDetach']) {
                continue;
            }

            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
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
     * @param array<string, object> $visited
     */
    private function cascadeMerge(object $document, object $managedCopy, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadeMerge'],
        );

        foreach ($associationMappings as $assoc) {
            $relatedDocuments = $class->reflFields[$assoc['fieldName']]->getValue($document);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments === $class->reflFields[$assoc['fieldName']]->getValue($managedCopy)) {
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
     * @param array<string, object> $visited
     */
    private function cascadePersist(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn ($assoc) => $assoc['isCascadePersist'],
        );

        foreach ($associationMappings as $fieldName => $mapping) {
            $relatedDocuments = $class->reflFields[$fieldName]->getValue($document);

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
                        [, $knownParent] = $this->getParentAssociation($relatedDocument);
                        if ($knownParent && $knownParent !== $document) {
                            $relatedDocument               = clone $relatedDocument;
                            $relatedDocuments[$relatedKey] = $relatedDocument;
                        }

                        $pathKey = CollectionHelper::isList($mapping['strategy']) ? $count++ : $relatedKey;
                        $this->setParentAssociation($relatedDocument, $mapping, $document, $mapping['fieldName'] . '.' . $pathKey);
                    }

                    $this->doPersist($relatedDocument, $visited);
                }
            } elseif ($relatedDocuments !== null) {
                if (! empty($mapping['embedded'])) {
                    [, $knownParent] = $this->getParentAssociation($relatedDocuments);
                    if ($knownParent && $knownParent !== $document) {
                        $relatedDocuments = clone $relatedDocuments;
                        $class->setFieldValue($document, $mapping['fieldName'], $relatedDocuments);
                    }

                    $this->setParentAssociation($relatedDocuments, $mapping, $document, $mapping['fieldName']);
                }

                $this->doPersist($relatedDocuments, $visited);
            }
        }
    }

    /**
     * Cascades the delete operation to associated documents.
     *
     * @param array<string, object> $visited
     */
    private function cascadeRemove(object $document, array &$visited): void
    {
        $class = $this->dm->getClassMetadata($document::class);
        foreach ($class->fieldMappings as $mapping) {
            if (! $mapping['isCascadeRemove'] && ( ! isset($mapping['orphanRemoval']) || ! $mapping['orphanRemoval'])) {
                continue;
            }

            $this->initializeObject($document);

            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
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
                $documentVersion = $class->reflFields[$class->versionField]->getValue($document);
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
            $this->identityMap                  =
            $this->documentIdentifiers          =
            $this->originalDocumentData         =
            $this->documentChangeSets           =
            $this->documentStates               =
            $this->scheduledForSynchronization  =
            $this->scheduledDocumentInsertions  =
            $this->scheduledDocumentUpserts     =
            $this->scheduledDocumentUpdates     =
            $this->scheduledDocumentDeletions   =
            $this->scheduledCollectionUpdates   =
            $this->scheduledCollectionDeletions =
            $this->parentAssociations           =
            $this->embeddedDocumentsRegistry    =
            $this->orphanRemovals               =
            $this->hasScheduledCollections      = [];

            $event = new Event\OnClearEventArgs($this->dm);
        } else {
            $visited = [];
            foreach ($this->identityMap as $className => $documents) {
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
        $this->orphanRemovals[spl_object_hash($document)] = $document;
    }

    /**
     * Unschedules an embedded or referenced object for removal.
     *
     * @internal
     */
    public function unscheduleOrphanRemoval(object $document): void
    {
        $oid = spl_object_hash($document);
        unset($this->orphanRemovals[$oid]);
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
            $class->reflFields[$propName]->setValue($document, $newValue);
            if ($this->isScheduledForUpdate($document)) {
                // @todo following line should be superfluous once collections are stored in change sets
                $this->setOriginalDocumentProperty(spl_object_hash($document), $propName, $newValue);
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
        $oid = spl_object_hash($coll);
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
        return isset($this->scheduledCollectionDeletions[spl_object_hash($coll)]);
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

        $oid = spl_object_hash($coll);
        if (! isset($this->scheduledCollectionDeletions[$oid])) {
            return;
        }

        $topmostOwner = $this->getOwningDocument($coll->getOwner());
        unset($this->scheduledCollectionDeletions[$oid]);
        unset($this->hasScheduledCollections[spl_object_hash($topmostOwner)][$oid]);
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

        $oid = spl_object_hash($coll);
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

        $oid = spl_object_hash($coll);
        if (! isset($this->scheduledCollectionUpdates[$oid])) {
            return;
        }

        $topmostOwner = $this->getOwningDocument($coll->getOwner());
        unset($this->scheduledCollectionUpdates[$oid]);
        unset($this->hasScheduledCollections[spl_object_hash($topmostOwner)][$oid]);
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
        return isset($this->scheduledCollectionUpdates[spl_object_hash($coll)]);
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
        $oid = spl_object_hash($document);

        return $this->visitedCollections[$oid] ?? [];
    }

    /**
     * Gets PersistentCollections that are scheduled to update and related to $document
     *
     * @internal
     *
     * @return array<string, PersistentCollectionInterface<array-key, object>>
     */
    public function getScheduledCollections(object $document): array
    {
        $oid = spl_object_hash($document);

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
        return isset($this->hasScheduledCollections[spl_object_hash($document)]);
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

        $document                                                                          = $this->getOwningDocument($coll->getOwner());
        $this->hasScheduledCollections[spl_object_hash($document)][spl_object_hash($coll)] = $coll;

        if ($document !== $coll->getOwner()) {
            $parent  = $coll->getOwner();
            $mapping = [];
            while (($parentAssoc = $this->getParentAssociation($parent)) !== null) {
                [$mapping, $parent] = $parentAssoc;
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
            $parentAssociation = $this->getParentAssociation($document);

            if (! $parentAssociation) {
                throw new UnexpectedValueException('Could not determine parent association for ' . $document::class);
            }

            [, $parentDocument] = $parentAssociation;
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
            $isManagedObject = isset($this->identityMap[$class->name][$serializedId]);
        }

        $oid = null;
        if ($isManagedObject) {
            /** @phpstan-var T $document */
            $document = $this->identityMap[$class->name][$serializedId];
            $oid      = spl_object_hash($document);
            if ($this->isUninitializedObject($document)) {
                if ($document instanceof InternalProxy) {
                    $document->__setInitialized(true);
                } elseif ($document instanceof GhostObjectInterface) {
                    $document->setProxyInitializer(null);
                } else {
                    throw new \RuntimeException(sprintf('Expected uninitialized proxy or ghost object from class "%s"', $document::name));
                }

                $overrideLocalValues = true;
                if ($document instanceof NotifyPropertyChanged) {
                    $document->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = ! empty($hints[Query::HINT_REFRESH]);
            }

            if ($overrideLocalValues) {
                $data                             = $this->hydratorFactory->hydrate($document, $data, $hints);
                $this->originalDocumentData[$oid] = $data;
            }
        } else {
            if ($document === null) {
                /** @phpstan-var T $document */
                $document = $class->newInstance();
            }

            if (! $class->isQueryResultDocument) {
                $this->registerManaged($document, $id, $data);
                $oid                                            = spl_object_hash($document);
                $this->documentStates[$oid]                     = self::STATE_MANAGED;
                $this->identityMap[$class->name][$serializedId] = $document;
            }

            $data = $this->hydratorFactory->hydrate($document, $data, $hints);

            if (! $class->isQueryResultDocument && ! $class->isView()) {
                $this->originalDocumentData[$oid] = $data;
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
     * Gets the identity map of the UnitOfWork.
     *
     * @internal
     *
     * @return array<class-string, array<string, object>>
     */
    public function getIdentityMap(): array
    {
        return $this->identityMap;
    }

    /**
     * Gets the original data of a document. The original data is the data that was
     * present at the time the document was reconstituted from the database.
     *
     * @return array<string, mixed>
     */
    public function getOriginalDocumentData(object $document): array
    {
        $oid = spl_object_hash($document);

        return $this->originalDocumentData[$oid] ?? [];
    }

    /**
     * @internal
     *
     * @param array<string, mixed> $data
     */
    public function setOriginalDocumentData(object $document, array $data): void
    {
        $oid                              = spl_object_hash($document);
        $this->originalDocumentData[$oid] = $data;
        unset($this->documentChangeSets[$oid]);
    }

    /**
     * Sets a property value of the original data array of a document.
     *
     * @internal
     *
     * @param mixed $value
     */
    public function setOriginalDocumentProperty(string $oid, string $property, $value): void
    {
        $this->originalDocumentData[$oid][$property] = $value;
    }

    /**
     * Gets the identifier of a document.
     *
     * @return mixed The identifier value
     */
    public function getDocumentIdentifier(object $document)
    {
        return $this->documentIdentifiers[spl_object_hash($document)] ?? null;
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
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of documents in the identity map.
     *
     * @internal
     */
    public function size(): int
    {
        $count = 0;
        foreach ($this->identityMap as $documentSet) {
            $count += count($documentSet);
        }

        return $count;
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
        $oid   = spl_object_hash($document);
        $class = $this->dm->getClassMetadata($document::class);

        if (! $class->identifier || $id === null) {
            $this->documentIdentifiers[$oid] = $oid;
        } else {
            $this->documentIdentifiers[$oid] = $class->getPHPIdentifierValue($id);
        }

        $this->documentStates[$oid]       = self::STATE_MANAGED;
        $this->originalDocumentData[$oid] = $data;
        $this->addToIdentityMap($document);
    }

    /**
     * Clears the property changeset of the document with the given OID.
     *
     * @internal
     */
    public function clearDocumentChangeSet(string $oid): void
    {
        $this->documentChangeSets[$oid] = [];
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
        $oid   = spl_object_hash($sender);
        $class = $this->dm->getClassMetadata($sender::class);

        if (! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark document for synchronization
        $this->documentChangeSets[$oid][$propertyName] = [$oldValue, $newValue];
        if (isset($this->scheduledForSynchronization[$class->name][$oid])) {
            return;
        }

        $this->scheduleForSynchronization($sender);
    }

    /**
     * Gets the currently scheduled document insertions in this UnitOfWork.
     *
     * @return array<string, object>
     */
    public function getScheduledDocumentInsertions(): array
    {
        return $this->scheduledDocumentInsertions;
    }

    /**
     * Gets the currently scheduled document upserts in this UnitOfWork.
     *
     * @return array<string, object>
     */
    public function getScheduledDocumentUpserts(): array
    {
        return $this->scheduledDocumentUpserts;
    }

    /**
     * Gets the currently scheduled document updates in this UnitOfWork.
     *
     * @return array<string, object>
     */
    public function getScheduledDocumentUpdates(): array
    {
        return $this->scheduledDocumentUpdates;
    }

    /**
     * Gets the currently scheduled document deletions in this UnitOfWork.
     *
     * @return array<string, object>
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
     * @return array<string, PersistentCollectionInterface<array-key, object>>
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
     * @return array<string, PersistentCollectionInterface<array-key, object>>
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
            $obj instanceof InternalProxy => $obj->__isInitialized() === false,
            $obj instanceof GhostObjectInterface => $obj->isProxyInitialized() === false,
            $obj instanceof PersistentCollectionInterface => $obj->isInitialized() === false,
            default => false
        };
    }

    /** @internal */
    public function stripTransactionOptions(array $options): array
    {
        return array_diff_key(
            $options,
            self::TRANSACTION_OPTIONS,
        );
    }

    private function objToStr(object $obj): string
    {
        return method_exists($obj, '__toString') ? (string) $obj : $obj::class . '@' . spl_object_hash($obj);
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

    /** @phpstan-param CommitOptions $options */
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
