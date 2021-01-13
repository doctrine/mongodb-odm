<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy\Factory;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\LifecycleEventManager;
use Doctrine\Persistence\NotifyPropertyChanged;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionProperty;

use function array_filter;
use function count;

/**
 * This factory is used to create proxy objects for documents at runtime.
 */
final class StaticProxyFactory implements ProxyFactory
{
    /** @var UnitOfWork The UnitOfWork this factory is bound to. */
    private $uow;

    /** @var LifecycleEventManager */
    private $lifecycleEventManager;

    /** @var LazyLoadingGhostFactory */
    private $proxyFactory;

    public function __construct(DocumentManager $documentManager)
    {
        $this->uow                   = $documentManager->getUnitOfWork();
        $this->lifecycleEventManager = new LifecycleEventManager($documentManager, $this->uow, $documentManager->getEventManager());
        $this->proxyFactory          = $documentManager->getConfiguration()->buildGhostObjectFactory();
    }

    public function getProxy(ClassMetadata $metadata, $identifier): GhostObjectInterface
    {
        $documentPersister = $this->uow->getDocumentPersister($metadata->getName());

        $ghostObject = $this
            ->proxyFactory
            ->createProxy(
                $metadata->getName(),
                $this->createInitializer($metadata, $documentPersister),
                [
                    'skippedProperties' => $this->skippedFieldsFqns($metadata),
                ]
            );

        $metadata->setIdentifierValue($ghostObject, $identifier);

        return $ghostObject;
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata[] $classes
     */
    public function generateProxyClasses(array $classes): int
    {
        $concreteClasses = array_filter($classes, static function (ClassMetadata $metadata): bool {
            return ! ($metadata->isMappedSuperclass || $metadata->isQueryResultDocument || $metadata->getReflectionClass()->isAbstract());
        });

        foreach ($concreteClasses as $metadata) {
            $this
                ->proxyFactory
                ->createProxy(
                    $metadata->getName(),
                    static function () {
                        // empty closure, serves its purpose, for now
                    },
                    [
                        'skippedProperties' => $this->skippedFieldsFqns($metadata),
                    ]
                );
        }

        return count($concreteClasses);
    }

    private function createInitializer(
        ClassMetadata $metadata,
        DocumentPersister $documentPersister
    ): Closure {
        return function (
            GhostObjectInterface $ghostObject,
            string $method,
            // we don't care
            array $parameters,
            // we don't care
            &$initializer,
            array $properties // we currently do not use this
        ) use (
            $metadata,
            $documentPersister
        ): bool {
            $originalInitializer = $initializer;
            $initializer         = null;
            $identifier          = $metadata->getIdentifierValue($ghostObject);

            if (! $documentPersister->load(['_id' => $identifier], $ghostObject)) {
                $initializer = $originalInitializer;

                if (! $this->lifecycleEventManager->documentNotFound($ghostObject, $identifier)) {
                    throw DocumentNotFoundException::documentNotFound($metadata->getName(), $identifier);
                }
            }

            if ($ghostObject instanceof NotifyPropertyChanged) {
                $ghostObject->addPropertyChangedListener($this->uow);
            }

            return true;
        };
    }

    /**
     * @return string[]
     */
    private function skippedFieldsFqns(ClassMetadata $metadata): array
    {
        $idFieldFqcns = [];

        foreach ($metadata->getIdentifierFieldNames() as $idField) {
            $idFieldFqcns[] = $this->propertyFqcn($metadata->getReflectionProperty($idField));
        }

        return $idFieldFqcns;
    }

    private function propertyFqcn(ReflectionProperty $property): string
    {
        if ($property->isPrivate()) {
            return "\0" . $property->getDeclaringClass()->getName() . "\0" . $property->getName();
        }

        if ($property->isProtected()) {
            return "\0*\0" . $property->getName();
        }

        return $property->getName();
    }
}
