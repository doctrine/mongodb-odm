<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionTrait;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * @final
 */
class PersistentCollection implements PersistentCollectionInterface
{
    use PersistentCollectionTrait;

    /**
     * @param BaseCollection $coll
     */
    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        $this->coll = $coll;
        $this->dm   = $dm;
        $this->uow  = $uow;
    }
}
