<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Decimal128;
use function bcsub;

class Decimal128Type extends Type implements Incrementable
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

    public function diff($old, $new)
    {
        return bcsub($new, $old);
    }
}
