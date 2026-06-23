<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use LogicException;

use function array_combine;
use function array_diff_key;
use function array_map;
use function array_udiff_assoc;
use function array_values;
use function count;
use function get_class;
use function is_object;
use function sprintf;

/**
 * Trait with methods needed to implement PersistentCollectionInterface.
 *
 * Collection/ReadableCollection/ArrayAccess interface methods live in
 * PersistentCollectionCompatibility so they can be typed or untyped depending
 * on which version of doctrine/collections is installed.
 *
 * @phpstan-import-type Hints from UnitOfWork
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @template TKey of array-key
 * @template T of object
 */
trait PersistentCollectionTrait
{
    /** @use PersistentCollectionCompatibility<TKey, T> */
    use PersistentCollectionCompatibility;

    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array<TKey, T>
     */
    private array $snapshot = [];

    /**
     * Collection's owning document
     */
    private ?object $owner = null;

    /**
     * @var array<string, mixed>|null
     * @phpstan-var FieldMapping|null
     */
    private ?array $mapping = null;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     */
    private bool $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     */
    private bool $initialized = true;

    /**
     * The wrapped Collection instance.
     *
     * @var BaseCollection<TKey, T>
     */
    private BaseCollection $coll;

    /**
     * The DocumentManager that manages the persistence of the collection.
     */
    private DocumentManager $dm;

    /**
     * The UnitOfWork that manages the persistence of the collection.
     */
    private UnitOfWork $uow;

    /**
     * The raw mongo data that will be used to initialize this collection.
     *
     * @var mixed[]
     */
    private array $mongoData = [];

    /**
     * Any hints to account for during reconstitution/lookup of the documents.
     *
     * @var array<int, mixed>
     * @phpstan-var Hints
     */
    private array $hints = [];

    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm  = $dm;
        $this->uow = $dm->getUnitOfWork();
    }

    public function setMongoData(array $mongoData)
    {
        $this->mongoData = $mongoData;
    }

    public function getMongoData()
    {
        return $this->mongoData;
    }

    public function setHints(array $hints)
    {
        $this->hints = $hints;
    }

    public function getHints()
    {
        return $this->hints;
    }

    public function initialize()
    {
        if ($this->initialized || ! $this->mapping) {
            return;
        }

        /** @var array<TKey, T> $newObjects */
        $newObjects = [];

        if ($this->isDirty) {
            // Remember any NEW objects added through add()
            $newObjects = $this->coll->toArray();
        }

        $this->initialized = true;

        $this->coll->clear();
        $this->uow->loadCollection($this);
        $this->takeSnapshot();

        $this->mongoData = [];

        // Reattach any NEW objects added through add()
        if (! $newObjects) {
            return;
        }

        foreach ($newObjects as $key => $obj) {
            if (CollectionHelper::isHash($this->mapping['strategy'])) {
                $this->coll->set($key, $obj);
            } else {
                $this->coll->add($obj);
            }
        }

        $this->isDirty = true;
    }

    /**
     * Marks this collection as changed/dirty.
     */
    private function changed(): void
    {
        if ($this->isDirty) {
            return;
        }

        $this->isDirty = true;

        if (! $this->needsSchedulingForSynchronization() || $this->owner === null) {
            return;
        }

        $this->uow->scheduleForSynchronization($this->owner);
    }

    public function isDirty()
    {
        if ($this->isDirty) {
            return true;
        }

        if (! $this->initialized && count($this->coll)) {
            // not initialized collection with added elements
            return true;
        }

        if ($this->initialized) {
            // if initialized let's check with last known snapshot
            return $this->coll->toArray() !== $this->snapshot;
        }

        return false;
    }

    public function setDirty($dirty)
    {
        $this->isDirty = $dirty;
    }

    public function setOwner(object $document, array $mapping)
    {
        $this->owner   = $document;
        $this->mapping = $mapping;
    }

    public function takeSnapshot()
    {
        if ($this->mapping !== null && CollectionHelper::isList($this->mapping['strategy'])) {
            $array = $this->coll->toArray();
            $this->coll->clear();
            foreach ($array as $document) {
                $this->coll->add($document);
            }
        }

        $this->snapshot = $this->coll->toArray();
        $this->isDirty  = false;
    }

    public function clearSnapshot()
    {
        $this->snapshot = [];
        $this->isDirty  = $this->coll->count() !== 0;
    }

    public function getSnapshot()
    {
        return $this->snapshot;
    }

    public function getDeleteDiff()
    {
        return array_udiff_assoc(
            $this->snapshot,
            $this->coll->toArray(),
            static fn ($a, $b) => $a === $b ? 0 : 1,
        );
    }

    public function getDeletedDocuments()
    {
        $coll               = $this->coll->toArray();
        $loadedObjectsByOid = array_combine(array_map('spl_object_id', $this->snapshot), $this->snapshot);
        $newObjectsByOid    = array_combine(array_map('spl_object_id', $coll), $coll);

        return array_values(array_diff_key($loadedObjectsByOid, $newObjectsByOid));
    }

    public function getInsertDiff()
    {
        return array_udiff_assoc(
            $this->coll->toArray(),
            $this->snapshot,
            static fn ($a, $b) => $a === $b ? 0 : 1,
        );
    }

    public function getInsertedDocuments()
    {
        $coll               = $this->coll->toArray();
        $newObjectsByOid    = array_combine(array_map('spl_object_id', $coll), $coll);
        $loadedObjectsByOid = array_combine(array_map('spl_object_id', $this->snapshot), $this->snapshot);

        return array_values(array_diff_key($newObjectsByOid, $loadedObjectsByOid));
    }

    public function getOwner(): ?object
    {
        return $this->owner;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function getTypeClass()
    {
        if (! isset($this->dm)) {
            throw new MongoDBException('No DocumentManager is associated with this PersistentCollection, please set one using setDocumentManager method.');
        }

        if (empty($this->mapping)) {
            throw new MongoDBException('No mapping is associated with this PersistentCollection, please set one using setOwner method.');
        }

        if (empty($this->mapping['targetDocument'])) {
            throw new MongoDBException('Specifying targetDocument is required for the ClassMetadata to be obtained.');
        }

        return $this->dm->getClassMetadata($this->mapping['targetDocument']);
    }

    public function setInitialized($bool)
    {
        $this->initialized = $bool;
    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    public function unwrap()
    {
        return $this->coll;
    }

    /**
     * Called by PHP when this collection is serialized. Ensures that the
     * internal state of the collection can be reproduced after serialization
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'coll' => $this->coll,
            'initialized' => $this->initialized,
            'mongoData' => $this->mongoData,
            'snapshot' => $this->snapshot,
            'isDirty' => $this->isDirty,
            'hints' => $this->hints,
        ];
    }

    /**
     * @deprecated Implement and use __serialize() instead.
     *
     * @return string[]
     */
    public function __sleep()
    {
        return ['coll', 'initialized', 'mongoData', 'snapshot', 'isDirty', 'hints'];
    }

    /**
     * Cleanup internal state of cloned persistent collection.
     *
     * The following problems have to be prevented:
     * 1. Added documents are added to old PersistentCollection
     * 2. New collection is not dirty, if reused on other document nothing
     * changes.
     * 3. Snapshot leads to invalid diffs being generated.
     * 4. Lazy loading grabs documents from old owner object.
     * 5. New collection is connected to old owner and leads to duplicate keys.
     */
    public function __clone()
    {
        if (is_object($this->coll)) {
            $this->coll = clone $this->coll;
        }

        $this->initialize();

        $this->owner    = null;
        $this->snapshot = [];

        $this->changed();
    }

    /**
     * Actual logic for adding an element to the collection.
     *
     * @return true
     */
    private function doAdd(mixed $value, bool $arrayAccess): bool
    {
        /* Initialize the collection before calling add() so this append operation
         * uses the appropriate key. Otherwise, we risk overwriting original data
         * when $newObjects are re-added in a later call to initialize().
         */
        if (isset($this->mapping['strategy']) && CollectionHelper::isHash($this->mapping['strategy'])) {
            $this->initialize();
        }

        $arrayAccess ? $this->coll->offsetSet(null, $value) : $this->coll->add($value);
        $this->changed();

        if (isset($this->uow) && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        return true;
    }

    /**
     * Actual logic for removing element by its key.
     *
     * @phpstan-return (
     *      $arrayAccess is false
     *      ? T|null
     *      : T|true|null
     * )
     */
    private function doRemove(mixed $offset, bool $arrayAccess): mixed
    {
        $this->initialize();
        if ($arrayAccess) {
            $this->coll->offsetUnset($offset);
            $removed = true;
        } else {
            $removed = $this->coll->remove($offset);
        }

        if (! $removed && ! $arrayAccess) {
            return $removed;
        }

        $this->changed();

        return $removed;
    }

    /**
     * Actual logic for setting an element in the collection.
     */
    private function doSet(mixed $offset, mixed $value, bool $arrayAccess): void
    {
        $arrayAccess ? $this->coll->offsetSet($offset, $value) : $this->coll->set($offset, $value);

        // Handle orphanRemoval
        if (isset($this->uow) && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        $this->changed();
    }

    /**
     * Returns whether or not this collection has orphan removal enabled.
     *
     * Embedded documents are automatically considered as "orphan removal enabled" because they might have references
     * that require to trigger cascade remove operations.
     */
    private function isOrphanRemovalEnabled(): bool
    {
        if ($this->mapping === null) {
            return false;
        }

        if (isset($this->mapping['embedded'])) {
            return true;
        }

        return isset($this->mapping['reference']) && $this->mapping['isOwningSide'] && $this->mapping['orphanRemoval'];
    }

    /**
     * Checks whether collection owner needs to be scheduled for dirty change in case the collection is modified.
     */
    private function needsSchedulingForSynchronization(): bool
    {
        return $this->owner && isset($this->dm) && ! empty($this->mapping['isOwningSide'])
            && $this->dm->getClassMetadata(get_class($this->owner))->isChangeTrackingNotify();
    }

    /** @return BaseCollection<TKey, T> */
    public function matching(Criteria $criteria): BaseCollection
    {
        $this->initialize();

        if (! $this->coll instanceof Selectable) {
            throw new LogicException('The backed collection must implement Selectable to use matching().');
        }

        $coll = $this->coll->matching($criteria);

        if (! $coll instanceof BaseCollection) {
            throw new LogicException(sprintf('The matching() method of the backed collection must return an instance of "%s".', BaseCollection::class));
        }

        return $coll;
    }
}
