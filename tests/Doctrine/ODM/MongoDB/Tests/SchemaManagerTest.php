<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;

class SchemaManagerTest extends \PHPUnit_Framework_TestCase
{
    private $indexedClasses = array(
        'Documents\CmsAddress',
        'Documents\CmsArticle',
        'Documents\CmsComment',
        'Documents\CmsProduct',
        'Documents\Comment',
        'Documents\SimpleReferenceUser',
    );

    private $someNonIndexedClasses = array(
        'Documents\CmsGroup',
        'Documents\CmsPhonenumber',
        'Documents\CmsUser',
    );

    private $someMappedSuperclassAndEmbeddedClasses = array(
        'Documents/CmsContent',
        'Documents/CmsPage',
        'Documents/Issue',
        'Documents/Message',
        'Documents/Phonenumber',
        'Documents/Song',
        'Documents/SubCategory',
    );

    private $classMetadatas = array();
    private $documentCollections = array();
    private $documentDatabases = array();
    private $schemaManager;

    public function setUp()
    {
        $this->dm = $this->getMockDocumentManager();

        $cmf = new ClassMetadataFactory();
        $cmf->setConfiguration($this->dm->getConfiguration());
        $cmf->setDocumentManager($this->dm);

        $map = array();

        foreach ($cmf->getAllMetadata() as $cm) {
            $this->documentCollections[$cm->name] = $this->getMockCollection();
            $this->documentDatabases[$cm->name] = $this->getMockDatabase();
            $this->classMetadatas[$cm->name] = $cm;
        }

        $this->dm->unitOfWork = $this->getMockUnitOfWork();
        $this->dm->metadataFactory = $cmf;
        $this->dm->documentCollections = $this->documentCollections;
        $this->dm->documentDatabases = $this->documentDatabases;

        $this->schemaManager = new SchemaManager($this->dm, $cmf);
        $this->dm->schemaManager = $this->schemaManager;
    }

    public function testEnsureIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection->expects($this->once())->method('ensureIndex');
            } else {
                $collection->expects($this->never())->method('ensureIndex');
            }
        }

        $this->schemaManager->ensureIndexes();
    }

    public function testEnsureDocumentIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === 'Documents\CmsArticle') {
                $collection->expects($this->once())->method('ensureIndex');
            } else {
                $collection->expects($this->never())->method('ensureIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes('Documents\CmsArticle');
    }

    public function testEnsureDocumentIndexesWithTwoLevelInheritance()
    {
        $collection = $this->documentCollections['Documents\CmsProduct'];
        $collection->expects($this->once())->method('ensureIndex');

        $this->schemaManager->ensureDocumentIndexes('Documents\CmsProduct');
    }

    public function testEnsureDocumentIndexesWithTimeout()
    {
        $collection = $this->documentCollections['Documents\CmsArticle'];
        $collection->expects($this->once())
            ->method('ensureIndex')
            ->with($this->anything(), $this->callback(function($o) {
                return isset($o['timeout']) && $o['timeout'] === 10000;
            }));

        $this->schemaManager->ensureDocumentIndexes('Documents\CmsArticle', 10000);
    }

    public function testUpdateDocumentIndexesShouldCreateMappedIndexes()
    {
        $database = $this->documentDatabases['Documents\CmsArticle'];
        $database->expects($this->never())
            ->method('command');

        $collection = $this->documentCollections['Documents\CmsArticle'];
        $collection->expects($this->once())
            ->method('getIndexInfo')
            ->will($this->returnValue(array()));
        $collection->expects($this->once())
            ->method('ensureIndex');
        $collection->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->schemaManager->updateDocumentIndexes('Documents\CmsArticle');
    }

    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes()
    {
        $database = $this->documentDatabases['Documents\CmsArticle'];
        $database->expects($this->once())
            ->method('command')
            ->with($this->callback(function($c) {
                return array_key_exists('deleteIndexes', $c);
            }));

        $collection = $this->documentCollections['Documents\CmsArticle'];
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

        $this->schemaManager->updateDocumentIndexes('Documents\CmsArticle');
    }

    public function testDeleteIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection->expects($this->once())->method('deleteIndexes');
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $collection->expects($this->never())->method('deleteIndexes');
            }
        }

        $this->schemaManager->deleteIndexes();
    }

    public function testDeleteDocumentIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === 'Documents\CmsArticle') {
                $collection->expects($this->once())->method('deleteIndexes');
            } else {
                $collection->expects($this->never())->method('deleteIndexes');
            }
        }

        $this->schemaManager->deleteDocumentIndexes('Documents\CmsArticle');
    }

    public function testCreateDocumentCollection()
    {
        $cm = $this->classMetadatas['Documents\CmsArticle'];
        $cm->collectionCapped = true;
        $cm->collectionSize = 1048576;
        $cm->collectionMax = 32;

        $database = $this->documentDatabases['Documents\CmsArticle'];
        $database->expects($this->once())
            ->method('createCollection')
            ->with('CmsArticle', true, 1048576, 32);

        $this->schemaManager->createDocumentCollection('Documents\CmsArticle');
    }

    public function testCreateGridFSCollection()
    {
        $database = $this->documentDatabases['Documents\File'];
        $database->expects($this->at(0))
            ->method('createCollection')
            ->with('File.files');
        $database->expects($this->at(1))
            ->method('createCollection')
            ->with('File.chunks');

        $this->schemaManager->createDocumentCollection('Documents\File');
    }

    public function testCreateCollections()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $database->expects($this->once())->method('createCollection');
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $database->expects($this->never())->method('createCollection');
            }
        }

        $this->schemaManager->createCollections();
    }

    public function testDropCollections()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $database->expects($this->once())
                    ->method('dropCollection')
                    ->with($this->classMetadatas[$class]->collection);
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $database->expects($this->never())->method('dropCollection');
            }
        }

        $this->schemaManager->dropCollections();
    }

    public function testDropDocumentCollection()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if ($class === 'Documents\CmsArticle') {
                $database->expects($this->once())
                    ->method('dropCollection')
                    ->with($this->classMetadatas[$class]->collection);
            } else {
                $database->expects($this->never())->method('dropCollection');
            }
        }

        $this->schemaManager->dropDocumentCollection('Documents\CmsArticle');
    }

    public function testCreateDocumentDatabase()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if ($class === 'Documents\CmsArticle') {
                $database->expects($this->once())->method('execute');
            } else {
                $database->expects($this->never())->method('execute');
            }
        }

        $this->schemaManager->createDocumentDatabase('Documents\CmsArticle');
    }

    public function testDropDocumentDatabase()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if ($class === 'Documents\CmsArticle') {
                $database->expects($this->once())->method('drop');
            } else {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->dm->getSchemaManager()->dropDocumentDatabase('Documents\CmsArticle');
    }

    public function testDropDatabases()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $database->expects($this->once())->method('drop');
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDatabases();
    }

    /**
     * @dataProvider dataIsMongoIndexEquivalentToDocumentIndex
     */
    public function testIsMongoIndexEquivalentToDocumentIndex($expected, $mongoIndex, $documentIndex)
    {
        $defaultMongoIndex = [
            'key' => ['foo' => 1, 'bar' => -1]
        ];
        $defaultDocumentIndex = [
            'keys' => ['foo' => 1, 'bar' => -1],
            'options' => [],
        ];

        $mongoIndex += $defaultMongoIndex;
        $documentIndex += $defaultDocumentIndex;

        $this->assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex));
    }

    public function dataIsMongoIndexEquivalentToDocumentIndex()
    {
        return [
            'keysSame' => [
                'expected' => true,
                'mongoIndex' => ['key' => ['foo' => 1]],
                'documentIndex' => ['keys' => ['foo' => 1]],
            ],
            'keysDiffer' => [
                'expected' => false,
                'mongoIndex' => ['key' => ['foo' => 1]],
                'documentIndex' => ['keys' => ['foo' => -1]],
            ],
            // Sparse option
            'sparseOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['sparse' => true],
                'documentIndex' => [],
            ],
            'sparseOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['sparse' => true]],
            ],
            'sparseInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['sparse' => true],
                'documentIndex' => ['options' => ['sparse' => true]],
            ],
            // Unique option
            'uniqueOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['unique' => true],
                'documentIndex' => [],
            ],
            'uniqueOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['unique' => true]],
            ],
            'uniqueInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['unique' => true],
                'documentIndex' => ['options' => ['unique' => true]],
            ],
            // DropDups option
            'dropDupsWithoutUniqueInMongoIndex' => [
                'expected' => true,
                'mongoIndex' => ['dropDups' => true],
                'documentIndex' => [],
            ],
            'dropDupsWithoutUniqueInDocumentIndex' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['dropDups' => true]],
            ],
            'dropDupsOnlyInMongoIndex' => [
                'expected' => true,
                'mongoIndex' => ['unique' => true, 'dropDups' => true],
                'documentIndex' => ['options' => ['unique' => true]],
            ],
            'dropDupsOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => ['unique' => true],
                'documentIndex' => ['options' => ['unique' => true, 'dropDups' => true]],
            ],
            // bits option
            'bitsOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['bits' => 5],
                'documentIndex' => [],
            ],
            'bitsOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['bits' => 5]],
            ],
            'bitsInBothIndexesMismatch' => [
                'expected' => false,
                'mongoIndex' => ['bits' => 3],
                'documentIndex' => ['options' => ['bits' => 5]],
            ],
            'bitsInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['bits' => 5],
                'documentIndex' => ['options' => ['bits' => 5]],
            ],
            // max option
            'maxOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['max' => 5],
                'documentIndex' => [],
            ],
            'maxOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['max' => 5]],
            ],
            'maxInBothIndexesMismatch' => [
                'expected' => false,
                'mongoIndex' => ['max' => 3],
                'documentIndex' => ['options' => ['max' => 5]],
            ],
            'maxInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['max' => 5],
                'documentIndex' => ['options' => ['max' => 5]],
            ],
            // min option
            'minOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['min' => 5],
                'documentIndex' => [],
            ],
            'minOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['min' => 5]],
            ],
            'minInBothIndexesMismatch' => [
                'expected' => false,
                'mongoIndex' => ['min' => 3],
                'documentIndex' => ['options' => ['min' => 5]],
            ],
            'minInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['min' => 5],
                'documentIndex' => ['options' => ['min' => 5]],
            ],
            // partialFilterExpression
            'partialFilterExpressionOnlyInMongoIndex' => [
                'expected' => false,
                'mongoIndex' => ['partialFilterExpression' => ['foo' => 5]],
                'documentIndex' => [],
            ],
            'partialFilterExpressionOnlyInDocumentIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['partialFilterExpression' => ['foo' => 5]]],
            ],
            'partialFilterExpressionOnlyInBothIndexesMismatch' => [
                'expected' => false,
                'mongoIndex' => ['partialFilterExpression' => ['foo' => 3]],
                'documentIndex' => ['options' => ['partialFilterExpression' => ['foo' => 5]]],
            ],
            'partialFilterExpressionOnlyInBothIndexes' => [
                'expected' => true,
                'mongoIndex' => ['partialFilterExpression' => ['foo' => 5]],
                'documentIndex' => ['options' => ['partialFilterExpression' => ['foo' => 5]]],
            ],
            'partialFilterExpressionEmptyInOneIndexIsSame' => [
                'expected' => true,
                'mongoIndex' => ['partialFilterExpression' => []],
                'documentIndex' => [],
            ]
        ];
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
        $config = new Configuration();
        $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/../../../../Documents'));

        $em = $this->getMockBuilder('Doctrine\Common\EventManager')
            ->disableOriginalConstructor()
            ->getMock();

        $dm = new DocumentManagerMock();
        $dm->eventManager = $em;
        $dm->config = $config;

        return $dm;
    }

    private function getMockUnitOfWork()
    {
        $documentPersister = $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();

        $documentPersister->expects($this->any())
            ->method('prepareFieldName')
            ->will($this->returnArgument(0));

        $uow = $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $uow->expects($this->any())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        return $uow;
    }
}
