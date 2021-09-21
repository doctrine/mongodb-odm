<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use function max;

/**
 * The Int type.
 */
class IntType extends Type implements Incrementable, Versionable
{
    public function convertToDatabaseValue($value)
    {
        return $value !== null ? (int) $value : null;
    }

    public function convertToPHPValue($value)
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

    public function diff($old, $new)
    {
        return $new - $old;
    }

    public function getNextVersion($current)
    {
        return max(1, (int) $current + 1);
    }
}
