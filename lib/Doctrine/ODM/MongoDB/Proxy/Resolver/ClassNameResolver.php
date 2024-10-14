<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

/** @deprecated Deprecated in favor of Doctrine\Persistence\Mapping\ProxyClassNameResolver */
interface ClassNameResolver
{
    /**
     * Gets the real class name of a class name that could be a proxy.
     *
     * @param class-string $class
     */
    public function getRealClass(string $class): string;
}
