<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Resolver;

use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Doctrine\Persistence\Proxy;

use function strrpos;
use function substr;

/** @internal */
class LazyGhostProxyClassNameResolver implements ClassNameResolver, ProxyClassNameResolver
{
    public function getRealClass(string $class): string
    {
        return $this->resolveClassName($class);
    }

    public function resolveClassName(string $className): string
    {
        $pos = strrpos($className, '\\' . Proxy::MARKER . '\\');

        if ($pos === false) {
            return $className;
        }

        return substr($className, $pos + Proxy::MARKER_LENGTH + 2);
    }
}
