<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

/**
 * @internal
 */
final class CachingClassNameResolver implements ClassNameResolver
{
    /** @var ClassNameResolver */
    private $resolver;

    /** @var array<string, string> */
    private $resolvedNames = [];

    public function __construct(ClassNameResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Gets the real class name of a class name that could be a proxy.
     */
    public function getRealClass(string $class): string
    {
        if (! isset($this->resolvedNames[$class])) {
            $this->resolvedNames[$class] = $this->resolver->getRealClass($class);
        }

        return $this->resolvedNames[$class];
    }
}
