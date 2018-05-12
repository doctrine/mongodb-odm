<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\CmsAddress;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\CmsGroup;
use Documents\CmsPhonenumber;
use Documents\CmsProduct;
use Documents\CmsUser;
use Documents\Comment;
use Documents\File;
use Documents\Sharded\ShardedUser;
use Documents\SimpleReferenceUser;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;
use MongoDB\Model\IndexInfoIteratorIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use const DOCTRINE_MONGODB_DATABASE;
use function in_array;

class SchemaManagerTest extends TestCase
{
    private $indexedClasses = [
        CmsAddress::class,
        CmsArticle::class,
        CmsComment::class,
        CmsProduct::class,
        Comment::class,
        SimpleReferenceUser::class,
    ];

    private $someNonIndexedClasses = [
        CmsGroup::class,
        CmsPhonenumber::class,
        CmsUser::class,
    ];

    private $someMappedSuperclassAndEmbeddedClasses = [
        'Documents/BlogTagAggregation',
        'Documents/CmsContent',
        'Documents/CmsPage',
        'Documents/Issue',
        'Documents/Message',
        'Documents/Phonenumber',
        'Documents/Song',
        'Documents/SubCategory',
    ];

    /** @var ClassMetadata[] */
    private $classMetadatas = [];

    /** @var Collection[]|MockObject[] */
    private $documentCollections = [];

    /** @var Bucket[]|MockObject[] */
    private $documentBuckets = [];

    /** @var Database[]|MockObject[] */
    private $documentDatabases = [];

    /** @var SchemaManager */
    private $schemaManager;

    public function setUp()
    {
        $this->dm = $this->getMockDocumentManager();

        $cmf = new ClassMetadataFactory();
        $cmf->setConfiguration($this->dm->getConfiguration());
        $cmf->setDocumentManager($this->dm);

        $map = [];

        foreach ($cmf->getAllMetadata() as $cm) {
            if ($cm->isFile) {
                $this->documentBuckets[$cm->name] = $this->getMockBucket();
            } else {
                $this->documentCollections[$cm->name] = $this->getMockCollection();
            }

            $this->documentDatabases[$cm->name] = $this->getMockDatabase();
            $this->classMetadatas[$cm->name] = $cm;
        }

        $this->dm->unitOfWork = $this->getMockUnitOfWork();
        $this->dm->metadataFactory = $cmf;
        $this->dm->documentCollections = $this->documentCollections;
        $this->dm->documentBuckets = $this->documentBuckets;
        $this->dm->documentDatabases = $this->documentDatabases;

        $this->schemaManager = new SchemaManager($this->dm, $cmf);
        $this->dm->schemaManager = $this->schemaManager;
    }

    public function testEnsureIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection->expects($this->once())->method('createIndex');
            } else {
                $collection->expects($this->never())->method('createIndex');
            }
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            $bucket->getFilesCollection()
                ->expects($this->any())
                ->method('listIndexes')
                ->willReturn([])
            ;
            $bucket->getFilesCollection()
                ->expects($this->once())
                ->method('createIndex')
                ->with(['filename' => 1, 'uploadDate' => 1])
            ;

            $bucket->getChunksCollection()
                ->expects($this->any())
                ->method('listIndexes')
                ->willReturn([])
            ;
            $bucket->getChunksCollection()
                ->expects($this->once())
                ->method('createIndex')
                ->with(['files_id' => 1, 'n' => 1], ['unique' => true])
            ;
        }

        $this->schemaManager->ensureIndexes();
    }

    public function testEnsureDocumentIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection->expects($this->once())->method('createIndex');
            } else {
                $collection->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(CmsArticle::class);
    }

    public function testEnsureDocumentIndexesForGridFSFile()
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('createIndex');
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            if ($class === File::class) {
                $bucket->getFilesCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([])
                ;
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['filename' => 1, 'uploadDate' => 1])
                ;

                $bucket->getChunksCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([])
                ;
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['files_id' => 1, 'n' => 1], ['unique' => true])
                ;
            } else {
                $bucket->getFilesCollection()->expects($this->never())->method('createIndex');
                $bucket->getChunksCollection()->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(File::class);
    }

    public function testEnsureDocumentIndexesWithTwoLevelInheritance()
    {
        $collection = $this->documentCollections[CmsProduct::class];
        $collection->expects($this->once())->method('createIndex');

        $this->schemaManager->ensureDocumentIndexes(CmsProduct::class);
    }

    public function testEnsureDocumentIndexesWithTimeout()
    {
        $collection = $this->documentCollections[CmsArticle::class];
        $collection->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), $this->callback(function ($o) {
                return isset($o['timeout']) && $o['timeout'] === 10000;
            }));

        $this->schemaManager->ensureDocumentIndexes(CmsArticle::class, 10000);
    }

    public function testUpdateDocumentIndexesShouldCreateMappedIndexes()
    {
        $collection = $this->documentCollections[CmsArticle::class];
        $collection->expects($this->once())
            ->method('listIndexes')
            ->will($this->returnValue(new IndexInfoIteratorIterator(new \ArrayIterator([]))));
        $collection->expects($this->once())
            ->method('createIndex');
        $collection->expects($this->never())
            ->method('dropIndex');

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class);
    }

    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes()
    {
        $collection = $this->documentCollections[CmsArticle::class];
        $indexes = [[
            'v' => 1,
            'key' => ['topic' => -1],
            'name' => 'topic_-1',
        ],
        ];
        $collection->expects($this->once())
            ->method('listIndexes')
            ->will($this->returnValue(new IndexInfoIteratorIterator(new \ArrayIterator($indexes))));
        $collection->expects($this->once())
            ->method('createIndex');
        $collection->expects($this->once())
            ->method('dropIndex');

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class);
    }

    public function testDeleteIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection->expects($this->once())->method('dropIndexes');
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $collection->expects($this->never())->method('dropIndexes');
            }
        }

        $this->schemaManager->deleteIndexes();
    }

    public function testDeleteDocumentIndexes()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection->expects($this->once())->method('dropIndexes');
            } else {
                $collection->expects($this->never())->method('dropIndexes');
            }
        }

        $this->schemaManager->deleteDocumentIndexes(CmsArticle::class);
    }

    public function testCreateDocumentCollection()
    {
        $cm = $this->classMetadatas[CmsArticle::class];
        $cm->collectionCapped = true;
        $cm->collectionSize = 1048576;
        $cm->collectionMax = 32;

        $database = $this->documentDatabases[CmsArticle::class];
        $database->expects($this->once())
            ->method('createCollection')
            ->with(
                'CmsArticle',
                [
                    'capped' => true,
                    'size' => 1048576,
                    'max' => 32,
                ]
            )
        ;

        $this->schemaManager->createDocumentCollection(CmsArticle::class);
    }

    public function testCreateDocumentCollectionForFile()
    {
        $database = $this->documentDatabases[File::class];
        $database->expects($this->at(0))
            ->method('createCollection')
            ->with('fs.files')
        ;
        $database->expects($this->at(1))
            ->method('createCollection')
            ->with('fs.chunks')
        ;

        $this->schemaManager->createDocumentCollection(File::class);
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
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with();
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $collection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropCollections();
    }

    public function testDropDocumentCollection()
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with();
            } else {
                $collection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(CmsArticle::class);
    }

    public function testDropDocumentCollectionForGridFSFile()
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('drop');
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            if ($class === File::class) {
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('drop')
                ;
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('drop')
                ;
            } else {
                $bucket->getFilesCollection()->expects($this->never())->method('drop');
                $bucket->getChunksCollection()->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(File::class);
    }

    public function testDropDocumentDatabase()
    {
        foreach ($this->documentDatabases as $class => $database) {
            if ($class === CmsArticle::class) {
                $database->expects($this->once())->method('drop');
            } else {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->dm->getSchemaManager()->dropDocumentDatabase(CmsArticle::class);
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
            'key' => ['foo' => 1, 'bar' => -1],
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
            'keysSameButNumericTypesDiffer' => [
                'expected' => true,
                'mongoIndex' => ['key' => ['foo' => 1.0]],
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
            ],
            // Comparing non-text Mongo index with text document index
            'textIndex' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['keys' => ['foo' => 'text', 'bar' => 'text']],
            ],
        ];
    }

    /**
     * @dataProvider dataIsMongoTextIndexEquivalentToDocumentIndex
     */
    public function testIsMongoIndexEquivalentToDocumentIndexWithTextIndexes($expected, $mongoIndex, $documentIndex)
    {
        $defaultMongoIndex = [
            'key' => ['_fts' => 'text', '_ftsx' => 1],
            'weights' => ['bar' => 1, 'foo' => 1],
            'default_language' => 'english',
            'language_override' => 'language',
            'textIndexVersion' => 3,
        ];
        $defaultDocumentIndex = [
            'keys' => ['foo' => 'text', 'bar' => 'text'],
            'options' => [],
        ];

        $mongoIndex += $defaultMongoIndex;
        $documentIndex += $defaultDocumentIndex;

        $this->assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex));
    }

    public function dataIsMongoTextIndexEquivalentToDocumentIndex()
    {
        return [
            'keysSame' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['keys' => ['foo' => 'text', 'bar' => 'text']],
            ],
            'keysSameWithDifferentOrder' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['keys' => ['bar' => 'text', 'foo' => 'text']],
            ],
            'keysDiffer' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['keys' => ['x' => 'text', 'y' => 'text']],
            ],
            'compoundIndexKeysSameAndWeightsSame' => [
                'expected' => true,
                'mongoIndex' => [
                    'key' => ['_fts' => 'text', '_ftsx' => 1, 'a' => -1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1,  'b' => 'text', 'c' => 'text', 'd' => 1],
                    'options' => ['weights' => ['b' => 1, 'c' => 2]],
                ],
            ],
            'compoundIndexKeysSameAndWeightsDiffer' => [
                'expected' => false,
                'mongoIndex' => [
                    'key' => ['_fts' => 'text', '_ftsx' => 1, 'a' => -1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1,  'b' => 'text', 'c' => 'text', 'd' => 1],
                    'options' => ['weights' => ['b' => 3, 'c' => 2]],
                ],
            ],
            'compoundIndexKeysDifferAndWeightsSame' => [
                'expected' => false,
                'mongoIndex' => [
                    'key' => ['_fts' => 'text', '_ftsx' => 1, 'a' => 1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1,  'b' => 'text', 'c' => 'text', 'd' => 1],
                    'options' => ['weights' => ['b' => 1, 'c' => 2]],
                ],
            ],
            'weightsSame' => [
                'expected' => true,
                'mongoIndex' => [
                    'weights' => ['a' => 1, 'b' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => 'text', 'b' => 'text'],
                    'options' => ['weights' => ['a' => 1, 'b' => 2]],
                ],
            ],
            'weightsSameAfterSorting' => [
                'expected' => true,
                'mongoIndex' => [
                    /* MongoDB returns the weights sorted by field name, but
                     * simulate an unsorted response to test our own ksort(). */
                    'weights' => ['a' => 1, 'c' => 3, 'b' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => 'text', 'b' => 'text', 'c' => 'text'],
                    'options' => ['weights' => ['c' => 3, 'a' => 1, 'b' => 2]],
                ],
            ],
            'weightsSameButNumericTypesDiffer' => [
                'expected' => true,
                'mongoIndex' => [
                    'weights' => ['a' => 1, 'b' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => 'text', 'b' => 'text'],
                    'options' => ['weights' => ['a' => 1.0, 'b' => 2.0]],
                ],
            ],
            'defaultLanguageSame' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['default_language' => 'english']],
            ],
            'defaultLanguageMismatch' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['default_language' => 'german']],
            ],
            'languageOverrideSame' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['language_override' => 'language']],
            ],
            'languageOverrideMismatch' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['language_override' => 'idioma']],
            ],
            'textIndexVersionSame' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['textIndexVersion' => 3]],
            ],
            'textIndexVersionMismatch' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['textIndexVersion' => 2]],
            ],
        ];
    }

    public function testEnsureDocumentSharding()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $dbName = DOCTRINE_MONGODB_DATABASE;
        $classMetadata = $this->dm->getClassMetadata(ShardedUser::class);
        $collectionName = $classMetadata->getCollection();
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn($dbName);
        $adminDBMock = $this->getMockDatabase();
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $this->dm->documentDatabases = [$classMetadata->getName() => $dbMock];

        $adminDBMock
            ->expects($this->at(0))
            ->method('command')
            ->with(['enableSharding' => $dbName])
            ->willReturn(['ok' => 1]);
        $adminDBMock
            ->expects($this->at(1))
            ->method('command')
            ->with(['shardCollection' => $dbName . '.' . $collectionName, 'key' => ['_id' => 'hashed']])
            ->willReturn(['ok' => 1]);

        $this->schemaManager->ensureDocumentSharding($classMetadata->getName());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Failed to ensure sharding for document
     */
    public function testEnsureDocumentShardingThrowsExceptionIfThereWasAnError()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $dbName = DOCTRINE_MONGODB_DATABASE;
        $classMetadata = $this->dm->getClassMetadata(ShardedUser::class);
        $collectionName = $classMetadata->getCollection();
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn($dbName);
        $adminDBMock = $this->getMockDatabase();
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $this->dm->documentDatabases = [$classMetadata->getName() => $dbMock];

        $adminDBMock
            ->expects($this->at(0))
            ->method('command')
            ->with(['enableSharding' => $dbName])
            ->willReturn(['ok' => 1]);
        $adminDBMock
            ->expects($this->at(1))
            ->method('command')
            ->with(['shardCollection' => $dbName . '.' . $collectionName, 'key' => ['_id' => 'hashed']])
            ->willReturn(['ok' => 0, 'code' => 666, 'errmsg' => 'Scary error']);

        $this->schemaManager->ensureDocumentSharding($classMetadata->getName());
    }

    public function testEnsureDocumentShardingIgnoresAlreadyShardedError()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $dbName = DOCTRINE_MONGODB_DATABASE;
        $classMetadata = $this->dm->getClassMetadata(ShardedUser::class);
        $collectionName = $classMetadata->getCollection();
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn($dbName);
        $adminDBMock = $this->getMockDatabase();
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $this->dm->documentDatabases = [$classMetadata->getName() => $dbMock];

        $adminDBMock
            ->expects($this->at(0))
            ->method('command')
            ->with(['enableSharding' => $dbName])
            ->willReturn(['ok' => 1]);
        $adminDBMock
            ->expects($this->at(1))
            ->method('command')
            ->with(['shardCollection' => $dbName . '.' . $collectionName, 'key' => ['_id' => 'hashed']])
            ->willReturn(['ok' => 0, 'code' => 20, 'errmsg' => 'already sharded']);

        $this->schemaManager->ensureDocumentSharding($classMetadata->getName());
    }

    public function testEnableShardingForDb()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $adminDBMock = $this->getMockDatabase();
        $adminDBMock
            ->expects($this->once())
            ->method('command')
            ->with(['enableSharding' => 'db'])
            ->willReturn(['ok' => 1]);
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn('db');
        $this->dm->documentDatabases = [ShardedUser::class => $dbMock];

        $this->schemaManager->enableShardingForDbByDocumentName(ShardedUser::class);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Failed to enable sharding for database
     */
    public function testEnableShardingForDbThrowsExceptionInCaseOfError()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $adminDBMock = $this->getMockDatabase();
        $adminDBMock
            ->expects($this->once())
            ->method('command')
            ->with(['enableSharding' => 'db'])
            ->willReturn(['ok' => 0, 'code' => 666, 'errmsg' => 'Scary error']);
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn('db');
        $this->dm->documentDatabases = [ShardedUser::class => $dbMock];

        $this->schemaManager->enableShardingForDbByDocumentName(ShardedUser::class);
    }

    public function testEnableShardingForDbIgnoresAlreadyShardedError()
    {
        $this->markTestSkipped('Sharding support is still WIP');

        $adminDBMock = $this->getMockDatabase();
        $adminDBMock
            ->expects($this->once())
            ->method('command')
            ->with(['enableSharding' => 'db'])
            ->willReturn(['ok' => 0, 'code' => 23, 'errmsg' => 'already enabled']);
        $connMock = $this->getMockClient();
        $connMock->method('selectDatabase')->with('admin')->willReturn($adminDBMock);
        $this->dm->connection = $connMock;
        $dbMock = $this->getMockDatabase();
        $dbMock->method('getName')->willReturn('db');
        $this->dm->documentDatabases = [ShardedUser::class => $dbMock];

        $this->schemaManager->enableShardingForDbByDocumentName(ShardedUser::class);
    }

    /** @return Bucket|MockObject */
    private function getMockBucket()
    {
        $mock = $this->createMock(Bucket::class);
        $mock->expects($this->any())->method('getFilesCollection')->willReturn($this->getMockCollection());
        $mock->expects($this->any())->method('getChunksCollection')->willReturn($this->getMockCollection());

        return $mock;
    }

    /** @return Collection|MockObject */
    private function getMockCollection()
    {
        return $this->createMock(Collection::class);
    }

    /** @return Database|MockObject */
    private function getMockDatabase()
    {
        return $this->createMock(Database::class);
    }

    private function getMockDocumentManager()
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/../../../../Documents'));

        $em = $this->createMock(EventManager::class);

        $dm = new DocumentManagerMock();
        $dm->eventManager = $em;
        $dm->config = $config;

        return $dm;
    }

    private function getMockUnitOfWork()
    {
        $documentPersister = $this->createMock(DocumentPersister::class);

        $documentPersister->expects($this->any())
            ->method('prepareFieldName')
            ->will($this->returnArgument(0));

        $uow = $this->createMock(UnitOfWork::class);

        $uow->expects($this->any())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        return $uow;
    }

    private function getMockClient()
    {
        return $this->createMock(Client::class);
    }
}
