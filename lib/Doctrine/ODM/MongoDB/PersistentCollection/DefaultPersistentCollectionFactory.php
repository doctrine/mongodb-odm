<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Default factory class for persistent collection classes.
 *
 */
final class DefaultPersistentCollectionFactory extends AbstractPersistentCollectionFactory
{
    /**
     * {@inheritdoc}
     */
    protected function createCollectionClass($collectionClass)
    {
        return new $collectionClass();
    }
}
