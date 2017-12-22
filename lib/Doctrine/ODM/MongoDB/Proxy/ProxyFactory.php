<?php

namespace Doctrine\ODM\MongoDB\Proxy;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Proxy\Proxy as BaseProxy;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Utility\LifecycleEventManager;
use ReflectionProperty;

/**
 * This factory is used to create proxy objects for documents at runtime.
 *
 * @since       1.0
 */
class ProxyFactory extends AbstractProxyFactory
{
    /**
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var \Doctrine\ODM\MongoDB\UnitOfWork The UnitOfWork this factory is bound to.
     */
    private $uow;

    /**
     * @var string The namespace that contains all proxy classes.
     */
    private $proxyNamespace;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $lifecycleEventManager;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager The DocumentManager the new factory works for.
     * @param string                                $proxyDir        The directory to use for the proxy classes. It
     *                                                               must exist.
     * @param string                                $proxyNamespace  The namespace to use for the proxy classes.
     * @param integer                               $autoGenerate    Whether to automatically generate proxy classes.
     */
    public function __construct(DocumentManager $documentManager, $proxyDir, $proxyNamespace, $autoGenerate = AbstractProxyFactory::AUTOGENERATE_NEVER)
    {
        $this->metadataFactory = $documentManager->getMetadataFactory();
        $this->uow = $documentManager->getUnitOfWork();
        $this->proxyNamespace = $proxyNamespace;
        $this->lifecycleEventManager = new LifecycleEventManager($documentManager, $this->uow, $documentManager->getEventManager());
        $proxyGenerator = new ProxyGenerator($proxyDir, $proxyNamespace);

        $proxyGenerator->setPlaceholder('baseProxyInterface', Proxy::class);

        parent::__construct($proxyGenerator, $this->metadataFactory, $autoGenerate);
    }

    /**
     * {@inheritDoc}
     */
    public function skipClass(BaseClassMetadata $class)
    {
        /* @var $class \Doctrine\ODM\Mongodb\Mapping\ClassMetadataInfo */
        return $class->isMappedSuperclass || $class->isQueryResultDocument || $class->getReflectionClass()->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function createProxyDefinition($className)
    {
        /* @var $classMetadata \Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo */
        $classMetadata     = $this->metadataFactory->getMetadataFor($className);
        $documentPersister = $this->uow->getDocumentPersister($className);
        $reflectionId      = $classMetadata->reflFields[$classMetadata->identifier];

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($className, $this->proxyNamespace),
            $classMetadata->getIdentifierFieldNames(),
            $classMetadata->getReflectionProperties(),
            $this->createInitializer($classMetadata, $documentPersister, $reflectionId),
            $this->createCloner($classMetadata, $documentPersister, $reflectionId)
        );
    }

    /**
     * Generates a closure capable of initializing a proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param \Doctrine\ODM\MongoDB\Persisters\DocumentPersister $documentPersister
     * @param \ReflectionProperty                                $reflectionId
     *
     * @return \Closure
     *
     * @throws \Doctrine\ODM\MongoDB\DocumentNotFoundException
     */
    private function createInitializer(
        BaseClassMetadata $classMetadata,
        DocumentPersister $documentPersister,
        ReflectionProperty $reflectionId
    ) {
        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            return function (BaseProxy $proxy) use ($documentPersister, $reflectionId) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if ( ! isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup();

                $id = $reflectionId->getValue($proxy);

                if (null === $documentPersister->load(array('_id' => $id), $proxy)) {
                    if ( ! $this->lifecycleEventManager->documentNotFound($proxy, $id)) {
                        throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
                    }
                }

                if ($proxy instanceof NotifyPropertyChanged) {
                    $proxy->addPropertyChangedListener($this->uow);
                }
            };
        }

        return function (BaseProxy $proxy) use ($documentPersister, $reflectionId) {
            $proxy->__setInitializer(null);
            $proxy->__setCloner(null);

            if ($proxy->__isInitialized()) {
                return;
            }

            $properties = $proxy->__getLazyProperties();

            foreach ($properties as $propertyName => $property) {
                if ( ! isset($proxy->$propertyName)) {
                    $proxy->$propertyName = $properties[$propertyName];
                }
            }

            $proxy->__setInitialized(true);

            $id = $reflectionId->getValue($proxy);

            if (null === $documentPersister->load(array('_id' => $id), $proxy)) {
                if ( ! $this->lifecycleEventManager->documentNotFound($proxy, $id)) {
                    throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
                }
            }

            if ($proxy instanceof NotifyPropertyChanged) {
                $proxy->addPropertyChangedListener($this->uow);
            }
        };
    }

    /**
     * Generates a closure capable of finalizing a cloned proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param \Doctrine\ODM\MongoDB\Persisters\DocumentPersister $documentPersister
     * @param \ReflectionProperty                                $reflectionId
     *
     * @return \Closure
     *
     * @throws \Doctrine\ODM\MongoDB\DocumentNotFoundException
     */
    private function createCloner(
        BaseClassMetadata $classMetadata,
        DocumentPersister $documentPersister,
        ReflectionProperty $reflectionId
    ) {
        return function (BaseProxy $proxy) use ($documentPersister, $classMetadata, $reflectionId) {
            if ($proxy->__isInitialized()) {
                return;
            }

            $proxy->__setInitialized(true);
            $proxy->__setInitializer(null);

            $id       = $reflectionId->getValue($proxy);
            $original = $documentPersister->load(array('_id' => $id));

            if (null === $original) {
                if ( ! $this->lifecycleEventManager->documentNotFound($proxy, $id)) {
                    throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
                }
            }

            foreach ($classMetadata->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                if ($classMetadata->hasField($propertyName) || $classMetadata->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($proxy, $reflectionProperty->getValue($original));
                }
            }
        };
    }
}
