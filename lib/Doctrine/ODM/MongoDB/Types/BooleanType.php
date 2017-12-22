<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Boolean type.
 *
 * @since       1.0
 */
class BooleanType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (boolean) $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (boolean) $value : null;
    }

    public function closureToMongo()
    {
        return '$return = (bool) $value;';
    }

    public function closureToPHP()
    {
        return '$return = (bool) $value;';
    }
}
