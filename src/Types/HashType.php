<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\MongoDBException;

use function is_array;

/**
 * The Hash type.
 */
class HashType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?object
    {
        if ($value !== null && ! is_array($value)) {
            throw MongoDBException::invalidValueForType('Hash', ['array', 'null'], $value);
        }

        return $value !== null ? (object) $value : null;
    }

    /** @return array<string, mixed>|null */
    public function convertToPHPValue(mixed $value): ?array
    {
        return $value !== null ? (array) $value : null;
    }
}
