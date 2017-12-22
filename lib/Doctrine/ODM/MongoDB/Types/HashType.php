<?php

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * The Hash type.
 *
 * @since       1.0
 */
class HashType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value !== null && ! is_array($value)) {
            throw MongoDBException::invalidValueForType('Hash', array('array', 'null'), $value);
        }
        return $value !== null ? (object) $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (array) $value : null;
    }
}
