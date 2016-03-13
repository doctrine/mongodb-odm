<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;

/**
 * Class that holds arguments for postCollectionLoad event.
 *
 * @since 1.1
 */
class PostCollectionLoadEventArgs extends ManagerEventArgs
{
    /**
     * @var PersistentCollectionInterface
     */
    private $collection;

    /**
     * @param PersistentCollectionInterface $collection
     * @param DocumentManager $dm
     */
    public function __construct(PersistentCollectionInterface $collection, DocumentManager $dm)
    {
        parent::__construct($dm);
        $this->collection = $collection;
    }

    /**
     * Gets collection that was just initialized (loaded).
     *
     * @return PersistentCollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
