<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use ProxyManager\Inflector\ClassNameInflectorInterface;
use ProxyManager\Proxy\ProxyInterface;

/** @internal */
final class ProxyManagerClassNameResolver implements ClassNameResolver, ProxyClassNameResolver
{
    public function __construct(private Configuration $configuration)
    {
    }

    public function getRealClass(string $class): string
    {
        return $this->resolveClassName($class);
    }

    /**
     * @param class-string<RealClassName>|class-string<ProxyInterface<RealClassName>> $className
     *
     * @return class-string<RealClassName>
     *
     * @phpstan-template RealClassName of object
     */
    public function resolveClassName(string $className): string
    {
        return $this->getClassNameInflector()->getUserClassName($className);
    }

    private function getClassNameInflector(): ClassNameInflectorInterface
    {
        return $this->configuration->getProxyManagerConfiguration()->getClassNameInflector();
    }
}
