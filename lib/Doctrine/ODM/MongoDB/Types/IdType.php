<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException;

/**
 * The Id type.
 */
class IdType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof ObjectId) {
            try {
                $value = new ObjectId((string) $value);
            } catch (InvalidArgumentException) {
                $value = new ObjectId();
            }
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value instanceof ObjectId ? (string) $value : $value;
    }

    public function closureToMongo(): string
    {
        return '$return = new MongoDB\BSON\ObjectId($value);';
    }

    public function closureToPHP(): string
    {
        return '$return = $value instanceof \MongoDB\BSON\ObjectId ? (string) $value : $value;';
    }
}
