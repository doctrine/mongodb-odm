<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\Persistence\Mapping\ProxyClassNameResolver;

/** @internal */
final class CachingClassNameResolver implements ClassNameResolver, ProxyClassNameResolver
{
    /** @var array<class-string, string> */
    private array $resolvedNames = [];

    public function __construct(private ProxyClassNameResolver $resolver)
    {
    }

    /**
     * Gets the real class name of a class name that could be a proxy.
     */
    public function getRealClass(string $class): string
    {
        return $this->resolveClassName($class);
    }

    public function resolveClassName(string $className): string
    {
        if (! isset($this->resolvedNames[$className])) {
            $this->resolvedNames[$className] = $this->resolver->resolveClassName($className);
        }

        return $this->resolvedNames[$className];
    }
}
