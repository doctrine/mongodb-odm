<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\ObjectId;

/**
 * The ObjectId type.
 */
class ObjectIdType extends Type implements Versionable
{
    public function convertToDatabaseValue(mixed $value): ?ObjectId
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof ObjectId) {
            $value = new ObjectId($value);
        }

        return $value;
    }

    public function convertToPHPValue(mixed $value): ?string
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

    public function getNextVersion(mixed $current): ObjectId
    {
        return new ObjectId();
    }
}
