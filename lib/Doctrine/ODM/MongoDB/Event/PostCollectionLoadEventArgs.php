<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;

/**
 * Class that holds arguments for postCollectionLoad event.
 *
 * @final
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
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in doctrine/mongodb-odm 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
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
