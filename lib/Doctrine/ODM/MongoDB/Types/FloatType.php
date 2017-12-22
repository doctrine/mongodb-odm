<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Float type.
 *
 * @since       1.0
 */
class FloatType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (float) $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (float) $value : null;
    }

    public function closureToMongo()
    {
        return '$return = (float) $value;';
    }

    public function closureToPHP()
    {
        return '$return = (float) $value;';
    }
}
