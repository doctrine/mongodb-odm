<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use ArrayIterator;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Proxy\ClassNameResolver;
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
use Documents\Sharded\ShardedOne;
use Documents\Sharded\ShardedOneWithDifferentKey;
use Documents\SimpleReferenceUser;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\WriteConcern;
use MongoDB\GridFS\Bucket;
use MongoDB\Model\IndexInfoIteratorIterator;
use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
        ShardedOne::class,
        ShardedOneWithDifferentKey::class,
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

    /** @var DocumentManagerMock */
    private $dm;

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
            $this->classMetadatas[$cm->name]    = $cm;
        }

        $this->dm->unitOfWork          = $this->getMockUnitOfWork();
        $this->dm->metadataFactory     = $cmf;
        $this->dm->documentCollections = $this->documentCollections;
        $this->dm->documentBuckets     = $this->documentBuckets;
        $this->dm->documentDatabases   = $this->documentDatabases;

        $this->schemaManager     = new SchemaManager($this->dm, $cmf);
        $this->dm->schemaManager = $this->schemaManager;
    }

    public static function getWriteOptions() : array
    {
        $writeConcern = new WriteConcern(1, 500, true);

        return [
            'noWriteOption' => [
                'expectedWriteOptions' => [],
                'maxTimeMs' => null,
                'writeConcern' => null,
            ],
            'onlyMaxTimeMs' => [
                'expectedWriteOptions' => ['maxTimeMs' => 1000],
                'maxTimeMs' => 1000,
                'writeConcern' => null,
            ],
            'onlyWriteConcern' => [
                'expectedWriteOptions' => ['writeConcern' => $writeConcern],
                'maxTimeMs' => null,
                'writeConcern' => $writeConcern,
            ],
            'maxTimeMsAndWriteConern' => [
                'expectedWriteOptions' => ['maxTimeMs' => 1000, 'writeConcern' => $writeConcern],
                'maxTimeMs' => 1000,
                'writeConcern' => $writeConcern,
            ],
        ];
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testEnsureIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with($this->anything(), new ArraySubset($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('createIndex');
            }
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            $bucket->getFilesCollection()
                ->expects($this->any())
                ->method('listIndexes')
                ->willReturn([]);
            $bucket->getFilesCollection()
                ->expects($this->once())
                ->method('createIndex')
                ->with(['filename' => 1, 'uploadDate' => 1], new ArraySubset($expectedWriteOptions));

            $bucket->getChunksCollection()
                ->expects($this->any())
                ->method('listIndexes')
                ->willReturn([]);
            $bucket->getChunksCollection()
                ->expects($this->once())
                ->method('createIndex')
                ->with(['files_id' => 1, 'n' => 1], new ArraySubset(['unique' => true] + $expectedWriteOptions));
        }

        $this->schemaManager->ensureIndexes($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testEnsureDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with($this->anything(), new ArraySubset($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testEnsureDocumentIndexesForGridFSFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('createIndex');
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            if ($class === File::class) {
                $bucket->getFilesCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([]);
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['filename' => 1, 'uploadDate' => 1], new ArraySubset($expectedWriteOptions));

                $bucket->getChunksCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([]);
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['files_id' => 1, 'n' => 1], new ArraySubset(['unique' => true] + $expectedWriteOptions));
            } else {
                $bucket->getFilesCollection()->expects($this->never())->method('createIndex');
                $bucket->getChunksCollection()->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(File::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testEnsureDocumentIndexesWithTwoLevelInheritance(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $collection = $this->documentCollections[CmsProduct::class];
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), new ArraySubset($expectedWriteOptions));

        $this->schemaManager->ensureDocumentIndexes(CmsProduct::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testUpdateDocumentIndexesShouldCreateMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $collection = $this->documentCollections[CmsArticle::class];
        $collection
            ->expects($this->once())
            ->method('listIndexes')
            ->will($this->returnValue(new IndexInfoIteratorIterator(new ArrayIterator([]))));
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), new ArraySubset($expectedWriteOptions));
        $collection
            ->expects($this->never())
            ->method('dropIndex')
            ->with(new ArraySubset($expectedWriteOptions));

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $collection = $this->documentCollections[CmsArticle::class];
        $indexes    = [
            [
                'v' => 1,
                'key' => ['topic' => -1],
                'name' => 'topic_-1',
            ],
        ];

        $collection
            ->expects($this->once())
            ->method('listIndexes')
            ->will($this->returnValue(new IndexInfoIteratorIterator(new ArrayIterator($indexes))));
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), new ArraySubset($expectedWriteOptions));
        $collection
            ->expects($this->once())
            ->method('dropIndex')
            ->with($this->anything(), new ArraySubset($expectedWriteOptions));

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDeleteIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses)) {
                $collection
                    ->expects($this->once())
                    ->method('dropIndexes')
                    ->with(new ArraySubset($expectedWriteOptions));
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $collection->expects($this->never())->method('dropIndexes');
            }
        }

        $this->schemaManager->deleteIndexes($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDeleteDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection
                    ->expects($this->once())
                    ->method('dropIndexes')
                    ->with(new ArraySubset($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('dropIndexes');
            }
        }

        $this->schemaManager->deleteDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateDocumentCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $cm                   = $this->classMetadatas[CmsArticle::class];
        $cm->collectionCapped = true;
        $cm->collectionSize   = 1048576;
        $cm->collectionMax    = 32;

        $options = [
            'capped' => true,
            'size' => 1048576,
            'max' => 32,
        ];

        $database = $this->documentDatabases[CmsArticle::class];
        $database->expects($this->once())
            ->method('createCollection')
            ->with(
                'CmsArticle',
                new ArraySubset($options + $expectedWriteOptions)
            );

        $this->schemaManager->createDocumentCollection(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateDocumentCollectionForFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $database = $this->documentDatabases[File::class];
        $database
            ->expects($this->at(0))
            ->method('createCollection')
            ->with('fs.files', new ArraySubset($expectedWriteOptions));
        $database->expects($this->at(1))
            ->method('createCollection')
            ->with('fs.chunks', new ArraySubset($expectedWriteOptions));

        $this->schemaManager->createDocumentCollection(File::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentDatabases as $class => $database) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $database
                    ->expects($this->once())
                    ->method('createCollection')
                    ->with($this->anything(), new ArraySubset($expectedWriteOptions));
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $database->expects($this->never())->method('createCollection');
            }
        }

        $this->schemaManager->createCollections($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $collection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropCollections($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDocumentCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            if ($class === CmsArticle::class) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDocumentCollectionForGridFSFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('drop');
        }

        foreach ($this->documentBuckets as $class => $bucket) {
            if ($class === File::class) {
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
            } else {
                $bucket->getFilesCollection()->expects($this->never())->method('drop');
                $bucket->getChunksCollection()->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(File::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDocumentDatabase(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentDatabases as $class => $database) {
            if ($class === CmsArticle::class) {
                $database
                    ->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
            } else {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->dm->getSchemaManager()->dropDocumentDatabase(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDatabases(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentDatabases as $class => $database) {
            if (in_array($class, $this->indexedClasses + $this->someNonIndexedClasses)) {
                $database
                    ->expects($this->once())
                    ->method('drop')
                    ->with(new ArraySubset($expectedWriteOptions));
            } elseif (in_array($class, $this->someMappedSuperclassAndEmbeddedClasses)) {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDatabases($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider dataIsMongoIndexEquivalentToDocumentIndex
     */
    public function testIsMongoIndexEquivalentToDocumentIndex($expected, $mongoIndex, $documentIndex)
    {
        $defaultMongoIndex    = [
            'key' => ['foo' => 1, 'bar' => -1],
        ];
        $defaultDocumentIndex = [
            'keys' => ['foo' => 1, 'bar' => -1],
            'options' => [],
        ];

        $mongoIndex    += $defaultMongoIndex;
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
        $defaultMongoIndex    = [
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

        $mongoIndex    += $defaultMongoIndex;
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

    /** @return Bucket|MockObject */
    private function getMockBucket()
    {
        $mock = $this->createMock(Bucket::class);
        $mock->expects($this->any())->method('getFilesCollection')->willReturn($this->getMockCollection());
        $mock->expects($this->any())->method('getChunksCollection')->willReturn($this->getMockCollection());

        return $mock;
    }

    private function getMockCollection()
    {
        return $this->createMock(Collection::class);
    }

    /** @return Database|MockObject */
    private function getMockDatabase()
    {
        return $this->createMock(Database::class);
    }

    private function getMockDocumentManager() : DocumentManagerMock
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/../../../../Documents'));

        $em = $this->createMock(EventManager::class);

        $dm                    = new DocumentManagerMock();
        $dm->eventManager      = $em;
        $dm->config            = $config;
        $dm->client            = $this->createMock(Client::class);
        $dm->classNameResolver = new ClassNameResolver($config);

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
}
