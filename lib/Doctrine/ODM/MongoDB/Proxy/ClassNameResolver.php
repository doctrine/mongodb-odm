<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy;

use Doctrine\ODM\MongoDB\Configuration;
use ProxyManager\Inflector\ClassNameInflectorInterface;
use function get_class;

final class ClassNameResolver
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
    public function getRealClass(string $class) : string
    {
        return $this->getClassNameInflector()->getUserClassName($class);
    }

    /**
     * Gets the real class name of an object (even if its a proxy).
     */
    public function getClass(object $object) : string
    {
        return $this->getRealClass(get_class($object));
    }

    private function getClassNameInflector() : ClassNameInflectorInterface
    {
        return $this->configuration->getProxyManagerConfiguration()->getClassNameInflector();
    }
}
