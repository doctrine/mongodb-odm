<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\PropertyAccessors;

use Doctrine\ODM\MongoDB\Proxy\InternalProxy;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionProperty;

use function ltrim;

/** @internal */
class ObjectCastPropertyAccessor implements PropertyAccessor
{
    /** @param class-string $class */
    public static function fromNames(string $class, string $name): self
    {
        $reflectionProperty = new ReflectionProperty($class, $name);

        $key = match (true) {
            $reflectionProperty->isPrivate() => "\0" . ltrim($class, '\\') . "\0" . $name,
            $reflectionProperty->isProtected() => "\0*\0" . $name,
            default => $name,
        };

        return new self($reflectionProperty, $key);
    }

    public static function fromReflectionProperty(ReflectionProperty $reflectionProperty): self
    {
        $name = $reflectionProperty->getName();

        $key = match (true) {
            $reflectionProperty->isPrivate() => "\0" . ltrim($reflectionProperty->getDeclaringClass()->getName(), '\\') . "\0" . $name,
            $reflectionProperty->isProtected() => "\0*\0" . $name,
            default => $name,
        };

        return new self($reflectionProperty, $key);
    }

    private function __construct(private ReflectionProperty $reflectionProperty, private string $key)
    {
    }

    public function setValue(object $object, mixed $value): void
    {
        if ($object instanceof InternalProxy && ! $object->__isInitialized()) {
            $object->__setInitialized(true);
            $this->reflectionProperty->setValue($object, $value);
            $object->__setInitialized(false);
        } elseif ($object instanceof GhostObjectInterface && ! $object->isProxyInitialized()) {
            $initializer = $object->getProxyInitializer();
            $object->setProxyInitializer();
            $this->reflectionProperty->setValue($object, $value);
            $object->setProxyInitializer($initializer);
        } else {
            $this->reflectionProperty->setValue($object, $value);
        }
    }

    public function getValue(object $object): mixed
    {
        return ((array) $object)[$this->key] ?? null;
    }

    public function getUnderlyingReflector(): ReflectionProperty
    {
        return $this->reflectionProperty;
    }
}
