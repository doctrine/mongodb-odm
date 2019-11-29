<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Decimal128;

class Decimal128Type extends Type
{
    use ClosureToPHP;

    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        if (! $value instanceof Decimal128) {
            $value = new Decimal128($value);
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (string) $value : null;
    }
}
