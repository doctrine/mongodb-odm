<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * AutoGenerator generates a native ObjectId
 *
 * @final
 */
class AutoGenerator extends AbstractIdGenerator
{
    public function __construct()
    {
        if (self::class === static::class) {
            return;
        }

        @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
    }

    /** @inheritDoc */
    public function generate(DocumentManager $dm, object $document)
    {
        return new ObjectId();
    }
}
