<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;

use function assert;

/**
 * Abstract factory for creating persistent collection classes.
 */
abstract class AbstractPersistentCollectionFactory implements PersistentCollectionFactory
{
    /**
     * @param BaseCollection<TKey, T>|null $coll
     *
     * @return PersistentCollectionInterface<TKey, T>
     *
     * @template TKey of array-key
     * @template T of object
     *
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function create(DocumentManager $dm, array $mapping, ?BaseCollection $coll = null): PersistentCollectionInterface
    {
        if ($coll === null) {
            /** @var BaseCollection<TKey, T> $coll */
            $coll = ! empty($mapping['collectionClass'])
                ? $this->createCollectionClass($mapping['collectionClass'])
                : new ArrayCollection();
        }

        if (empty($mapping['collectionClass'])) {
            return new PersistentCollection($coll, $dm, $dm->getUnitOfWork());
        }

        $className = $dm->getConfiguration()->getPersistentCollectionGenerator()
            ->loadClass($mapping['collectionClass'], $dm->getConfiguration()->getAutoGeneratePersistentCollectionClasses());

        $persistentCollection = new $className($coll, $dm, $dm->getUnitOfWork());

        assert($persistentCollection instanceof PersistentCollectionInterface);

        return $persistentCollection;
    }

    /**
     * Creates instance of collection class to be wrapped by PersistentCollection.
     *
     * @param string $collectionClass FQCN of class to instantiate
     * @psalm-param class-string<T> $collectionClass
     *
     * @return T
     *
     * @template T of BaseCollection
     */
    abstract protected function createCollectionClass(string $collectionClass): BaseCollection;
}
