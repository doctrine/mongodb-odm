<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The ObjectId type.
 *
 * @since       1.0
 */
class ObjectIdType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        if ( ! $value instanceof \MongoDB\BSON\ObjectId) {
            $value = new \MongoDB\BSON\ObjectId($value);
        }
        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (string) $value : null;
    }

    public function closureToMongo()
    {
        return '$return = new MongoDB\BSON\ObjectId($value);';
    }

    public function closureToPHP()
    {
        return '$return = (string) $value;';
    }
}
