<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Int64;

/**
 * The Int64 type (long)
 */
class Int64Type extends IntType implements Incrementable, Versionable
{
    public function convertToDatabaseValue($value)
    {
        if ($value instanceof Int64 || $value === null) {
            return $value;
        }

        return new Int64($value);
    }

    public function closureToMongo(): string
    {
        return '$return = new \MongoDB\BSON\Int64($value);';
    }
}
