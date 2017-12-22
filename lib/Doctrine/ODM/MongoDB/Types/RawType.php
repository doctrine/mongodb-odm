<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * Raw data type.
 *
 * @since       1.0
 */
class RawType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value;
    }

    public function closureToMongo()
    {
        return '$return = $value;';
    }

    public function closureToPHP()
    {
        return '$return = $value;';
    }
}
