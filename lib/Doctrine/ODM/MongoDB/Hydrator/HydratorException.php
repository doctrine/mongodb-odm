<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

use BackedEnum;
use Doctrine\ODM\MongoDB\MongoDBException;
use ValueError;

use function sprintf;

/**
 * MongoDB ODM Hydrator Exception
 */
final class HydratorException extends MongoDBException
{
    public static function hydratorDirectoryNotWritable(): self
    {
        return new self('Your hydrator directory must be writable.');
    }

    public static function hydratorDirectoryRequired(): self
    {
        return new self('You must configure a hydrator directory. See docs for details.');
    }

    public static function hydratorNamespaceRequired(): self
    {
        return new self('You must configure a hydrator namespace. See docs for details');
    }

    public static function associationTypeMismatch(string $className, string $fieldName, string $expectedType, string $actualType): self
    {
        return new self(sprintf(
            'Expected association for field "%s" in document of type "%s" to be of type "%s", "%s" received.',
            $fieldName,
            $className,
            $expectedType,
            $actualType
        ));
    }

    /**
     * @param int|string $key
     */
    public static function associationItemTypeMismatch(string $className, string $fieldName, $key, string $expectedType, string $actualType): self
    {
        return new self(sprintf(
            'Expected association item with key "%s" for field "%s" in document of type "%s" to be of type "%s", "%s" received.',
            $key,
            $fieldName,
            $className,
            $expectedType,
            $actualType
        ));
    }

    /**
     * @param class-string             $className
     * @param class-string<BackedEnum> $enumType
     */
    public static function invalidEnumValue(
        string $className,
        string $fieldName,
        string $value,
        string $enumType,
        ValueError $previous
    ): self {
        return new self(sprintf(
            'Trying to hydrate an enum property "%s::%s", but "%s" is not listed as one of "%s" cases',
            $className,
            $fieldName,
            $value,
            $enumType
        ), 0, $previous);
    }
}
