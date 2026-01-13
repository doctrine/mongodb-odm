<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Int64;

use function max;

/**
 * The Int64 type (long)
 */
class Int64Type extends Type implements Incrementable, Versionable
{
    public function convertToDatabaseValue(mixed $value): ?Int64
    {
        if ($value instanceof Int64 || $value === null) {
            return $value;
        }

        return new Int64($value);
    }

    public function convertToPHPValue(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function closureToPHP(): string
    {
        return '$return = (int) $value;';
    }

    public function diff(mixed $old, mixed $new): int
    {
        return (int) ($new - $old);
    }

    public function getNextVersion(mixed $current): int
    {
        return max(1, (int) $current + 1);
    }
}
