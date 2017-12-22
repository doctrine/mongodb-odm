<?php

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * MongoDB ODM Hydrator Exception
 *
 * @since  1.0
 */
class HydratorException extends MongoDBException
{
    public static function hydratorDirectoryNotWritable()
    {
        return new self('Your hydrator directory must be writable.');
    }

    public static function hydratorDirectoryRequired()
    {
        return new self('You must configure a hydrator directory. See docs for details.');
    }

    public static function hydratorNamespaceRequired()
    {
        return new self('You must configure a hydrator namespace. See docs for details');
    }
}
