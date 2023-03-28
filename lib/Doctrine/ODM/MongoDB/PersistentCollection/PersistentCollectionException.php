<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\ODM\MongoDB\MongoDBException;
use Throwable;

use function sprintf;

/**
 * MongoDB ODM PersistentCollection Exception.
 */
final class PersistentCollectionException extends MongoDBException
{
    public static function directoryNotWritable(): self
    {
        return new self('Your PersistentCollection directory must be writable.');
    }

    public static function directoryRequired(): self
    {
        return new self('You must configure a PersistentCollection directory. See docs for details.');
    }

    public static function namespaceRequired(): self
    {
        return new self('You must configure a PersistentCollection namespace. See docs for details');
    }

    public static function invalidParameterTypeHint(
        string $className,
        string $methodName,
        string $parameterName,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf(
                'The type hint of parameter "%s" in method "%s" in class "%s" is invalid.',
                $parameterName,
                $methodName,
                $className,
            ),
            0,
            $previous,
        );
    }

    public static function invalidReturnTypeHint(string $className, string $methodName, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'The return type of method "%s" in class "%s" is invalid.',
                $methodName,
                $className,
            ),
            0,
            $previous,
        );
    }

    public static function parentClassRequired(string $className, string $methodName): self
    {
        return new self(
            sprintf(
                'The method "%s" in class "%s" defines a parent return type, but the class does not extend any class.',
                $methodName,
                $className,
            ),
        );
    }

    public static function ownerRequiredToLoadCollection(): self
    {
        return new self('Cannot load persistent collection without an owner.');
    }
}
