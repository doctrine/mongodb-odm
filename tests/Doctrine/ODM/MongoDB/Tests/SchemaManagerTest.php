<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SchemaManagerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->uow->expects($this->any())
            ->method('getDocumentPersister')
            ->will($this->returnValue($this->getMockDocumentPersister()));

        $this->dm = $this->getMockDocumentManager();
        $this->dm->setSchemaManager(new SchemaManager($this->dm, $this->dm->getMetadataFactory()));
        $this->dm->setUnitOfWork($this->uow);
    }

    public function tearDown()
    {
        unset($this->dm, $this->uow);
    }

    /**
     * @dataProvider provideIndexedClasses
     */
    public function testEnsureIndexes(array $classes)
    {
        foreach ($classes as $className) {
            $collection = $this->getMockCollection();
            $collection->expects($this->once())
                ->method('ensureIndex');
            $this->dm->setDocumentCollection($className, $collection);
        }

        $this->dm->getSchemaManager()->ensureIndexes();
    }

    public function testEnsureDocumentIndexes()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('ensureIndex');
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Documents\CmsArticle');
    }

    public function testEnsureDocumentIndexesWithTwoLevelInheritance()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('ensureIndex');
        $this->dm->setDocumentCollection('Documents\CmsProduct', $collection);
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Documents\CmsProduct');
    }

    public function testEnsureDocumentIndexesWithTimeout()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('ensureIndex')
            ->with($this->anything(), $this->callback(function($o) {
                return isset($o['timeout']) && $o['timeout'] === 10000;
            }));
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);
        $this->dm->getSchemaManager()->ensureDocumentIndexes('Documents\CmsArticle', 10000);
    }

    public function testUpdateDocumentIndexesShouldCreateMappedIndexes()
    {
        $database = $this->getMockDatabase();
        $database->expects($this->never())
            ->method('command');

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('getIndexInfo')
            ->will($this->returnValue(array()));
        $collection->expects($this->once())
            ->method('ensureIndex');
        $collection->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);
        $this->dm->getSchemaManager()->updateDocumentIndexes('Documents\CmsArticle');
    }

    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes()
    {
        $database = $this->getMockDatabase();
        $database->expects($this->once())
            ->method('command')
            ->with($this->callback(function($c) {
                return array_key_exists('deleteIndexes', $c);
            }));

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('getIndexInfo')
            ->will($this->returnValue(array(array(
                'v' => 1,
                'key' => array('topic' => -1),
                'name' => 'topic_-1'
            ))));
        $collection->expects($this->once())
            ->method('ensureIndex');
        $collection->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);
        $this->dm->getSchemaManager()->updateDocumentIndexes('Documents\CmsArticle');
    }

    /**
     * @dataProvider provideIndexedClasses
     */
    public function testDeleteIndexes(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $collection = $this->getMockCollection();
            $collection->expects($this->once())
                ->method('deleteIndexes');
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);

            $this->dm->setDocumentCollection($className, $collection);
        }

        $metadataFactory = $this->getMockMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->deleteIndexes();
    }

    public function testDeleteDocumentIndexes()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('deleteIndexes');
        $this->dm->setDocumentCollection('Documents\CmsArticle', $collection);

        $this->dm->getSchemaManager()->deleteDocumentIndexes('Documents\CmsArticle');
    }

    public function testCreateDocumentCollection()
    {
        $className = 'Documents\CmsArticle';

        $classMetadata = $this->getMockClassMetadata($className);
        $classMetadata->expects($this->any())
            ->method('isFile')
            ->will($this->returnValue(false));
        $classMetadata->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue('cms_articles'));
        $classMetadata->expects($this->once())
            ->method('getCollectionCapped')
            ->will($this->returnValue(true));
        $classMetadata->expects($this->once())
            ->method('getCollectionSize')
            ->will($this->returnValue(1048576));
        $classMetadata->expects($this->once())
            ->method('getCollectionMax')
            ->will($this->returnValue(32));

        $documentDatabase = $this->getMockDatabase();
        $documentDatabase->expects($this->once())
            ->method('createCollection')
            ->with('cms_articles', true, 1048576, 32);

        $this->dm->setDocumentDatabase($className, $documentDatabase);
        $this->dm->setClassMetadata($className, $classMetadata);
        $this->dm->getSchemaManager()->createDocumentCollection($className);
    }

    public function testCreateGridFSCollection()
    {
        $className = 'Documents\File';

        $classMetadata = $this->getMockClassMetadata($className);
        $classMetadata->expects($this->any())
            ->method('isFile')
            ->will($this->returnValue(true));
        $classMetadata->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue('fs'));

        $documentDatabase = $this->getMockDatabase();
        $documentDatabase->expects($this->at(0))
            ->method('createCollection')
            ->with('fs.files');
        $documentDatabase->expects($this->at(1))
            ->method('createCollection')
            ->with('fs.chunks');

        $this->dm->setDocumentDatabase($className, $documentDatabase);
        $this->dm->setClassMetadata($className, $classMetadata);
        $this->dm->getSchemaManager()->createDocumentCollection($className);
    }

    /**
     * @dataProvider provideIndexedClasses
     */
    public function testCreateCollections(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);
            $documentDatabase = $this->getMockDatabase();
            $documentDatabase->expects($this->once())
                ->method('createCollection');
            $this->dm->setDocumentDatabase($className, $documentDatabase);
        }

        $metadataFactory = $this->getMockMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->createCollections();
    }

    /**
     * @dataProvider provideIndexedClasses
     */
    public function testDropCollections(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $metadata = $this->getMockClassMetadata($className);
            $metadata->expects($this->once())
                ->method('getCollection')
                ->will($this->returnValue($className));
            $documentDatabase = $this->getMockDatabase();
            $documentDatabase->expects($this->once())
                ->method('dropCollection')
                ->with($className);
            $this->dm->setDocumentDatabase($className, $documentDatabase);
            $this->dm->setClassMetadata($className, $metadata);
            $metadatas[] = $metadata;
        }

        $metadataFactory = $this->getMockMetadataFactory();
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
        $classMetadata = $this->getMockClassMetadata($className);
        $classMetadata->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collectionName));

        $documentDatabase = $this->getMockDatabase();
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
        $documentDatabase = $this->getMockDatabase();
        $documentDatabase->expects($this->once())
            ->method('execute');
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->getSchemaManager()->createDocumentDatabase($className);
    }

    public function testDropDocumentDatabase()
    {
        $className = 'Documents\CmsArticle';
        $dbName = 'test_db';

        $documentDatabase = $this->getMockDatabase();
        $documentDatabase->expects($this->once())
            ->method('drop');
        $this->dm->setDocumentDatabase($className, $documentDatabase);

        $this->dm->getSchemaManager()->dropDocumentDatabase($className);
    }

    /**
     * @dataProvider provideIndexedClasses
     */
    public function testDropDatabases(array $classes)
    {
        $metadatas = array();
        foreach ($classes as $className) {
            $documentDatabase = $this->getMockDatabase();
            $documentDatabase->expects($this->once())
                ->method('drop');
            $this->dm->setDocumentDatabase($className, $documentDatabase);
            $metadatas[] = (object) array('name' => $className, 'isMappedSuperclass' => false, 'isEmbeddedDocument' => false);
        }

        $metadataFactory = $this->getMockMetadataFactory();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadatas));

        $this->dm->setMetadataFactory($metadataFactory);
        $this->dm->setSchemaManager(new \Doctrine\ODM\MongoDB\SchemaManager($this->dm, $metadataFactory));

        $this->dm->getSchemaManager()->dropDatabases();
    }

    public function provideIndexedClasses()
    {
        return array(array(array(
                'Documents\SimpleReferenceUser',
                'Documents\Comment',
                'Documents\CmsArticle',
                'Documents\CmsAddress',
                'Documents\CmsComment',
                'Documents\CmsProduct'
        )));
    }

    private function getMockClassMetadata($className)
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->getMock();
    }

    private function getMockCollection()
    {
        return $this->getMockBuilder('Doctrine\MongoDB\Collection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockDatabase()
    {
        return $this->getMockBuilder('Doctrine\MongoDB\Database')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockDocumentManager()
    {
        $connection = $this->getMockBuilder('Doctrine\MongoDB\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('doctrine_odm_tests');

        $reader = new AnnotationReader();
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/../../../../Documents'));

        return DocumentManagerMock::create($connection, $config);
    }

    private function getMockDocumentPersister()
    {
        $documentPersister = $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();

        $documentPersister->expects($this->any())
            ->method('prepareFieldName')
            ->will($this->returnArgument(0));

        return $documentPersister;
    }

    private function getMockMetadataFactory()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
