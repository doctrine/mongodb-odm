<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Interface for persistent collection classes factory.
 *
 * @psalm-import-type FieldMapping from ClassMetadata
 */
interface PersistentCollectionFactory
{
    /**
     * Creates specified persistent collection to work with given collection class.
     *
     * @psalm-param FieldMapping $mapping
     * @psalm-param BaseCollection<array-key, object>|null $coll
     *
     * @psalm-return PersistentCollectionInterface<array-key, object>
     */
    public function create(DocumentManager $dm, array $mapping, ?BaseCollection $coll = null): PersistentCollectionInterface;
}
