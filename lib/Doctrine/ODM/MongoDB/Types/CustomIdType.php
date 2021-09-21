<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Id type.
 */
class CustomIdType extends Type
{
    public function convertToDatabaseValue($value)
    {
        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value;
    }

    public function closureToMongo(): string
    {
        return '$return = $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = $value;';
    }
}
