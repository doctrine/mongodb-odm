<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\MaxKey;
use MongoDB\BSON\MinKey;

/**
 * The Key type.
 */
class KeyType extends Type
{
    public function convertToDatabaseValue(mixed $value): MinKey|MaxKey|null
    {
        if ($value === null) {
            return null;
        }

        return $value ? new MaxKey() : new MinKey();
    }

    public function convertToPHPValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof MaxKey ? 1 : 0;
    }
}
