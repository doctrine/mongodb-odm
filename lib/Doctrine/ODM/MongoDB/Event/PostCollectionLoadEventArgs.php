<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * Class that holds arguments for postCollectionLoad event.
 *
 * @final
 */
class PostCollectionLoadEventArgs extends ManagerEventArgs
{
    /** @var PersistentCollectionInterface */
    private $collection;

    public function __construct(PersistentCollectionInterface $collection, DocumentManager $dm)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($dm);
        $this->collection = $collection;
    }

    /**
     * Gets collection that was just initialized (loaded).
     */
    public function getCollection() : PersistentCollectionInterface
    {
        return $this->collection;
    }
}
