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
use Traversable;

use function array_combine;
use function array_diff_key;
use function array_map;
use function array_udiff_assoc;
use function array_values;
use function count;
use function is_object;
use function method_exists;

/**
 * Trait with methods needed to implement PersistentCollectionInterface.
 *
 * @internal
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

    public function setDocumentManager(DocumentManager $dm): void
    {
        $this->dm  = $dm;
        $this->uow = $dm->getUnitOfWork();
    }

    public function setMongoData(array $mongoData): void
    {
        $this->mongoData = $mongoData;
    }

    /** @return mixed[] */
    public function getMongoData(): array
    {
        return $this->mongoData;
    }

    public function setHints(array $hints): void
    {
        $this->hints = $hints;
    }

    /** @return array<int, mixed> */
    public function getHints(): array
    {
        return $this->hints;
    }

    public function initialize(): void
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
    }

    public function isDirty(): bool
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

    public function setDirty(bool $dirty): void
    {
        $this->isDirty = $dirty;
    }

    public function setOwner(object $document, array $mapping): void
    {
        $this->owner   = $document;
        $this->mapping = $mapping;
    }

    public function takeSnapshot(): void
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

    public function clearSnapshot(): void
    {
        $this->snapshot = [];
        $this->isDirty  = $this->coll->count() !== 0;
    }

    /** @return array<TKey, T> */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    /** @return T[] */
    public function getDeleteDiff(): array
    {
        return array_udiff_assoc(
            $this->snapshot,
            $this->coll->toArray(),
            static fn ($a, $b) => $a === $b ? 0 : 1,
        );
    }

    /** @return list<T> */
    public function getDeletedDocuments(): array
    {
        $coll               = $this->coll->toArray();
        $loadedObjectsByOid = array_combine(array_map('spl_object_id', $this->snapshot), $this->snapshot);
        $newObjectsByOid    = array_combine(array_map('spl_object_id', $coll), $coll);

        return array_values(array_diff_key($loadedObjectsByOid, $newObjectsByOid));
    }

    /** @return T[] */
    public function getInsertDiff(): array
    {
        return array_udiff_assoc(
            $this->coll->toArray(),
            $this->snapshot,
            static fn ($a, $b) => $a === $b ? 0 : 1,
        );
    }

    /** @return list<T> */
    public function getInsertedDocuments(): array
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

    /** @return array<string, mixed> */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /** @return ClassMetadata<T> */
    public function getTypeClass(): ClassMetadata
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

    public function setInitialized(bool $bool): void
    {
        $this->initialized = $bool;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /** @return T|null */
    public function first(): ?object
    {
        $this->initialize();

        return $this->coll->first();
    }

    /** @return T|null */
    public function last(): ?object
    {
        $this->initialize();

        return $this->coll->last();
    }

    /** @return bool|T|null */
    public function remove(mixed $key): bool|object|null
    {
        return $this->doRemove($key, false);
    }

    /** @param T $element */
    public function removeElement(mixed $element): bool
    {
        $this->initialize();
        $removed = $this->coll->removeElement($element);

        if (! $removed) {
            return $removed;
        }

        $this->changed();

        return $removed;
    }

    /** @param TKey $key */
    public function containsKey(string|int $key): bool
    {
        $this->initialize();

        return $this->coll->containsKey($key);
    }

    /**
     * @param TMaybeContained $element
     *
     * @template TMaybeContained
     */
    public function contains(mixed $element): bool
    {
        $this->initialize();

        return $this->coll->contains($element);
    }

    public function exists(Closure $p): bool
    {
        $this->initialize();

        return $this->coll->exists($p);
    }

    /**
     * @param TMaybeContained $element
     *
     * @phpstan-return (TMaybeContained is T ? TKey|false : false)
     *
     * @template TMaybeContained
     */
    public function indexOf(mixed $element): string|int|false
    {
        $this->initialize();

        return $this->coll->indexOf($element);
    }

    /**
     * @param TKey $key
     *
     * @return T|null
     */
    public function get(string|int $key): ?object
    {
        $this->initialize();

        return $this->coll->get($key);
    }

    /** @return list<TKey> */
    public function getKeys(): array
    {
        $this->initialize();

        return $this->coll->getKeys();
    }

    /** @return list<T> */
    public function getValues(): array
    {
        $this->initialize();

        return $this->coll->getValues();
    }

    public function count(): int
    {
        // Workaround around not being able to directly count inverse collections anymore
        $this->initialize();

        return $this->coll->count();
    }

    /**
     * @param TKey   $key
     * @param T|null $value
     */
    public function set(string|int $key, mixed $value): void
    {
        $this->doSet($key, $value, false);
    }

    /** @param T $element */
    public function add(mixed $element): bool
    {
        return $this->doAdd($element, false);
    }

    public function isEmpty(): bool
    {
        return $this->initialized ? $this->coll->isEmpty() : $this->count() === 0;
    }

    /** @phpstan-return Traversable<TKey, T> */
    public function getIterator(): Traversable
    {
        $this->initialize();

        return $this->coll->getIterator();
    }

    /**
     * @phpstan-param Closure(T):U $func
     *
     * @phpstan-return BaseCollection<TKey, U>
     *
     * @template U
     */
    public function map(Closure $func): BaseCollection
    {
        $this->initialize();

        return $this->coll->map($func);
    }

    /** @phpstan-return BaseCollection<TKey, T> */
    public function filter(Closure $p): BaseCollection
    {
        $this->initialize();

        return $this->coll->filter($p);
    }

    public function forAll(Closure $p): bool
    {
        $this->initialize();

        return $this->coll->forAll($p);
    }

    /** @return array{0: BaseCollection<TKey, T>, 1: BaseCollection<TKey, T>} */
    public function partition(Closure $p): array
    {
        $this->initialize();

        return $this->coll->partition($p);
    }

    /** @return array<TKey, T> */
    public function toArray(): array
    {
        $this->initialize();

        return $this->coll->toArray();
    }

    public function clear(): void
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

    /** @return array<TKey, T> */
    public function slice(int $offset, int|null $length = null): array
    {
        $this->initialize();

        return $this->coll->slice($offset, $length);
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

    /* ArrayAccess implementation */

    /** @param TKey $offset */
    public function offsetExists(mixed $offset): bool
    {
        $this->initialize();

        return $this->coll->offsetExists($offset);
    }

    /**
     * @param TKey $offset
     *
     * @phpstan-return T|null
     */
    public function offsetGet(mixed $offset): ?object
    {
        $this->initialize();

        return $this->coll->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! isset($offset)) {
            $this->doAdd($value, true);

            return;
        }

        $this->doSet($offset, $value, true);
    }

    /** @param TKey $offset */
    public function offsetUnset(mixed $offset): void
    {
        $this->doRemove($offset, true);
    }

    /** @return TKey|null */
    public function key(): int|string|null
    {
        return $this->coll->key();
    }

    /**
     * Gets the element of the collection at the current iterator position.
     *
     * @phpstan-return T|false
     */
    public function current(): object|false
    {
        return $this->coll->current();
    }

    /** @phpstan-return T|false */
    public function next(): object|false
    {
        return $this->coll->next();
    }

    /** @return BaseCollection<TKey, T> */
    public function unwrap(): BaseCollection
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
    public function __clone(): void
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
     * @param T|null $value
     */
    private function doAdd(?object $value, bool $arrayAccess): true
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
     * @param TKey $offset
     *
     * @return bool|T|null
     * @phpstan-return (
     *      $arrayAccess is false
     *      ? T|null
     *      : T|true|null
     * )
     */
    private function doRemove(string|int $offset, bool $arrayAccess): bool|object|null
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
     * @param TKey   $offset
     * @param T|null $value
     */
    private function doSet(string|int $offset, ?object $value, bool $arrayAccess): void
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
     * @phpstan-param Closure(TKey, T):bool $p
     *
     * @phpstan-return T|null
     */
    public function findFirst(Closure $p): ?object
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
    public function reduce(Closure $func, mixed $initial = null): mixed
    {
        if (! method_exists($this->coll, 'reduce')) {
            throw new BadMethodCallException('reduce() is only available since doctrine/collections v2');
        }

        return $this->coll->reduce($func, $initial);
    }
}
