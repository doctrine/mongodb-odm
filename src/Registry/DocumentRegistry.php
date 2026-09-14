<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Registry;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use SplObjectStorage;

use function count;
use function serialize;
use function spl_object_id;

/**
 * Tracks every document known to a DocumentManager for the lifetime of that
 * manager (until it is cleared), from two directions:
 *
 *  - by document instance, via {@see ManagedObjectState} (persistence state,
 *    identifier, original data, parent association);
 *  - by class and (serialized) identifier, to find the managed instance for
 *    a given database identity without loading it twice.
 *
 * @internal This class is not part of the public API and is subject to change.
 */
final class DocumentRegistry
{
    /** @var SplObjectStorage<object, ManagedObjectState> */
    private SplObjectStorage $objectStates;

    /** @var array<class-string, array<string, object>> */
    private array $identityMap = [];

    public function __construct()
    {
        $this->objectStates = new SplObjectStorage();
    }

    public function getObjectState(object $document): ?ManagedObjectState
    {
        return $this->objectStates[$document] ?? null;
    }

    public function getOrCreateObjectState(object $document, PersistenceState $state = PersistenceState::New): ManagedObjectState
    {
        if (! isset($this->objectStates[$document])) {
            $this->objectStates[$document] = new ManagedObjectState($state);
        }

        return $this->objectStates[$document];
    }

    public function removeObjectState(object $document): void
    {
        unset($this->objectStates[$document]);
    }

    public function clear(): void
    {
        $this->objectStates = new SplObjectStorage();
        $this->identityMap  = [];
    }

    /**
     * Registers a document in the identity map.
     *
     * Note that documents in a hierarchy are registered with the class name of
     * the root document. Identifiers are serialized before being used as array
     * keys to allow differentiation of equal, but not identical, values.
     *
     * @phpstan-param ClassMetadata<T> $class
     *
     * @template T of object
     */
    public function addToIdentityMap(ClassMetadata $class, object $document): bool
    {
        $id = $this->getSerializedIdForDocument($class, $document);

        if (isset($this->identityMap[$class->name][$id])) {
            return false;
        }

        $this->identityMap[$class->name][$id] = $document;

        return true;
    }

    /**
     * Removes a document from the identity map.
     *
     * @phpstan-param ClassMetadata<T> $class
     *
     * @template T of object
     */
    public function removeFromIdentityMap(ClassMetadata $class, object $document): bool
    {
        $id = $this->getSerializedIdForDocument($class, $document);

        if (! isset($this->identityMap[$class->name][$id])) {
            return false;
        }

        unset($this->identityMap[$class->name][$id]);

        return true;
    }

    /**
     * Checks whether a document is registered in the identity map.
     *
     * @phpstan-param ClassMetadata<T> $class
     *
     * @template T of object
     */
    public function isInIdentityMap(ClassMetadata $class, object $document): bool
    {
        if ($this->getObjectState($document)?->identifier === null) {
            return false;
        }

        return isset($this->identityMap[$class->name][$this->getSerializedIdForDocument($class, $document)]);
    }

    /**
     * Gets a document in the identity map by its identifier.
     *
     * @param mixed $id Document identifier
     * @phpstan-param ClassMetadata<T> $class
     *
     * @phpstan-return T
     *
     * @template T of object
     */
    public function getById($id, ClassMetadata $class): object
    {
        return $this->identityMap[$class->name][$this->getSerializedId($class, $id)];
    }

    /**
     * Tries to get a document by its identifier. If no document is found for
     * the given identifier, FALSE is returned.
     *
     * @param mixed $id Document identifier
     * @phpstan-param ClassMetadata<T> $class
     *
     * @return mixed The found document or FALSE.
     * @phpstan-return T|false
     *
     * @template T of object
     */
    public function tryGetById($id, ClassMetadata $class)
    {
        return $this->identityMap[$class->name][$this->getSerializedId($class, $id)] ?? false;
    }

    /**
     * Checks whether an identifier exists in the identity map.
     *
     * @param mixed $id
     */
    public function containsId($id, string $rootClassName): bool
    {
        return isset($this->identityMap[$rootClassName][serialize($id)]);
    }

    /** @return array<class-string, array<string, object>> */
    public function getIdentityMap(): array
    {
        return $this->identityMap;
    }

    /**
     * The number of documents currently tracked in the identity map.
     */
    public function size(): int
    {
        $count = 0;
        foreach ($this->identityMap as $documentSet) {
            $count += count($documentSet);
        }

        return $count;
    }

    /** @phpstan-param ClassMetadata<object> $class */
    private function getSerializedIdForDocument(ClassMetadata $class, object $document): string
    {
        if (! $class->identifier) {
            return (string) spl_object_id($document);
        }

        return $this->getSerializedId($class, $this->getObjectState($document)?->identifier);
    }

    /**
     * @param mixed $id
     * @phpstan-param ClassMetadata<object> $class
     */
    private function getSerializedId(ClassMetadata $class, $id): string
    {
        return serialize($class->getDatabaseIdentifierValue($id));
    }
}
