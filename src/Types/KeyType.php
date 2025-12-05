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
    use ClosureToPHP;

    /** @return MinKey|MaxKey|null */
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        return $value ? new MaxKey() : new MinKey();
    }

    /** @return int|null */
    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof MaxKey ? 1 : 0;
    }
}
