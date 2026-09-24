<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use InvalidArgumentException;

use function sprintf;

final class InvalidTypeException extends InvalidArgumentException
{
    /** @internal */
    public static function invalidTypeName(string $name): self
    {
        return new self(sprintf('Invalid type specified: "%s"', $name));
    }

    /** @internal */
    public static function invalidServiceType(string $serviceId, string $actualType): self
    {
        return new self(sprintf('Service "%s" must be an instance of "%s", got "%s".', $serviceId, Type::class, $actualType));
    }

    /** @internal */
    public static function invalidTypeClass(string $name, string $class): self
    {
        return new self(sprintf('Type class "%s" registered for type "%s" must be a subclass of "%s".', $class, $name, Type::class));
    }

    /** @internal */
    public static function constructorHasRequiredParameters(string $class): self
    {
        return new self(sprintf('Type class "%s" must not have a constructor with required parameters to be registered by class name. Register an instance of the class instead.', $class));
    }

    /** @internal */
    public static function classNotInstantiable(string $class): self
    {
        return new self(sprintf('Type class "%s" is not instantiable and cannot be registered by class name. Register an instance of the class instead.', $class));
    }
}
