<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @template-extends ClassMetadataFactory<ClassMetadata>
 * @method list<ClassMetadata> getAllMetadata()
 * @method ClassMetadata[] getLoadedMetadata()
 * @method ClassMetadata getMetadataFor($className)
 */
interface ClassMetadataFactoryInterface extends ClassMetadataFactory
{
    /**
     * Sets the cache for created metadata.
     */
    public function setCache(CacheItemPoolInterface $cache): void;

    /**
     * Sets the configuration for the factory.
     */
    public function setConfiguration(Configuration $config): void;

    /**
     * Sets the document manager owning the factory.
     */
    public function setDocumentManager(DocumentManager $dm): void;

    /**
     * Sets a resolver for real class names of a proxy.
     */
    public function setProxyClassNameResolver(ProxyClassNameResolver $resolver): void;
}
