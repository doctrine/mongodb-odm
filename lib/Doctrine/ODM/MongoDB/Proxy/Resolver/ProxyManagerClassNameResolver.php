<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\ODM\MongoDB\Configuration;
use ProxyManager\Inflector\ClassNameInflectorInterface;

/**
 * @internal
 */
final class ProxyManagerClassNameResolver implements ClassNameResolver
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Gets the real class name of a class name that could be a proxy.
     */
    public function getRealClass(string $class): string
    {
        return $this->getClassNameInflector()->getUserClassName($class);
    }

    private function getClassNameInflector(): ClassNameInflectorInterface
    {
        return $this->configuration->getProxyManagerConfiguration()->getClassNameInflector();
    }
}
