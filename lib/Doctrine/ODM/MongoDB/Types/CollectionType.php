<?php

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * The Collection type.
 *
 * @since       1.0
 */
class CollectionType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value !== null && ! is_array($value)) {
            throw MongoDBException::invalidValueForType('Collection', array('array', 'null'), $value);
        }
        return $value !== null ? array_values($value) : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? array_values($value) : null;
    }
}
