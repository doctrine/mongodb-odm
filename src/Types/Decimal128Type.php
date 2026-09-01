<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Decimal128;

use function bcadd;
use function bcsub;

class Decimal128Type extends Type implements Incrementable, Versionable
{
    public function convertToDatabaseValue(mixed $value): ?Decimal128
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Decimal128) {
            $value = new Decimal128($value);
        }

        return $value;
    }

    public function convertToPHPValue(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    public function diff(mixed $old, mixed $new): string
    {
        return bcsub($new, $old);
    }

    public function getNextVersion(mixed $current): string
    {
        if ($current === null) {
            return '1';
        }

        return bcadd($current, '1');
    }
}
