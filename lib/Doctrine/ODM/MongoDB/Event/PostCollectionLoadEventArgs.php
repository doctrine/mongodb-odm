<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;

/**
 * Class that holds arguments for postCollectionLoad event.
 *
 * @template TKey of array-key
 * @template T of object
 */
final class PostCollectionLoadEventArgs extends ManagerEventArgs
{
    /** @var PersistentCollectionInterface<TKey, T> */
    private $collection;

    /**
     * @param PersistentCollectionInterface<TKey, T> $collection
     */
    public function __construct(PersistentCollectionInterface $collection, DocumentManager $dm)
    {
        parent::__construct($dm);
        $this->collection = $collection;
    }

    /**
     * Gets collection that was just initialized (loaded).
     *
     * @return PersistentCollectionInterface<TKey, T>
     */
    public function getCollection(): PersistentCollectionInterface
    {
        return $this->collection;
    }
}
