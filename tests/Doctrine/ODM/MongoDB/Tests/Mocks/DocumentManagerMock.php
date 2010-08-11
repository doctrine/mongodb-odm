<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Proxy\ProxyFactory,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\Common\EventManager;
    
class DocumentManagerMock extends \Doctrine\ODM\MongoDB\DocumentManager
{
    private $uowMock;
    private $proxyFactoryMock;

    public function getUnitOfWork()
    {
        return isset($this->uowMock) ? $this->uowMock : parent::getUnitOfWork();
    }

    public function setUnitOfWork($uow)
    {
        $this->uowMock = $uow;
    }

    public function setProxyFactory($proxyFactory)
    {
        $this->proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory()
    {
        return isset($this->proxyFactoryMock) ? $this->proxyFactoryMock : parent::getProxyFactory();
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