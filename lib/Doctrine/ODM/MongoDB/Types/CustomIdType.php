<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Id type.
 *
 * @since       1.0
 */
class CustomIdType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? $value : null;
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
