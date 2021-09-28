<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Factory;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use ProxyManager\Proxy\GhostObjectInterface;

interface ProxyFactory
{
    /**
     * @param ClassMetadata<object>[] $classes
     */
    public function generateProxyClasses(array $classes): int;

    /**
     * Gets a reference proxy instance for the entity of the given type and identified by
     * the given identifier.
     *
     * @param mixed $identifier
     * @psalm-param ClassMetadata<T> $metadata
     *
     * @psalm-return T&GhostObjectInterface<T>
     *
     * @template T of object
     */
    public function getProxy(ClassMetadata $metadata, $identifier): GhostObjectInterface;
}
