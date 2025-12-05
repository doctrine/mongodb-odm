<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\MongoDBException;

use function array_values;
use function is_array;

/**
 * The Collection type.
 */
class CollectionType extends Type
{
    /** @return list<mixed>|null */
    public function convertToDatabaseValue($value)
    {
        if ($value !== null && ! is_array($value)) {
            throw MongoDBException::invalidValueForType('Collection', ['array', 'null'], $value);
        }

        return $value !== null ? array_values($value) : null;
    }

    /** @return list<mixed>|null */
    public function convertToPHPValue($value)
    {
        return $value !== null ? array_values($value) : null;
    }

    public function closureToPHP(): string
    {
        return '$return = array_values($value);';
    }
}
