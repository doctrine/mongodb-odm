<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Creates the custom collections that delegate to a managed collection.
 *
 * The default implementation calls the constructor without arguments. Use a
 * custom factory when the constructor requires dependencies, for instance
 * services from a dependency injection container.
 */
interface DelegateCollectionFactory
{
    /**
     * @param class-string<DelegateCollection<array-key, mixed>> $collectionClass
     *
     * @return DelegateCollection<array-key, mixed>
     */
    public function create(string $collectionClass): DelegateCollection;
}
