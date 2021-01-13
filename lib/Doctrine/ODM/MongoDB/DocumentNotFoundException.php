<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use function json_encode;
use function sprintf;

/**
 * Class for exception when encountering proxy object that has
 * an identifier that does not exist in the database.
 */
final class DocumentNotFoundException extends MongoDBException
{
    public static function documentNotFound(string $className, $identifier): self
    {
        return new self(sprintf(
            'The "%s" document with identifier %s could not be found.',
            $className,
            json_encode($identifier)
        ));
    }
}
