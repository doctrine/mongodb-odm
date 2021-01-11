<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Closure;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

use function array_udiff;
use function array_udiff_assoc;
use function array_values;
use function count;
use function get_class;
use function is_object;
use function spl_object_hash;

/**
 * Trait with methods needed to implement PersistentCollectionInterface.
 */
trait PersistentCollectionTrait
{
    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array
     */
    private $snapshot = [];

    /**
     * Collection's owning entity
     *
     * @var object|null
     */
    private $owner;

    /** @var array|null */
    private $mapping;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var bool
     */
    private $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     *
     * @var bool
     */
    private $initialized = true;

    /**
     * The wrapped Collection instance.
     *
     * @var BaseCollection
     */
    private $coll;

    /**
     * The DocumentManager that manages the persistence of the collection.
     *
     * @var DocumentManager|null
     */
    private $dm;

    /**
     * The UnitOfWork that manages the persistence of the collection.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * The raw mongo data that will be used to initialize this collection.
     *
     * @var array
     */
    private $mongoData = [];

    /**
     * Any hints to account for during reconstitution/lookup of the documents.
     *
     * @var array
     */
    private $hints = [];

    /** {@inheritdoc} */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm  = $dm;
        $this->uow = $dm->getUnitOfWork();
    }

    /** {@inheritdoc} */
    public function setMongoData(array $mongoData)
    {
        $this->mongoData = $mongoData;
    }

    /** {@inheritdoc} */
    public function getMongoData()
    {
        return $this->mongoData;
    }

    /** {@inheritdoc} */
    public function setHints(array $hints)
    {
        $this->hints = $hints;
    }

    /** {@inheritdoc} */
    public function getHints()
    {
        return $this->hints;
    }

    /** {@inheritdoc} */
    public function initialize()
    {
        if ($this->initialized || ! $this->mapping) {
            return;
        }

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
    private function changed()
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function setDirty($dirty)
    {
        $this->isDirty = $dirty;
    }

    /** {@inheritdoc} */
    public function setOwner(object $document, array $mapping)
    {
        $this->owner   = $document;
        $this->mapping = $mapping;
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function clearSnapshot()
    {
        $this->snapshot = [];
        $this->isDirty  = $this->coll->count() !== 0;
    }

    /** {@inheritdoc} */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /** {@inheritdoc} */
    public function getDeleteDiff()
    {
        return array_udiff_assoc(
            $this->snapshot,
            $this->coll->toArray(),
            static function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }

    /** {@inheritdoc} */
    public function getDeletedDocuments()
    {
        $compare = static function ($a, $b) {
            $compareA = is_object($a) ? spl_object_hash($a) : $a;
            $compareb = is_object($b) ? spl_object_hash($b) : $b;

            return $compareA === $compareb ? 0 : ($compareA > $compareb ? 1 : -1);
        };

        return array_values(array_udiff(
            $this->snapshot,
            $this->coll->toArray(),
            $compare
        ));
    }

    /** {@inheritdoc} */
    public function getInsertDiff()
    {
        return array_udiff_assoc(
            $this->coll->toArray(),
            $this->snapshot,
            static function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }

    /** {@inheritdoc} */
    public function getInsertedDocuments()
    {
        $compare = static function ($a, $b) {
            $compareA = is_object($a) ? spl_object_hash($a) : $a;
            $compareb = is_object($b) ? spl_object_hash($b) : $b;

            return $compareA === $compareb ? 0 : ($compareA > $compareb ? 1 : -1);
        };

        return array_values(array_udiff(
            $this->coll->toArray(),
            $this->snapshot,
            $compare
        ));
    }

    /** {@inheritdoc} */
    public function getOwner(): ?object
    {
        return $this->owner;
    }

    /** {@inheritdoc} */
    public function getMapping()
    {
        return $this->mapping;
    }

    /** {@inheritdoc} */
    public function getTypeClass()
    {
        if ($this->dm === null) {
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

    /** {@inheritdoc} */
    public function setInitialized($bool)
    {
        $this->initialized = $bool;
    }

    /** {@inheritdoc} */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /** {@inheritdoc} */
    public function first()
    {
        $this->initialize();

        return $this->coll->first();
    }

    /** {@inheritdoc} */
    public function last()
    {
        $this->initialize();

        return $this->coll->last();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        return $this->doRemove($key, false);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->coll->containsKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        $this->initialize();

        return $this->coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Closure $p)
    {
        $this->initialize();

        return $this->coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->initialize();

        return $this->coll->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        $this->initialize();

        return $this->coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $this->initialize();

        return $this->coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        // Workaround around not being able to directly count inverse collections anymore
        $this->initialize();

        return $this->coll->count();
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->doSet($key, $value, false);
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        return $this->doAdd($value, false);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->initialized ? $this->coll->isEmpty() : $this->count() === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->initialize();

        return $this->coll->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function map(Closure $func)
    {
        $this->initialize();

        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        $this->initialize();

        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(Closure $p)
    {
        $this->initialize();

        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(Closure $p)
    {
        $this->initialize();

        return $this->coll->partition($p);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->initialize();

        return $this->coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->coll->slice($offset, $length);
    }

    /**
     * Called by PHP when this collection is serialized. Ensures that the
     * internal state of the collection can be reproduced after serialization
     */
    public function __sleep()
    {
        return ['coll', 'initialized', 'mongoData', 'snapshot', 'isDirty', 'hints'];
    }

    /* ArrayAccess implementation */

    /**
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        $this->initialize();

        return $this->coll->offsetExists($offset);
    }

    /**
     * @see get()
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->coll->offsetGet($offset);
    }

    /**
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if (! isset($offset)) {
            $this->doAdd($value, true);

            return;
        }

        $this->doSet($offset, $value, true);
    }

    /**
     * @see remove()
     */
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

    /**
     * Moves the internal iterator position to the next element.
     */
    public function next()
    {
        return $this->coll->next();
    }

    /**
     * {@inheritdoc}
     */
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
     * 4. Lazy loading grabs entities from old owner object.
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

        if ($this->uow !== null && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        return true;
    }

    /**
     * Actual logic for removing element by its key.
     *
     * @param mixed $offset
     * @param bool  $arrayAccess
     *
     * @return bool
     */
    private function doRemove($offset, $arrayAccess)
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
     * @param bool  $arrayAccess
     */
    private function doSet($offset, $value, $arrayAccess)
    {
        $arrayAccess ? $this->coll->offsetSet($offset, $value) : $this->coll->set($offset, $value);

        // Handle orphanRemoval
        if ($this->uow !== null && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        $this->changed();
    }

    /**
     * Returns whether or not this collection has orphan removal enabled.
     *
     * Embedded documents are automatically considered as "orphan removal enabled" because they might have references
     * that require to trigger cascade remove operations.
     *
     * @return bool
     */
    private function isOrphanRemovalEnabled()
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
     *
     * @return bool
     */
    private function needsSchedulingForSynchronization()
    {
        return $this->owner && $this->dm && ! empty($this->mapping['isOwningSide'])
            && $this->dm->getClassMetadata(get_class($this->owner))->isChangeTrackingNotify();
    }
}
