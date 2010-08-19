<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Proxy\ProxyFactory,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\SchemaManager,
    Doctrine\ODM\MongoDB\MongoCollection;
    
class DocumentManagerMock extends \Doctrine\ODM\MongoDB\DocumentManager
{
    private $uowMock;
    private $proxyFactoryMock;
    private $metadataFactory;
    private $schemaManager;
    private $documentCollections = array();

    public function getUnitOfWork()
    {
        return isset($this->uowMock) ? $this->uowMock : parent::getUnitOfWork();
    }

    public function setUnitOfWork(UnitOfWork $uow)
    {
        $this->uowMock = $uow;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory)
    {
        $this->proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory()
    {
        return isset($this->proxyFactoryMock) ? $this->proxyFactoryMock : parent::getProxyFactory();
    }

    public function setMetadataFactory(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    public function getMetadataFactory()
    {
        return isset($this->metadataFactory) ? $this->metadataFactory : parent::getMetadataFactory();
    }

    public function setSchemaManager(SchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    public function getSchemaManager()
    {
        return isset($this->schemaManager) ? $this->schemaManager : parent::getSchemaManager();
    }

    public function setDocumentCollection($className, MongoCollection $collection)
    {
        $this->documentCollections[$className] = $collection;
    }

    public function getDocumentCollection($className)
    {
        return isset($this->documentCollections[$className]) ? $this->documentCollections[$className] : parent::getDocumentCollection($className);
    }

    public static function create(Mongo $mongo, Configuration $config = null, EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new \Doctrine\ODM\MongoDB\Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl(\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::create());
        }
        if (is_null($eventManager)) {
            $eventManager = new \Doctrine\Common\EventManager();
        }
        
        return new DocumentManagerMock($mongo, $config, $eventManager);   
    }
}