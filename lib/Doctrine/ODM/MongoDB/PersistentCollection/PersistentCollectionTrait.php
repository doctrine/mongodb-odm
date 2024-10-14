<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use BadMethodCallException;
use Closure;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use ReturnTypeWillChange;
use Traversable;

use function array_combine;
use function array_diff_key;
use function array_map;
use function array_udiff_assoc;
use function array_values;
use function count;
use function get_class;
use function is_object;
use function method_exists;

/**
 * Trait with methods needed to implement PersistentCollectionInterface.
 *
 * @phpstan-import-type Hints from UnitOfWork
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @template TKey of array-key
 * @template T of object
 */
trait PersistentCollectionTrait
{
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

    public function first()
    {
        $this->initialize();

        return $this->coll->first();
    }

    public function last()
    {
        $this->initialize();

        return $this->coll->last();
    }

    public function remove($key)
    {
        return $this->doRemove($key, false);
    }

    public function removeElement($element)
    {
        $this->initialize();
        $removed = $this->coll->removeElement($element);

        if (! $removed) {
            return $removed;
        }

        $this->changed();

        return $removed;
    }

    public function containsKey($key)
    {
        $this->initialize();

        return $this->coll->containsKey($key);
    }

    /** @template TMaybeContained */
    public function contains($element)
    {
        $this->initialize();

        return $this->coll->contains($element);
    }

    public function exists(Closure $p)
    {
        $this->initialize();

        return $this->coll->exists($p);
    }

    /**
     * @phpstan-return (TMaybeContained is T ? TKey|false : false)
     *
     * @template TMaybeContained
     */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->coll->indexOf($element);
    }

    public function get($key)
    {
        $this->initialize();

        return $this->coll->get($key);
    }

    public function getKeys()
    {
        $this->initialize();

        return $this->coll->getKeys();
    }

    public function getValues()
    {
        $this->initialize();

        return $this->coll->getValues();
    }

    /** @return int */
    #[ReturnTypeWillChange]
    public function count()
    {
        // Workaround around not being able to directly count inverse collections anymore
        $this->initialize();

        return $this->coll->count();
    }

    public function set($key, $value)
    {
        $this->doSet($key, $value, false);
    }

    public function add($element)
    {
        return $this->doAdd($element, false);
    }

    public function isEmpty()
    {
        return $this->initialized ? $this->coll->isEmpty() : $this->count() === 0;
    }

    /**
     * @return Traversable
     * @phpstan-return Traversable<TKey, T>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        $this->initialize();

        return $this->coll->getIterator();
    }

    public function map(Closure $func)
    {
        $this->initialize();

        return $this->coll->map($func);
    }

    public function filter(Closure $p)
    {
        $this->initialize();

        return $this->coll->filter($p);
    }

    public function forAll(Closure $p)
    {
        $this->initialize();

        return $this->coll->forAll($p);
    }

    public function partition(Closure $p)
    {
        $this->initialize();

        return $this->coll->partition($p);
    }

    public function toArray()
    {
        $this->initialize();

        return $this->coll->toArray();
    }

    public function clear()
    {
        if ($this->initialized && $this->isEmpty()) {
            return;
        }

        if ($this->isOrphanRemovalEnabled()) {
            $this->initialize();
            foreach ($this->coll as $element) {
                $this->uow->scheduleOrphanRemoval($element);
            }
        }

        $this->mongoData = [];
        $this->coll->clear();

        // Nothing to do for inverse-side collections
        if (! $this->mapping['isOwningSide']) {
            return;
        }

        // Nothing to do if the collection was initialized but contained no data
        if ($this->initialized && empty($this->snapshot)) {
            return;
        }

        $this->changed();
        $this->uow->scheduleCollectionDeletion($this);
        $this->takeSnapshot();
    }

    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->coll->slice($offset, $length);
    }

    /**
     * Called by PHP when this collection is serialized. Ensures that the
     * internal state of the collection can be reproduced after serialization
     *
     * @return string[]
     */
    public function __sleep()
    {
        return ['coll', 'initialized', 'mongoData', 'snapshot', 'isDirty', 'hints'];
    }

    /* ArrayAccess implementation */

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $this->initialize();

        return $this->coll->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     * @phpstan-return T|null
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->coll->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (! isset($offset)) {
            $this->doAdd($value, true);

            return;
        }

        $this->doSet($offset, $value, true);
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->doRemove($offset, true);
    }

    public function key()
    {
        return $this->coll->key();
    }

    /**
     * Gets the element of the collection at the current iterator position.
     */
    public function current()
    {
        return $this->coll->current();
    }

    public function next()
    {
        return $this->coll->next();
    }

    public function unwrap()
    {
        return $this->coll;
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
     * @param mixed $value
     * @param bool  $arrayAccess
     *
     * @return true
     */
    private function doAdd($value, $arrayAccess)
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
     * @param mixed $offset
     *
     * @return bool|T|null
     * @phpstan-return (
     *      $arrayAccess is false
     *      ? T|null
     *      : T|true|null
     * )
     */
    private function doRemove($offset, bool $arrayAccess)
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
     *
     * @param mixed $offset
     * @param mixed $value
     */
    private function doSet($offset, $value, bool $arrayAccess): void
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

    /**
     * @phpstan-param Closure(TKey, T):bool $p
     *
     * @phpstan-return T|null
     */
    public function findFirst(Closure $p)
    {
        if (! method_exists($this->coll, 'findFirst')) {
            throw new BadMethodCallException('findFirst() is only available since doctrine/collections v2');
        }

        return $this->coll->findFirst($p);
    }

    /**
     * @phpstan-param Closure(TReturn|TInitial|null, T):(TInitial|TReturn) $func
     * @phpstan-param TInitial|null $initial
     *
     * @phpstan-return TReturn|TInitial|null
     *
     * @phpstan-template TReturn
     * @phpstan-template TInitial
     */
    public function reduce(Closure $func, $initial = null)
    {
        if (! method_exists($this->coll, 'reduce')) {
            throw new BadMethodCallException('reduce() is only available since doctrine/collections v2');
        }

        return $this->coll->reduce($func, $initial);
    }
}
