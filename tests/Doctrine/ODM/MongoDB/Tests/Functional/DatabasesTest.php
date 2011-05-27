<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Annotations as ODM,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\MongoDB\Connection,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Ecommerce\ConfigurableProduct,
    Documents\Ecommerce\StockItem,
    Documents\Ecommerce\Currency,
    Documents\Ecommerce\Money,
    Documents\Ecommerce\Option;

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