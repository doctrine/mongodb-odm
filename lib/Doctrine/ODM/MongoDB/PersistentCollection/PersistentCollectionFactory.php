<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManagerInterface;

/**
 * Interface for persistent collection classes factory.
 */
interface PersistentCollectionFactory
{
    /**
     * Creates specified persistent collection to work with given collection class.
     */
    public function create(DocumentManagerInterface $dm, array $mapping, ?BaseCollection $coll = null) : PersistentCollectionInterface;
}
