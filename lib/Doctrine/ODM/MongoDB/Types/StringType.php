<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The String type.
 *
 * @since       1.0
 */
class StringType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return ($value === null || $value instanceof \MongoDB\BSON\Regex) ? $value : (string) $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (string) $value : null;
    }

    public function closureToMongo()
    {
        return '$return = (string) $value;';
    }

    public function closureToPHP()
    {
        return '$return = (string) $value;';
    }
}
