<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Decorator;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Aggregation\Builder as AggregationBuilder;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerDecorator;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;

/**
 * Base class for DocumentManager decorators
 */
abstract class DocumentManagerDecorator extends ObjectManagerDecorator implements ObjectManager
{
    /** @var ObjectManager */
    protected $wrapped;

    public function __construct(ObjectManager $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($documentName = null): Builder
    {
        return $this->wrapped->createQueryBuilder($documentName);
    }

    /**
     * {@inheritdoc}
     */
    public function createAggregationBuilder($documentName): AggregationBuilder
    {
        return $this->wrapped->createAggregationBuilder($documentName);
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($documentName, $id): object
    {
        return $this->wrapped->getReference($documentName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPartialReference($documentName, $identifier): object
    {
        return $this->wrapped->getPartialReference($documentName, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function createReference(object $document, array $referenceMapping): object
    {
        return $this->wrapped->createReference($document, $referenceMapping);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->wrapped->close();
    }

    /**
     * {@inheritdoc}
     */
    public function lock(object $document, int $lockMode, ?int $lockVersion = null): void
    {
        $this->wrapped->lock($document, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(object $document): void
    {
        $this->wrapped->unlock($document);
    }

    /**
     * {@inheritdoc}
     */
    public function find($documentName, $id, $lockMode = null, $lockVersion = null)
    {
        return $this->wrapped->find($documentName, $id, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->wrapped->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventManager(): EventManager
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getClient(): Client
    {
        return $this->wrapped->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen()
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork(): UnitOfWork
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function getHydratorFactory() : HydratorFactory
    {
        return $this->wrapped->getHydratorFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNameResolver() : ClassNameResolver
    {
        return $this->wrapped->getClassNameResolver();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager() : SchemaManager
    {
        return $this->wrapped->getSchemaManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentDatabase(string $className) : Database
    {
        return $this->wrapped->getDocumentDatabase($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentDatabases() : array
    {
        return $this->wrapped->getDocumentDatabases();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentCollections() : array
    {
        return $this->wrapped->getDocumentCollections();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentCollection(string $className) : Collection
    {
        return $this->wrapped->getDocumentCollection($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentBucket(string $className) : Bucket
    {
        return $this->wrapped->getDocumentBucket($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory()
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterCollection(): FilterCollection
    {
        return $this->wrapped->getFilterCollection();
    }
}
