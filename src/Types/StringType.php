<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Regex;

/**
 * The String type.
 */
class StringType extends Type
{
    public function convertToDatabaseValue(mixed $value): Regex|string|null
    {
        return $value === null || $value instanceof Regex ? $value : (string) $value;
    }

    public function convertToPHPValue(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    public function closureToMongo(): string
    {
        return '$return = (string) $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = (string) $value;';
    }
}
