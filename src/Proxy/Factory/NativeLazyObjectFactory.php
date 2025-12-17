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
use ReflectionProperty;
use WeakMap;

use function array_key_exists;
use function count;

use const PHP_VERSION_ID;

/** @internal */
class NativeLazyObjectFactory implements ProxyFactory
{
    /** @var WeakMap<object, bool>|null */
    private static ?WeakMap $lazyObjects = null;

    private readonly UnitOfWork $unitOfWork;
    private readonly LifecycleEventManager $lifecycleEventManager;

    /** @var array<class-string, ReflectionProperty[]> */
    private array $skippedProperties = [];

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

        foreach ($this->getSkippedProperties($metadata) as $property) {
            $property->skipLazyInitialization($proxy);
        }

        if (isset(self::$lazyObjects)) {
            self::$lazyObjects[$proxy] = true;
        }

        return $proxy;
    }

    /** @return ReflectionProperty[] */
    private function getSkippedProperties(ClassMetadata $metadata): array
    {
        if (isset($this->skippedProperties[$metadata->name])) {
            return $this->skippedProperties[$metadata->name];
        }

        $skippedProperties = [];
        foreach ($metadata->reflClass->getProperties() as $property) {
            if (array_key_exists($property->name, $metadata->propertyAccessors)) {
                continue;
            }

            if ($property->isVirtual()) {
                continue;
            }

            if ($property->isStatic()) {
                continue;
            }

            $skippedProperties[] = $property;
        }

        return $this->skippedProperties[$metadata->name] = $skippedProperties;
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
