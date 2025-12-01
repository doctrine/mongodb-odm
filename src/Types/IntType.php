<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use function max;

/**
 * The Int type.
 */
class IntType extends Type implements Incrementable, Versionable
{
    public function convertToDatabaseValue(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function closureToMongo(): string
    {
        return '$return = (int) $value;';
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
