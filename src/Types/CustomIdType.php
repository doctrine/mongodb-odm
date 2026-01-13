<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Id type.
 */
class CustomIdType extends Type
{
    public function convertToDatabaseValue(mixed $value): mixed
    {
        return $value;
    }

    public function convertToPHPValue(mixed $value): mixed
    {
        return $value;
    }

    public function closureToPHP(): string
    {
        return '$return = $value;';
    }
}
