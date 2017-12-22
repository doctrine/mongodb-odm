<?php

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\Driver\Exception\InvalidArgumentException;

/**
 * The Id type.
 *
 * @since       1.0
 */
class IdType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        if ( ! $value instanceof \MongoDB\BSON\ObjectId) {
            try {
                $value = new \MongoDB\BSON\ObjectId($value);
            } catch (InvalidArgumentException $e) {
                $value = new \MongoDB\BSON\ObjectId();
            }
        }
        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value instanceof \MongoDB\BSON\ObjectId ? (string) $value : $value;
    }

    public function closureToMongo()
    {
        return '$return = new MongoDB\BSON\ObjectId($value);';
    }

    public function closureToPHP()
    {
        return '$return = $value instanceof \MongoDB\BSON\ObjectId ? (string) $value : $value;';
    }
}
