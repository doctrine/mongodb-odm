<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use JsonException;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Class for exception when encountering proxy object that has
 * an identifier that does not exist in the database.
 */
final class DocumentNotFoundException extends MongoDBException
{
    /** @param mixed $identifier */
    public static function documentNotFound(string $className, $identifier): self
    {
        try {
            $id = json_encode($identifier, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
        }

        return new self(sprintf(
            'The "%s" document with identifier %s could not be found.',
            $className,
            $id ?? false,
        ), 0, $e ?? null);
    }
}
