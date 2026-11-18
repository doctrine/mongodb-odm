<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Creates custom collections by calling their constructor without arguments.
 */
final class DefaultDelegateCollectionFactory implements DelegateCollectionFactory
{
    public function create(string $collectionClass): DelegateCollection
    {
        return new $collectionClass();
    }
}
