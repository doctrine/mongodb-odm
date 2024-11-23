<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy;

use Doctrine\Persistence\Proxy;
use ProxyManager\Proxy\GhostObjectInterface;

use function class_exists;

if (class_exists(GhostObjectInterface::class)) {
    /**
     * @internal
     *
     * @template T of object
     * @template-extends Proxy<T>
     */
    interface InternalProxy extends Proxy, GhostObjectInterface
    {
        public function __setInitialized(bool $initialized): void;
    }

} else {
    /**
     * @internal
     *
     * @template T of object
     * @template-extends Proxy<T>
     */
    interface InternalProxy extends Proxy
    {
        public function __setInitialized(bool $initialized): void;
    }
}
