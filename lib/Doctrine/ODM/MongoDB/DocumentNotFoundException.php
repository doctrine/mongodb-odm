<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Throwable;
use const E_USER_DEPRECATED;
use function json_encode;
use function sprintf;
use function trigger_error;

/**
 * Class for exception when encountering proxy object that has
 * an identifier that does not exist in the database.
 *
 * @final
 */
class DocumentNotFoundException extends MongoDBException
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($message, $code, $previous);
    }

    public static function documentNotFound(string $className, $identifier) : self
    {
        return new self(sprintf(
            'The "%s" document with identifier %s could not be found.',
            $className,
            json_encode($identifier)
        ));
    }
}
