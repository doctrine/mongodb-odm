<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use InvalidArgumentException;
use Throwable;

use function sprintf;

final class InvalidTypeException extends InvalidArgumentException
{
    public static function invalidTypeName(string $name): self
    {
        return new self(sprintf('Invalid type specified: "%s"', $name));
    }

    public static function serviceNotFound(string $name, string $serviceId, ?Throwable $previous = null): self
    {
        return new self(sprintf('Service "%s" registered for type "%s" was not found in the container.', $serviceId, $name), 0, $previous);
    }

    public static function invalidServiceType(string $name, string $serviceId, string $actualType): self
    {
        return new self(sprintf('Service "%s" registered for type "%s" must be an instance of "%s", got "%s".', $serviceId, $name, Type::class, $actualType));
    }

    public static function invalidTypeClass(string $name, string $class): self
    {
        return new self(sprintf('Type class "%s" registered for type "%s" must be a subclass of "%s".', $class, $name, Type::class));
    }

    public static function constructorHasRequiredParameters(string $class): self
    {
        return new self(sprintf('Type class "%s" must not have a constructor with required parameters to be registered by class name. Register an instance of the class instead.', $class));
    }
}
