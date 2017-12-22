<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Int type.
 *
 * @since       1.0
 */
class IntType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (integer) $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (integer) $value : null;
    }

    public function closureToMongo()
    {
        return '$return = (int) $value;';
    }

    public function closureToPHP()
    {
        return '$return = (int) $value;';
    }
}
