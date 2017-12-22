<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Key type.
 *
 * @since       1.0
 */
class KeyType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        return $value ? new \MongoDB\BSON\MaxKey : new \MongoDB\BSON\MinKey;
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof \MongoDB\BSON\MaxKey ? 1 : 0;
    }
}
