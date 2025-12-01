<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Float type.
 */
class FloatType extends Type implements Incrementable
{
    public function convertToDatabaseValue(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    public function closureToMongo(): string
    {
        return '$return = (float) $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = (float) $value;';
    }

    public function diff(mixed $old, mixed $new): ?float
    {
        return (float) ($new - $old);
    }
}
