<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SchemaManagerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected $dm;

    public function setUp()
    {
        $this->dm = $this->getDocumentManager();
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm));
    }

    public function tearDown()
    {
        unset ($this->dm);
    }

    /**
     * @dataProvider getIndexedClasses
     */
    public function testEnsureIndexes(array $classes)
    {
        foreach ($classes as $className) {
            $collection = $this->getDocumentCollection();
            $collection->expects($this->once())
                ->method('ensureIndex');
            $this->dm->setDocumentCollection($className, $collection);
        }

        $this->dm->getSchemaManager()->ensureIndexes();
    }

    public function getIndexedClasses()
    {
        return array(
            array(array('Documents\CmsArticle', 'Documents\CmsAddress', 'Documents\CmsComment'))
        );
    }

    public function testEnsureDocumentIndexes()
    {
        $collection = $this->getDocumentCollection();
        $collection->expects($this->once())
                ->method('ensureIndex');
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Documents\CmsArticle');
    }

    public function testDeleteDocumentIndexes()
    {
        $collection = $this->getDocumentCollection();
        $collection->expects($this->once())
                ->method('deleteIndexes');
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);

        $this->dm->getSchemaManager()->deleteDocumentIndexes('Documents\CmsArticle');
    }

    protected function getDocumentManager()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setDefaultDB('doctrine_odm_tests');

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/../../../../Documents'));
        return DocumentManagerMock::create($this->getMongo(), $config);
    }

    protected function getMongo()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\Mongo');
    }

    protected function getMatadataFactory()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory', array('getAllMetadata', 'getMetadataFor'), array(), '', false, false);
    }

    protected function getDocumentCollection()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\MongoCollection', array('ensureIndex', 'deleteIndex', 'deleteIndexes'), array(), '', false, false);
    }
}