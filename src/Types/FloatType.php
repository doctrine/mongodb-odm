<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Float type.
 */
class FloatType extends Type implements Incrementable
{
    /** @return float|null */
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (float) $value : null;
    }

    /** @return float|null */
    public function convertToPHPValue($value)
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

    /** @return float|null */
    public function diff($old, $new)
    {
        return (float) ($new - $old);
    }
}
