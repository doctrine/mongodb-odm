<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Closure;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\Common\Collections\Criteria;
use ReturnTypeWillChange;
use Traversable;

use function defined;

/**
 * Compatibility trait for PersistentCollection between doctrine/collections v2 and v3.
 *
 * @internal
 *
 * @template TKey of array-key
 * @template T of object
 */
if (! defined(Criteria::class . '::ASC')) {
    // doctrine/collections 3 — full type declarations required by the interface
    /**
     * @internal
     *
     * @template TKey of array-key
     * @template T of object
     */
    trait PersistentCollectionCompatibility
    {
        /**
         * Adds an element at the end of the collection.
         *
         * @phpstan-param T $value
         */
        public function add(mixed $value): void
        {
            $this->doAdd($value, false);
        }

        public function first(): mixed
        {
            $this->initialize();

            return $this->coll->first();
        }

        public function last(): mixed
        {
            $this->initialize();

            return $this->coll->last();
        }

        public function key(): int|string|null
        {
            return $this->coll->key();
        }

        public function current(): mixed
        {
            return $this->coll->current();
        }

        public function next(): mixed
        {
            return $this->coll->next();
        }

        public function contains(mixed $element): bool
        {
            $this->initialize();

            return $this->coll->contains($element);
        }

        public function isEmpty(): bool
        {
            return $this->initialized ? $this->coll->isEmpty() : $this->count() === 0;
        }

        public function containsKey(string|int $key): bool
        {
            $this->initialize();

            return $this->coll->containsKey($key);
        }

        public function get(string|int $key): mixed
        {
            $this->initialize();

            return $this->coll->get($key);
        }

        public function getKeys(): array
        {
            $this->initialize();

            return $this->coll->getKeys();
        }

        public function getValues(): array
        {
            $this->initialize();

            return $this->coll->getValues();
        }

        public function toArray(): array
        {
            $this->initialize();

            return $this->coll->toArray();
        }

        public function slice(int $offset, int|null $length = null): array
        {
            $this->initialize();

            return $this->coll->slice($offset, $length);
        }

        public function exists(Closure $p): bool
        {
            $this->initialize();

            return $this->coll->exists($p);
        }

        public function indexOf(mixed $element): int|string|false
        {
            $this->initialize();

            return $this->coll->indexOf($element);
        }

        public function filter(Closure $p): BaseCollection
        {
            $this->initialize();

            return $this->coll->filter($p);
        }

        public function map(Closure $func): BaseCollection
        {
            $this->initialize();

            return $this->coll->map($func);
        }

        public function partition(Closure $p): array
        {
            $this->initialize();

            return $this->coll->partition($p);
        }

        public function forAll(Closure $p): bool
        {
            $this->initialize();

            return $this->coll->forAll($p);
        }

        /**
         * @phpstan-param Closure(TKey, T):bool $p
         *
         * @phpstan-return T|null
         */
        public function findFirst(Closure $p): mixed
        {
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
            return $this->coll->reduce($func, $initial);
        }

        public function remove(string|int $key): mixed
        {
            return $this->doRemove($key, false);
        }

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

        public function set(string|int $key, mixed $value): void
        {
            $this->doSet($key, $value, false);
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

        public function count(): int
        {
            // Workaround around not being able to directly count inverse collections anymore
            $this->initialize();

            return $this->coll->count();
        }

        /** @phpstan-return Traversable<TKey, T> */
        public function getIterator(): Traversable
        {
            $this->initialize();

            return $this->coll->getIterator();
        }

        public function offsetExists(mixed $offset): bool
        {
            $this->initialize();

            return $this->coll->offsetExists($offset);
        }

        /** @phpstan-return T|null */
        public function offsetGet(mixed $offset): mixed
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

        public function offsetUnset(mixed $offset): void
        {
            $this->doRemove($offset, true);
        }
    }
} else {
    // doctrine/collections 2 — no type declarations
    /**
     * @internal
     *
     * @template TKey of array-key
     * @template T of object
     */
    trait PersistentCollectionCompatibility
    {
        /**
         * Adds an element at the end of the collection.
         *
         * @phpstan-param T $value
         *
         * @return true The return value is kept for BC reasons, but will be void in doctrine/mongodb-odm 3.0.
         */
        public function add(mixed $value): bool
        {
            $this->doAdd($value, false);

            return true;
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

        public function key()
        {
            return $this->coll->key();
        }

        public function current()
        {
            return $this->coll->current();
        }

        public function next()
        {
            return $this->coll->next();
        }

        public function contains($element)
        {
            $this->initialize();

            return $this->coll->contains($element);
        }

        public function isEmpty()
        {
            return $this->initialized ? $this->coll->isEmpty() : $this->count() === 0;
        }

        public function containsKey($key)
        {
            $this->initialize();

            return $this->coll->containsKey($key);
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

        public function toArray()
        {
            $this->initialize();

            return $this->coll->toArray();
        }

        public function slice($offset, $length = null)
        {
            $this->initialize();

            return $this->coll->slice($offset, $length);
        }

        public function exists(Closure $p)
        {
            $this->initialize();

            return $this->coll->exists($p);
        }

        public function indexOf($element)
        {
            $this->initialize();

            return $this->coll->indexOf($element);
        }

        public function filter(Closure $p)
        {
            $this->initialize();

            return $this->coll->filter($p);
        }

        public function map(Closure $func)
        {
            $this->initialize();

            return $this->coll->map($func);
        }

        public function partition(Closure $p)
        {
            $this->initialize();

            return $this->coll->partition($p);
        }

        public function forAll(Closure $p)
        {
            $this->initialize();

            return $this->coll->forAll($p);
        }

        /**
         * @phpstan-param Closure(TKey, T):bool $p
         *
         * @phpstan-return T|null
         */
        public function findFirst(Closure $p)
        {
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
            return $this->coll->reduce($func, $initial);
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

        public function set($key, $value)
        {
            $this->doSet($key, $value, false);
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

        /** @return int */
        #[ReturnTypeWillChange]
        public function count()
        {
            // Workaround around not being able to directly count inverse collections anymore
            $this->initialize();

            return $this->coll->count();
        }

        /** @phpstan-return Traversable<TKey, T> */
        #[ReturnTypeWillChange]
        public function getIterator()
        {
            $this->initialize();

            return $this->coll->getIterator();
        }

        /** @param mixed $offset */
        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            $this->initialize();

            return $this->coll->offsetExists($offset);
        }

        /**
         * @param mixed $offset
         *
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

        /** @param mixed $offset */
        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            $this->doRemove($offset, true);
        }
    }
}
