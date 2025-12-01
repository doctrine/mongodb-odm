<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * Raw data type.
 */
class RawType extends Type
{
    public function convertToDatabaseValue(mixed $value): mixed
    {
        return $value;
    }

    public function convertToPHPValue(mixed $value): mixed
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
