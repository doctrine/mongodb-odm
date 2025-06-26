<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use LogicException;

use function sprintf;

/**
 * The Id type.
 */
class CustomIdType extends Type
{
    public function getBSONType(): BsonType
    {
        throw new LogicException(sprintf('Cannot determine BSON type for "%s".', self::class));
    }

    public function convertToDatabaseValue($value)
    {
        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value;
    }

    public function closureToMongo(): string
    {
        return '$return = $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = $value;';
    }
}
