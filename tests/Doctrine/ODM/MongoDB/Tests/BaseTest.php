<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Mocks\MetadataDriverMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\ConnectionMock;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $dm;
    protected $uow;

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');

        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');

        $config->setDefaultDB('doctrine_odm_tests');

        /*
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        $config->setMetadataCacheImpl(new ApcCache());
        */

        $reader = new AnnotationReader();
        $this->annotationDriver = new AnnotationDriver($reader, __DIR__ . '/../../../../Documents');
        $config->setMetadataDriverImpl($this->annotationDriver);

        $conn = new Connection(null, array(), $config);
        $this->dm = DocumentManager::create($conn, $config);
        $this->uow = $this->dm->getUnitOfWork();
    }

    protected function getTestDocumentManager($metadataDriver = null)
    {
        if ($metadataDriver === null) {
            $metadataDriver = new MetadataDriverMock();
        }
        $mongoMock = new ConnectionMock();
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\ODM\MongoDB\Tests\Proxies');
        $eventManager = new EventManager();
        $mockDriver = new MetadataDriverMock();
        $config->setMetadataDriverImpl($metadataDriver);

        return DocumentManagerMock::create($mongoMock, $config, $eventManager);
    }

    public function tearDown()
    {
        if ($this->dm) {
            $collections = $this->dm->getConnection()->selectDatabase('doctrine_odm_tests')->listCollections();
            foreach ($collections as $collection) {
                $collection->remove(array(), array('safe' => true));
            }
        }
    }

    public function escape($command)
    {
        return $this->dm->getConfiguration()->getMongoCmd() . $command;
    }
}