<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\MongoDB\Connection;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $dm;
    protected $uow;

    public function setUp()
    {
        $this->dm = $this->createTestDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
    }

    public function tearDown()
    {
        if ( ! $this->dm) {
            return;
        }

        $collections = $this->dm->getConnection()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->listCollections();

        foreach ($collections as $collection) {
            $collection->drop();
        }
    }

    protected function getConfiguration()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setMetadataDriverImpl($this->createMetadataDriverImpl());

        $config->addFilter('testFilter', 'Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter');
        $config->addFilter('testFilter2', 'Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter');

        return $config;
    }

    protected function createMetadataDriverImpl()
    {
        return AnnotationDriver::create(__DIR__ . '/../../../../Documents');
    }

    protected function createTestDocumentManager()
    {
        $config = $this->getConfiguration();
        $conn = new Connection(DOCTRINE_MONGODB_SERVER, array(), $config);

        return DocumentManager::create($conn, $config);
    }
}
