<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\Persistence\Mapping\ProxyClassNameResolver;

/**
 * @internal
 */
final class CachingClassNameResolver implements ClassNameResolver, ProxyClassNameResolver
{
    /** @var ProxyClassNameResolver */
    private $resolver;

    /** @var array<string, string> */
    private $resolvedNames = [];

    public function __construct(ProxyClassNameResolver $resolver)
    {
        $this->resolver = $resolver;
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
            $this->resolvedNames[$className] = $this->resolver->getRealClass($className);
        }

        return $this->resolvedNames[$className];
    }
}
