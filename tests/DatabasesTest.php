<?php

require_once 'TestInit.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Ecommerce\ConfigurableProduct,
    Documents\Ecommerce\StockItem,
    Documents\Ecommerce\Currency,
    Documents\Ecommerce\Money,
    Documents\Ecommerce\Option;

class DatabasesTest extends PHPUnit_Framework_TestCase
{
    protected $dm;

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));
        $config->setDefaultDB('testing');

        $this->dm = DocumentManager::create(new Mongo(), $config);
    }

    public function testDefaultDatabase()
    {
        $this->assertEquals('testing', $this->dm->getDocumentDB('DefaultDatabaseTest')->getName());
    }
}

/** @Document(collection="test") */
class DefaultDatabaseTest
{
    /** @Id */
    private $id;

    /** @String */
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