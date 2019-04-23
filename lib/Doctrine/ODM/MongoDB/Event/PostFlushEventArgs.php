<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\ObjectManager;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * Provides event arguments for the postFlush event.
 *
 * @final
 */
class PostFlushEventArgs extends ManagerEventArgs
{
    public function __construct(ObjectManager $objectManager)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($objectManager);
    }
}
