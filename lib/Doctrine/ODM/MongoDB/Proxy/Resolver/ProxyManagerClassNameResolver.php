<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use ProxyManager\Inflector\ClassNameInflectorInterface;

/**
 * @internal
 */
final class ProxyManagerClassNameResolver implements ClassNameResolver, ProxyClassNameResolver
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getRealClass(string $class): string
    {
        return $this->resolveClassName($class);
    }

    public function resolveClassName(string $className): string
    {
        return $this->getClassNameInflector()->getUserClassName($className);
    }

    private function getClassNameInflector(): ClassNameInflectorInterface
    {
        return $this->configuration->getProxyManagerConfiguration()->getClassNameInflector();
    }
}
