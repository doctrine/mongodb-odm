<?php

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Interface for persistent collection classes factory.
 *
 * @since 1.1
 */
interface PersistentCollectionFactory
{
    /**
     * Creates specified persistent collection to work with given collection class.
     *
     * @param DocumentManager $dm DocumentManager with which collection is associated
     * @param array $mapping Mapping of field holding collection
     * @param BaseCollection $coll Collection to be decorated
     * @return PersistentCollectionInterface
     */
    public function create(DocumentManager $dm, array $mapping, BaseCollection $coll = null);
}
