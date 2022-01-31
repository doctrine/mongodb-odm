<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Abstract factory for creating persistent collection classes.
 */
abstract class AbstractPersistentCollectionFactory implements PersistentCollectionFactory
{
    public function create(DocumentManager $dm, array $mapping, ?BaseCollection $coll = null): PersistentCollectionInterface
    {
        if ($coll === null) {
            $coll = ! empty($mapping['collectionClass'])
                ? $this->createCollectionClass($mapping['collectionClass'])
                : new ArrayCollection();
        }

        if (empty($mapping['collectionClass'])) {
            return new PersistentCollection($coll, $dm, $dm->getUnitOfWork());
        }

        $className = $dm->getConfiguration()->getPersistentCollectionGenerator()
            ->loadClass($mapping['collectionClass'], $dm->getConfiguration()->getAutoGeneratePersistentCollectionClasses());

        return new $className($coll, $dm, $dm->getUnitOfWork());
    }

    /**
     * Creates instance of collection class to be wrapped by PersistentCollection.
     *
     * @param string $collectionClass FQCN of class to instantiate
     *
     * @return BaseCollection
     */
    abstract protected function createCollectionClass(string $collectionClass): BaseCollection;
}
