<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\ODM\MongoDB\MongoDBException;
use Throwable;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * MongoDB ODM Hydrator Exception
 *
 * @final
 */
class HydratorException extends MongoDBException
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($message, $code, $previous);
    }

    public static function hydratorDirectoryNotWritable() : self
    {
        return new self('Your hydrator directory must be writable.');
    }

    public static function hydratorDirectoryRequired() : self
    {
        return new self('You must configure a hydrator directory. See docs for details.');
    }

    public static function hydratorNamespaceRequired() : self
    {
        return new self('You must configure a hydrator namespace. See docs for details');
    }
}
