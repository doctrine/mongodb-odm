<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;

use function is_a;
use function is_string;

/**
 * Abstract factory for creating persistent collection classes.
 */
abstract class AbstractPersistentCollectionFactory implements PersistentCollectionFactory
{
    public function create(DocumentManager $dm, array $mapping, ?BaseCollection $coll = null): PersistentCollectionInterface
    {
        $collectionClass = $mapping['collectionClass'] ?? null;
        $isDelegated     = is_string($collectionClass) && is_a($collectionClass, DelegateCollection::class, true);

        if ($coll === null) {
            $coll = $isDelegated || empty($collectionClass)
                ? new ArrayCollection()
                : $this->createCollectionClass($collectionClass);
        }

        if ($isDelegated || empty($collectionClass)) {
            return new PersistentCollection($coll, $dm, $dm->getUnitOfWork());
        }

        $className = $dm->getConfiguration()->getPersistentCollectionGenerator()
            ->loadClass($collectionClass, $dm->getConfiguration()->getAutoGeneratePersistentCollectionClasses());

        return new $className($coll, $dm, $dm->getUnitOfWork());
    }

    /**
     * Creates instance of collection class to be wrapped by PersistentCollection.
     *
     * @param string $collectionClass FQCN of class to instantiate
     *
     * @return BaseCollection<array-key, object>
     */
    abstract protected function createCollectionClass(string $collectionClass): BaseCollection;
}
