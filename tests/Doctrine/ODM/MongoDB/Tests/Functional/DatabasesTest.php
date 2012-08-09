<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\ClassLoader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\StockItem;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;

class DatabasesTest extends \PHPUnit_Framework_TestCase
{
    protected $dm;

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../../Proxies');
        $config->setProxyNamespace('Proxies');

        $config->setHydratorDir(__DIR__ . '/../../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');

        $reader = new AnnotationReader();
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));
        $config->setDefaultDB('testing');

        $this->dm = DocumentManager::create(new Connection(), $config);
    }

    public function testDefaultDatabase()
    {
        $this->assertEquals('testing', $this->dm->getDocumentDatabase('Doctrine\ODM\MongoDB\Tests\Functional\DefaultDatabaseTest')->getName());
    }
}

/** @ODM\Document(collection="test") */
class DefaultDatabaseTest
{
    /** @ODM\Id */
    private $id;

    /** @ODM\String */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}