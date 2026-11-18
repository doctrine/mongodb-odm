<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\PropertyAccessors;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\PersistentCollection\DefaultDelegateCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function array_keys;
use function count;
use function current;
use function implode;
use function sprintf;

/**
 * Exposes the managed collection to the ODM while the document holds its
 * custom collection, which stays the same for the whole document lifetime.
 *
 * @internal
 */
final class DelegateCollectionPropertyAccessor implements PropertyAccessor
{
    /** @var array<class-string, ReflectionProperty> */
    private static array $innerProperties = [];

    private readonly ReflectionProperty $innerProperty;
    private DelegateCollectionFactory $collectionFactory;

    /** @param class-string<DelegateCollection<array-key, mixed>> $collectionClass */
    public function __construct(
        private readonly PropertyAccessor $parent,
        private readonly string $collectionClass,
        private readonly string $fieldName,
        DelegateCollectionFactory|null $collectionFactory = null,
    ) {
        $this->innerProperty     = self::resolveInnerProperty($collectionClass);
        $this->collectionFactory = $collectionFactory ?? new DefaultDelegateCollectionFactory();
    }

    public function setCollectionFactory(DelegateCollectionFactory $collectionFactory): void
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function getValue(object $object): mixed
    {
        $value = $this->parent->getValue($object);

        return $value instanceof DelegateCollection ? self::readInnerCollection($value) : $value;
    }

    public function setValue(object $object, mixed $value): void
    {
        if ($value === null) {
            $this->parent->setValue($object, null);

            return;
        }

        if ($value instanceof DelegateCollection) {
            $this->parent->setValue($object, $value);

            return;
        }

        $current = $this->parent->getValue($object);

        if ($current instanceof DelegateCollection && ! self::isOwnedByAnotherAssociation($current, $object, $this->fieldName)) {
            $this->innerProperty->setValue($current, $value);

            return;
        }

        // The factory creates the instance. The default implementation calls the
        // constructor without arguments; a custom factory can inject dependencies.
        $delegate = $this->collectionFactory->create($this->collectionClass);
        $this->innerProperty->setValue($delegate, $value);
        $this->parent->setValue($object, $delegate);
    }

    public function getUnderlyingReflector(): ReflectionProperty
    {
        return $this->parent->getUnderlyingReflector();
    }

    /**
     * Returns the collection held by a custom collection, read from its property
     * so that no user code runs.
     *
     * @param DelegateCollection<array-key, mixed> $delegate
     *
     * @return Collection<array-key, mixed>|null
     */
    public static function readInnerCollection(DelegateCollection $delegate): Collection|null
    {
        $property = self::$innerProperties[$delegate::class] ??= self::resolveInnerProperty($delegate::class);
        $value    = $property->isInitialized($delegate) ? $property->getValue($delegate) : null;

        return $value instanceof Collection ? $value : null;
    }

    /**
     * Resolves the single Collection-typed property holding the managed collection.
     *
     * @param class-string $collectionClass
     *
     * @throws LogicException When the class does not declare exactly one
     *                        writable Collection-typed property.
     */
    public static function resolveInnerProperty(string $collectionClass): ReflectionProperty
    {
        $candidates = [];

        $reflection = new ReflectionClass($collectionClass);

        while ($reflection !== false) {
            foreach ($reflection->getProperties() as $property) {
                if ($property->isStatic() || isset($candidates[$property->getName()])) {
                    continue;
                }

                $type = $property->getType();

                // Exactly Collection: a narrower type such as ArrayCollection cannot hold the PersistentCollection
                if (! $type instanceof ReflectionNamedType || $type->getName() !== Collection::class) {
                    continue;
                }

                $candidates[$property->getName()] = $property;
            }

            $reflection = $reflection->getParentClass();
        }

        if (count($candidates) !== 1) {
            throw new LogicException(sprintf(
                'the class must declare exactly one property typed as %s to hold the underlying collection, %s found%s',
                Collection::class,
                count($candidates),
                $candidates === [] ? '' : ' ($' . implode(', $', array_keys($candidates)) . ')',
            ));
        }

        $property = current($candidates);

        if ($property->isReadOnly()) {
            throw new LogicException(sprintf(
                'the property $%s holding the underlying collection must not be readonly',
                $property->getName(),
            ));
        }

        return $property;
    }

    /**
     * A custom collection assigned from another managed document shares its
     * managed collection: the document needs its own instance.
     *
     * @param DelegateCollection<array-key, mixed> $current
     */
    private static function isOwnedByAnotherAssociation(DelegateCollection $current, object $object, string $fieldName): bool
    {
        $inner = self::readInnerCollection($current);

        if (! $inner instanceof PersistentCollectionInterface) {
            return false;
        }

        $owner = $inner->getOwner();

        if ($owner === null) {
            return false;
        }

        return $owner !== $object || ($inner->getMapping()['fieldName'] ?? null) !== $fieldName;
    }
}
