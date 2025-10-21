<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Factory;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\LifecycleEventManager;
use Doctrine\Persistence\NotifyPropertyChanged;
use LogicException;
use ReflectionClass;
use WeakMap;

use function count;

use const PHP_VERSION_ID;

/** @internal */
class NativeLazyObjectFactory implements ProxyFactory
{
    /** @var WeakMap<object, bool>|null */
    private static ?WeakMap $lazyObjects = null;

    private readonly UnitOfWork $unitOfWork;
    private readonly LifecycleEventManager $lifecycleEventManager;

    public function __construct(
        DocumentManager $documentManager,
    ) {
        if (PHP_VERSION_ID < 80400) {
            throw new LogicException('Native lazy objects require PHP 8.4 or higher.');
        }

        $this->unitOfWork            = $documentManager->getUnitOfWork();
        $this->lifecycleEventManager = new LifecycleEventManager($documentManager, $this->unitOfWork, $documentManager->getEventManager());
    }

    public function generateProxyClasses(array $classes): int
    {
        // Nothing to generate, that's the point of native lazy objects

        return count($classes);
    }

    public function getProxy(ClassMetadata $metadata, $identifier): object
    {
        $proxy = $metadata->reflClass->newLazyGhost(function (object $object) use (
            $identifier,
            $metadata,
        ): void {
            $original = $this->unitOfWork->getDocumentPersister($metadata->name)->load([$metadata->identifier => $identifier], $object);

            if ($object instanceof NotifyPropertyChanged) {
                $object->addPropertyChangedListener($this->unitOfWork);
            }

            if ($original !== null) {
                return;
            }

            if (! $this->lifecycleEventManager->documentNotFound($object, $identifier)) {
                throw DocumentNotFoundException::documentNotFound($metadata->name, $identifier);
            }
        }, ReflectionClass::SKIP_INITIALIZATION_ON_SERIALIZE);

        $metadata->propertyAccessors[$metadata->identifier]->setValue($proxy, $identifier);

        if (isset(self::$lazyObjects)) {
            self::$lazyObjects[$proxy] = true;
        }

        return $proxy;
    }

    /** @internal Only for tests */
    public static function enableTracking(bool $enabled = true): void
    {
        if ($enabled) {
            self::$lazyObjects ??= new WeakMap();
        } else {
            self::$lazyObjects = null;
        }
    }

    /** @internal Only for tests */
    public static function isLazyObject(object $object): bool
    {
        if (! isset(self::$lazyObjects)) {
            throw new LogicException('Lazy object tracking is not enabled.');
        }

        return self::$lazyObjects->offsetExists($object);
    }
}
