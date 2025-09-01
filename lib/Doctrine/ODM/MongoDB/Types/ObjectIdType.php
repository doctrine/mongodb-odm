<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\ObjectId;

/**
 * The ObjectId type.
 */
class ObjectIdType extends Type implements Versionable
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof ObjectId) {
            $value = new ObjectId($value);
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? (string) $value : null;
    }

    public function closureToMongo(): string
    {
        return '$return = new MongoDB\BSON\ObjectId($value);';
    }

    public function closureToPHP(): string
    {
        return '$return = (string) $value;';
    }

    public function getNextVersion($current): ObjectId
    {
        return new ObjectId();
    }
}
