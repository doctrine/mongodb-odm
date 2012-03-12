<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SchemaManagerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected $uow;
    protected $dm;

    public function setUp()
    {
        $this->uow = $this->getUnitOfWork();
        $documentPersister = $this->getDocumentPersister();
        $this->uow->expects($this->any())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        $this->dm = $this->getDocumentManager();
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $this->dm->getMetadataFactory()));
        $this->dm->setUnitOfWork($this->uow);
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
            array(array('Documents\SimpleReferenceUser', 'Documents\Comment', 'Documents\CmsArticle', 'Documents\CmsAddress', 'Documents\CmsComment', 'Documents\CmsProduct'))
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

    public function testEnsureDocumentIndexesWithTwoLevelInheritance()
    {
        $collection = $this->getDocumentCollection();
        $collection->expects($this->once())
            ->method('ensureIndex');
        $this->dm->setDocumentCollection('Documents\CmsProduct', $collection);
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Documents\CmsProduct');
    }

    /**
     * @dataProvider getIndexedClasses
     */
    public function testDeleteIndexes(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $collection = $this->getDocumentCollection();
            $collection->expects($this->once())
                ->method('deleteIndexes');
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);

            $this->dm->setDocumentCollection($className, $collection);
        }

        $metadataFactory = $this->getMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->deleteIndexes();
    }

    public function testDeleteDocumentIndexes()
    {
        $collection = $this->getDocumentCollection();
        $collection->expects($this->once())
            ->method('deleteIndexes');
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);

        $this->dm->getSchemaManager()->deleteDocumentIndexes('Documents\CmsArticle');
    }

    public function testCreateDocumentCollection()
    {
        $className = 'Documents\CmsArticle';
        $classMetadata = $this->getClassMetadata($className);
        $classMetadata->expects($this->once())
            ->method('getCollection');
        $classMetadata->expects($this->once())
            ->method('getCollectionCapped');
        $classMetadata->expects($this->once())
            ->method('getCollectionSize');
        $classMetadata->expects($this->once())
            ->method('getCollectionMax');

        $documentDatabase = $this->getDocumentDatabase($className);
        $documentDatabase->expects($this->once())
            ->method('createCollection');
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->setClassMetadata($className, $classMetadata);

        $this->dm->getSchemaManager()->createDocumentCollection($className);
    }

    /**
     * @dataProvider getIndexedClasses
     */
    public function testCreateCollections(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);
            $documentDatabase = $this->getDocumentDatabase($className);
            $documentDatabase->expects($this->once())
                ->method('createCollection');
            $this->dm->setDocumentDatabase($className, $documentDatabase);
        }

        $metadataFactory = $this->getMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->createCollections();
    }

    /**
     * @dataProvider getIndexedClasses
     */
    public function testDropCollections(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $metadata = $this->getClassMetadata($className);
            $metadata->expects($this->once())
                ->method('getCollection')
                ->will($this->returnValue($className));
            $documentDatabase = $this->getDocumentDatabase($className);
            $documentDatabase->expects($this->once())
                ->method('dropCollection')
                ->with($className);
            $this->dm->setDocumentDatabase($className, $documentDatabase);
            $this->dm->setClassMetadata($className, $metadata);
            $metadatas[] = $metadata;
        }

        $metadataFactory = $this->getMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->dropCollections();
    }

    public function testDropDocumentCollection()
    {
        $className = 'Documents\CmsArticle';
        $collectionName = 'cms_articles';
        $classMetadata = $this->getClassMetadata($className);
        $classMetadata->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collectionName));

        $documentDatabase = $this->getDocumentDatabase($className);
        $documentDatabase->expects($this->once())
            ->method('dropCollection')
            ->with($collectionName);
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->setClassMetadata($className, $classMetadata);

        $this->dm->getSchemaManager()->dropDocumentCollection($className);
    }

    public function testCreateDocumentDatabase()
    {
        $className = 'Documents\CmsArticle';
        $dbName = 'test_db';
        $documentDatabase = $this->getDocumentDatabase($className);
        $documentDatabase->expects($this->once())
            ->method('execute');
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->getSchemaManager()->createDocumentDatabase($className);
    }

    public function testDropDocumentDatabase()
    {
        $className = 'Documents\CmsArticle';
        $dbName = 'test_db';

        $documentDatabase = $this->getDocumentDatabase($className);
        $documentDatabase->expects($this->once())
            ->method('drop');
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->getSchemaManager()->dropDocumentDatabase($className);
    }

    /**
     * @dataProvider getIndexedClasses
     */
    public function testDropDatabases(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $documentDatabase = $this->getDocumentDatabase($className);
            $documentDatabase->expects($this->once())
                ->method('drop');
            $this->dm->setDocumentDatabase($className, $documentDatabase);
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);
        }

        $metadataFactory = $this->getMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->dropDatabases();
    }

    protected function getDocumentManager()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');

        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');

        $config->setDefaultDB('doctrine_odm_tests');

        $reader = new AnnotationReader();
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/../../../../Documents'));
        return DocumentManagerMock::create($this->getConnection(), $config);
    }

    protected function getDocumentPersister()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getUnitOfWork()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getConnection()
    {
        return $this->getMock('Doctrine\MongoDB\Connection', array('selectDatabase'), array(), '', false, false);
    }

    protected function getMetadataFactory()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory', array('getAllMetadata', 'getMetadataFor'), array(), '', false, false);
    }

    protected function getClassMetadata($className)
    {
        $classMetadata = $this->getMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadata', array('getCollection', 'getCollectionCapped', 'getCollectionSize', 'getCollectionMax'), array($className), '', true, false);
        $classMetadata->name = $className;
        return $classMetadata;
    }

    protected function getDocumentCollection()
    {
        return $this->getMock('Doctrine\MongoDB\Collection', array('ensureIndex', 'deleteIndex', 'deleteIndexes', 'selectCollection'), array(), '', false, false);
    }

    protected function getDocumentDatabase($className)
    {
        $documentDatabase = $this->getMock('Doctrine\MongoDB\Database', array('authenticate', 'command', 'createCollection', 'createDBRef', 'drop', 'dropCollection', 'execute', 'forceError', 'getDatabaseRef', '__get', 'getGridFS', 'getProfilingLevel', 'getLastError'), array(), '', false, false);
        return $documentDatabase;
    }
}