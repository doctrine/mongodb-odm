<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Boolean type.
 */
class BooleanType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?bool
    {
        return $value !== null ? (bool) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?bool
    {
        return $value !== null ? (bool) $value : null;
    }

    public function closureToPHP(): string
    {
        return '$return = (bool) $value;';
    }
}
