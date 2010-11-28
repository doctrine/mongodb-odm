<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Proxy\ProxyFactory,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\SchemaManager,
    Doctrine\ODM\MongoDB\MongoCollection;
    
class DocumentManagerMock extends \Doctrine\ODM\MongoDB\DocumentManager
{
    private $uowMock;
    private $proxyFactoryMock;
    private $metadataFactory;
    private $schemaManager;
    private $documentCollections = array();
    private $documentDBs = array();
    private $documentMetadatas = array();

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

    public function setDocumentCollection($documentName, MongoCollection $collection)
    {
        $this->documentCollections[$documentName] = $collection;
    }

    public function getDocumentCollection($documentName)
    {
        return isset($this->documentCollections[$documentName]) ? $this->documentCollections[$documentName] : parent::getDocumentCollection($documentName);
    }

    public function setDocumentDB($documentName, \MongoDB $documentDB)
    {
        $this->documentDBs[$documentName] = $documentDB;
    }

    public function getDocumentDB($documentName)
    {
        return isset($this->documentDBs[$documentName]) ? $this->documentDBs[$documentName] : parent::getDocumentDB($documentName);
    }


    public function setClassMetadata($documentName, ClassMetadata $metadata)
    {
        $this->documentMetadatas[$documentName] = $metadata;
    }

    public function getClassMetadata($documentName)
    {
        return isset($this->documentMetadatas[$documentName]) ? $this->documentMetadatas[$documentName] : parent::getClassMetadata($documentName);
    }

    public static function create(Mongo $mongo = null, Configuration $config = null, EventManager $eventManager = null)
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