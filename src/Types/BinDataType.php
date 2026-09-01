<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for generic data.
 */
class BinDataType extends Type
{
    /**
     * Data type for binary data
     *
     * @see http://bsonspec.org/#/specification
     */
    protected int $binDataType = Binary::TYPE_GENERIC;

    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Binary) {
            return new Binary($value, $this->binDataType);
        }

        if ($value->getType() !== $this->binDataType) {
            return new Binary($value->getData(), $this->binDataType);
        }

        return $value;
    }

    public function convertToPHPValue(mixed $value): mixed
    {
        return $value !== null ? ($value instanceof Binary ? $value->getData() : $value) : null;
    }

    public function closureToPHP(): string
    {
        return '$return = $value !== null ? ($value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value) : null;';
    }
}
