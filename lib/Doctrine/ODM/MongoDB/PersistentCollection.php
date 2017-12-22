<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionTrait;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * @since 1.0
 */
class PersistentCollection implements PersistentCollectionInterface
{
    use PersistentCollectionTrait;

    /**
     * @param BaseCollection $coll
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     */
    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow)
    {
        $this->coll = $coll;
        $this->dm = $dm;
        $this->uow = $uow;
    }
}
