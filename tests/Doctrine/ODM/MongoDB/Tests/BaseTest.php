<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

use Doctrine\ODM\MongoDB\Tests\Mocks\MetadataDriverMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\MongoMock;
use Doctrine\Common\EventManager;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $dm;
    protected $uow;

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setDefaultDB('doctrine_odm_tests');

        $config->setLoggerCallable(function(array $log) {
            //print_r($log);
        });
        //$config->setMetadataCacheImpl(new ApcCache());

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $this->annotationDriver = new AnnotationDriver($reader, __DIR__ . '/Documents');
        $config->setMetadataDriverImpl($this->annotationDriver);

        $this->dm = DocumentManager::create(new Mongo(), $config);
        $this->uow = $this->dm->getUnitOfWork();
    }

    protected function getTestDocumentManager($metadataDriver = null)
    {
        if ($metadataDriver === null) {
            $metadataDriver = new MetadataDriverMock();
        }
        $mongoMock = new MongoMock();
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
        $mongo = $this->dm->getMongo();
        $dbs = $mongo->listDBs();
        foreach ($dbs['databases'] as $db) {
            $collections = $mongo->selectDB($db['name'])->listCollections();
            foreach ($collections as $collection) {
                $collection->drop();
            }
        }
        $this->dm->getMongo()->close();
    }

    public function escape($command)
    {
        return $this->dm->getConfiguration()->getMongoCmd() . $command;
    }
}