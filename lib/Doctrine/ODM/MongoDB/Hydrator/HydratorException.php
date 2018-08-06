<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * MongoDB ODM Hydrator Exception
 */
class HydratorException extends MongoDBException
{
    public static function hydratorDirectoryNotWritable(): self
    {
        return new self('Your hydrator directory must be writable.');
    }

    public static function hydratorDirectoryRequired(): self
    {
        return new self('You must configure a hydrator directory. See docs for details.');
    }

    public static function hydratorNamespaceRequired(): self
    {
        return new self('You must configure a hydrator namespace. See docs for details');
    }
}
