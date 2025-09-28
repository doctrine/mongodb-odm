<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Factory;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use LogicException;
use ReflectionClass;
use WeakMap;

use function count;

use const PHP_VERSION_ID;

/** @internal */
class NativeLazyObjectFactory implements ProxyFactory
{
    private static ?WeakMap $lazyObjects = null;

    public function __construct(
        private readonly UnitOfWork $unitOfWork,
    ) {
        if (PHP_VERSION_ID < 80400) {
            throw new LogicException('Native lazy objects require PHP 8.4 or higher.');
        }
    }

    public function generateProxyClasses(array $classes): int
    {
        // Nothing to generate, that's the point of native lazy objects

        return count($classes);
    }

    public function getProxy(ClassMetadata $metadata, $identifier): object
    {
        $documentPersister = $this->unitOfWork->getDocumentPersister($metadata->name);

        $proxy = $metadata->reflClass->newLazyGhost(static function (object $object) use (
            $identifier,
            $documentPersister,
            $metadata,
        ): void {
            $original = $documentPersister->load([$metadata->identifier => $identifier], $object);
            if ($original === null) {
                throw DocumentNotFoundException::documentNotFound($metadata->name, $identifier);
            }
        }, ReflectionClass::SKIP_INITIALIZATION_ON_SERIALIZE);

        $metadata->propertyAccessors[$metadata->identifier]->setValue($proxy, $identifier);

        if (isset(self::$lazyObjects)) {
            self::$lazyObjects[$proxy] = true;
        }

        return $proxy;
    }

    /** Only for internal tests */
    public static function enableTracking(bool $enabled = true): void
    {
        if ($enabled) {
            self::$lazyObjects ??= new WeakMap();
        } else {
            self::$lazyObjects = null;
        }
    }

    /** Only for internal tests */
    public static function isLazyObject(object $object): bool
    {
        if (! isset(self::$lazyObjects)) {
            throw new LogicException('Lazy object tracking is not enabled.');
        }

        return self::$lazyObjects->offsetExists($object);
    }
}
