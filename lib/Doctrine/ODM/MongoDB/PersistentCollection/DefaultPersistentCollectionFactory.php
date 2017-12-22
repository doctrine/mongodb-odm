<?php

namespace Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Default factory class for persistent collection classes.
 *
 * @since 1.1
 */
final class DefaultPersistentCollectionFactory extends AbstractPersistentCollectionFactory
{
    /**
     * {@inheritdoc}
     */
    protected function createCollectionClass($collectionClass)
    {
        return new $collectionClass;
    }
}
