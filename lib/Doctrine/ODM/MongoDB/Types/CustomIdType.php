<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Id type.
 *
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
