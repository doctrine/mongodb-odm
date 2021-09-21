<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Boolean type.
 */
class BooleanType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (bool) $value : null;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (bool) $value : null;
    }

    public function closureToMongo(): string
    {
        return '$return = (bool) $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = (bool) $value;';
    }
}
