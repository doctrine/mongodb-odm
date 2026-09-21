<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Registry;

use Countable;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use InvalidArgumentException;
use SplObjectStorage;

use function count;
use function serialize;
use function spl_object_id;
use function sprintf;

/**
 * Tracks every document known to a DocumentManager for the lifetime of that
 * manager (until it is cleared), from two directions:
 *
 *  - by document instance, via {@see ManagedObjectState} (persistence state,
 *    identifier, original data, parent association);
 *  - by class and (serialized) identifier, to find the managed instance for
 *    a given database identity without loading it twice.
 *
 * @internal
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 */
final class DocumentRegistry implements Countable
{
    /** @var SplObjectStorage<object, ManagedObjectState> */
    private SplObjectStorage $objectStates;

    /** @var array<class-string, array<string, object>> */
    private array $identityMap = [];

    public function __construct()
    {
        $this->objectStates = new SplObjectStorage();
    }

    /**
     * Fully establishes a document as managed: records its persistence state
     * and (if given) original data on its ManagedObjectState, reads its
     * identifier off the document itself (or, for documents without an
     * identifier field, a value that uniquely identifies the instance
     * instead), and adds it to the identity map.
     *
     * Returns whether the document was newly added to the identity map (as
     * opposed to already being present there).
     *
     * @param array<string, mixed>|null $originalData
     * @phpstan-param ClassMetadata<T> $class
     *
     * @template T of object
     */
    public function track(ClassMetadata $class, object $document, PersistenceState $state = PersistenceState::New, ?array $originalData = null): bool
    {
        $objectState               = $this->getOrCreateObjectState($document, $state);
        $objectState->state        = $state;
        $objectState->identifier   = $class->identifier ? $class->getIdentifierValue($document) : null;
        $objectState->identifier ??= spl_object_id($document);
        if ($originalData !== null) {
            $objectState->originalData = $originalData;
        }

        return $this->addToIdentityMap($class, $document);
    }

    /**
     * Fully forgets a document: removes its ManagedObjectState and its entry
     * in the identity map, if any.
     *
     * Returns whether the document was removed from the identity map (as
     * opposed to not having been present there).
     *
     * @phpstan-param ClassMetadata<T> $class
     *
     * @template T of object
     */
    public function stopTracking(ClassMetadata $class, object $document): bool
    {
        $wasInIdentityMap = $this->removeFromIdentityMap($class, $document);
        $this->removeObjectState($document);

        return $wasInIdentityMap;
    }

    public function clear(): void
    {
        $this->objectStates = new SplObjectStorage();
        $this->identityMap  = [];
    }

    /**
     * Sets the parent association for a given embedded document.
     *
     * @phpstan-param FieldMapping $mapping
     */
    public function setParentAssociation(object $document, array $mapping, ?object $parent, string $field): void
    {
        $this->getOrCreateObjectState($document)->parentAssociation = new ParentAssociation($mapping, $parent, $field);
    }

    public function getParentAssociation(object $document): ?ParentAssociation
    {
        return $this->getObjectState($document)?->parentAssociation;
    }

    /**
     * Gets the original data of a document. The original data is the data
     * that was present at the time the document was reconstituted from the
     * database, used for calculating changesets at commit time.
     *
     * @return array<string, mixed>
     */
    public function getOriginalDocumentData(object $document): array
    {
        $objectState = $this->getObjectState($document);

        return $objectState !== null ? $objectState->originalData ?? [] : [];
    }

    /** @param array<string, mixed> $data */
    public function setOriginalDocumentData(object $document, array $data): void
    {
        $this->getOrCreateObjectState($document)->originalData = $data;
    }

    public function setOriginalDocumentProperty(object $document, string $property, mixed $value): void
    {
        $this->getOrCreateObjectState($document)->originalData[$property] = $value;
    }

    public function getDocumentIdentifier(object $document): mixed
    {
        return $this->getObjectState($document)?->identifier;
    }

    public function getObjectState(object $document): ?ManagedObjectState
    {
        return $this->objectStates[$document] ?? null;
    }

    /**
     * Note that this creates a ManagedObjectState for the document if none
     * exists yet, without adding it to the identity map or otherwise
     * establishing it as tracked. Prefer {@see track()} unless you have a
     * specific reason to set state on a document ahead of (or without)
     * fully tracking it, e.g. recording a parent association before an
     * embedded document has an identifier of its own.
     */
    public function getOrCreateObjectState(object $document, PersistenceState $state = PersistenceState::New): ManagedObjectState
    {
        return $this->objectStates[$document] ??= new ManagedObjectState($state);
    }

    public function removeObjectState(object $document): void
    {
        unset($this->objectStates[$document]);
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
     * @phpstan-param ClassMetadata<T> $class
     *
     * @phpstan-return T
     *
     * @throws InvalidArgumentException If no document is registered for the given identifier.
     *
     * @template T of object
     */
    public function getById(mixed $id, ClassMetadata $class): object
    {
        $serializedId = $this->getSerializedId($id, $class);

        if (! isset($this->identityMap[$class->name][$serializedId])) {
            throw new InvalidArgumentException(sprintf('No document of class "%s" is registered for the given identifier.', $class->name));
        }

        return $this->identityMap[$class->name][$serializedId];
    }

    /**
     * Tries to get a document by its identifier. If no document is found for
     * the given identifier, FALSE is returned.
     *
     * @phpstan-param ClassMetadata<T> $class
     *
     * @phpstan-return T|false
     *
     * @template T of object
     */
    public function tryGetById(mixed $id, ClassMetadata $class): object|bool
    {
        return $this->identityMap[$class->name][$this->getSerializedId($id, $class)] ?? false;
    }

    /**
     * Checks whether an identifier exists in the identity map.
     */
    public function containsId(mixed $id, string $rootClassName): bool
    {
        return isset($this->identityMap[$rootClassName][serialize($id)]);
    }

    /** @return array<class-string, array<string, object>> */
    public function getIdentityMap(): array
    {
        return $this->identityMap;
    }

    /**
     * The number of documents currently tracked by this registry.
     */
    public function count(): int
    {
        return count($this->objectStates);
    }

    /** @phpstan-param ClassMetadata<object> $class */
    private function getSerializedIdForDocument(ClassMetadata $class, object $document): string
    {
        if (! $class->identifier) {
            return (string) spl_object_id($document);
        }

        return $this->getSerializedId($this->getObjectState($document)?->identifier, $class);
    }

    /** @phpstan-param ClassMetadata<object> $class */
    private function getSerializedId(mixed $id, ClassMetadata $class): string
    {
        return serialize($class->getDatabaseIdentifierValue($id));
    }
}
