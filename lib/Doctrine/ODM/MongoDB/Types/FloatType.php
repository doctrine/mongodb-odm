<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Float type.
 */
class FloatType extends Type implements Incrementable
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (float) $value : null;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function diff($old, $new)
    {
        return $new - $old;
    }
}
