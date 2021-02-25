<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use ArrayIterator;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\SchemaManager;
use Documents\CmsAddress;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\CmsProduct;
use Documents\Comment;
use Documents\File;
use Documents\Sharded\ShardedOne;
use Documents\Sharded\ShardedOneWithDifferentKey;
use Documents\SimpleReferenceUser;
use Documents\UserName;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\WriteConcern;
use MongoDB\GridFS\Bucket;
use MongoDB\Model\IndexInfo;
use MongoDB\Model\IndexInfoIteratorIterator;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;

use function array_map;
use function assert;
use function class_exists;
use function in_array;

class SchemaManagerTest extends BaseTest
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

    private $views = [
        UserName::class,
    ];

    /** @var Collection[]|MockObject[] */
    private $documentCollections = [];

    /** @var Bucket[]|MockObject[] */
    private $documentBuckets = [];

    /** @var Database[]|MockObject[] */
    private $documentDatabases = [];

    /** @var SchemaManager */
    private $schemaManager;

    public function setUp(): void
    {
        parent::setUp();

        $client   = $this->createMock(Client::class);
        $this->dm = DocumentManager::create($client, $this->dm->getConfiguration(), $this->createMock(EventManager::class));

        foreach ($this->dm->getMetadataFactory()->getAllMetadata() as $cm) {
            assert($cm instanceof ClassMetadata);
            if ($cm->isMappedSuperclass || $cm->isEmbeddedDocument || $cm->isQueryResultDocument) {
                continue;
            }

            if ($cm->isFile) {
                $this->documentBuckets[$cm->getBucketName()] = $this->getMockBucket();
            } else {
                $this->documentCollections[$cm->getCollection()] = $this->getMockCollection();
            }

            $db = $this->getDatabaseName($cm);
            if (isset($this->documentDatabases[$db])) {
                continue;
            }

            $this->documentDatabases[$db] = $this->getMockDatabase();
        }

        $client->method('selectDatabase')->willReturnCallback(function (string $db) {
            return $this->documentDatabases[$db];
        });

        $this->schemaManager = $this->dm->getSchemaManager();
    }

    public function tearDown(): void
    {
        // do not call parent, client here is mocked and there's nothing to tidy up in the database
    }

    public static function getWriteOptions(): array
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
            'maxTimeMsAndWriteConcern' => [
                'expectedWriteOptions' => ['maxTimeMs' => 1000, 'writeConcern' => $writeConcern],
                'maxTimeMs' => 1000,
                'writeConcern' => $writeConcern,
            ],
        ];
    }

    public static function getIndexCreationWriteOptions(): array
    {
        $originalOptionsWithBackground = array_map(static function (array $arguments): array {
            $arguments['expectedWriteOptions']['background'] = false;

            return $arguments;
        }, self::getWriteOptions());

        return $originalOptionsWithBackground + [
            'backgroundOptionSet' => [
                'expectedWriteOptions' => ['background' => true],
                'maxTimeMs' => null,
                'writeConcern' => null,
                'background' => true,
            ],
        ];
    }

    /**
     * @dataProvider getIndexCreationWriteOptions
     */
    public function testEnsureIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        $indexedCollections = array_map(
            function (string $fqcn) {
                return $this->dm->getClassMetadata($fqcn)->getCollection();
            },
            $this->indexedClasses
        );
        foreach ($this->documentCollections as $collectionName => $collection) {
            if (in_array($collectionName, $indexedCollections)) {
                $collection
                    ->expects($this->atLeastOnce())
                    ->method('createIndex')
                    ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
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
                ->expects($this->atLeastOnce())
                ->method('createIndex')
                ->with(['filename' => 1, 'uploadDate' => 1], $this->writeOptions($expectedWriteOptions));

            $bucket->getChunksCollection()
                ->expects($this->any())
                ->method('listIndexes')
                ->willReturn([]);
            $bucket->getChunksCollection()
                ->expects($this->atLeastOnce())
                ->method('createIndex')
                ->with(['files_id' => 1, 'n' => 1], $this->writeOptions(['unique' => true] + $expectedWriteOptions));
        }

        $this->schemaManager->ensureIndexes($maxTimeMs, $writeConcern, $background);
    }

    /**
     * @dataProvider getIndexCreationWriteOptions
     */
    public function testEnsureDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        $cmsArticleCollectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $cmsArticleCollectionName) {
                $collection
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern, $background);
    }

    /**
     * @dataProvider getIndexCreationWriteOptions
     */
    public function testEnsureDocumentIndexesForGridFSFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('createIndex');
        }

        $fileBucket = $this->dm->getClassMetadata(File::class)->getBucketName();
        foreach ($this->documentBuckets as $class => $bucket) {
            if ($class === $fileBucket) {
                $bucket->getFilesCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([]);
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['filename' => 1, 'uploadDate' => 1], $this->writeOptions($expectedWriteOptions));

                $bucket->getChunksCollection()
                    ->expects($this->any())
                    ->method('listIndexes')
                    ->willReturn([]);
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['files_id' => 1, 'n' => 1], $this->writeOptions(['unique' => true] + $expectedWriteOptions));
            } else {
                $bucket->getFilesCollection()->expects($this->never())->method('createIndex');
                $bucket->getChunksCollection()->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(File::class, $maxTimeMs, $writeConcern, $background);
    }

    /**
     * @dataProvider getIndexCreationWriteOptions
     */
    public function testEnsureDocumentIndexesWithTwoLevelInheritance(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false)
    {
        $collectionName = $this->dm->getClassMetadata(CmsProduct::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), $this->writeOptions($expectedWriteOptions));

        $this->schemaManager->ensureDocumentIndexes(CmsProduct::class, $maxTimeMs, $writeConcern, $background);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testUpdateDocumentIndexesShouldCreateMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listIndexes')
            ->will($this->returnValue(new IndexInfoIteratorIterator(new ArrayIterator([]))));
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
        $collection
            ->expects($this->never())
            ->method('dropIndex')
            ->with($this->writeOptions($expectedWriteOptions));

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $indexes        = [
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
            ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
        $collection
            ->expects($this->once())
            ->method('dropIndex')
            ->with($this->anything(), $this->writeOptions($expectedWriteOptions));

        $this->schemaManager->updateDocumentIndexes(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDeleteIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $views = array_map(
            function (string $fqcn) {
                return $this->dm->getClassMetadata($fqcn)->getCollection();
            },
            $this->views
        );

        foreach ($this->documentCollections as $collectionName => $collection) {
            if (in_array($collectionName, $views)) {
                $collection->expects($this->never())->method('dropIndexes');
            } else {
                $collection
                    ->expects($this->atLeastOnce())
                    ->method('dropIndexes')
                    ->with($this->writeOptions($expectedWriteOptions));
            }
        }

        $this->schemaManager->deleteIndexes($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDeleteDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $cmsArticleCollectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $cmsArticleCollectionName) {
                $collection
                    ->expects($this->once())
                    ->method('dropIndexes')
                    ->with($this->writeOptions($expectedWriteOptions));
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
        $cm                   = $this->dm->getClassMetadata(CmsArticle::class);
        $cm->collectionCapped = true;
        $cm->collectionSize   = 1048576;
        $cm->collectionMax    = 32;

        $options = [
            'capped' => true,
            'size' => 1048576,
            'max' => 32,
        ];

        $database = $this->documentDatabases[$this->getDatabaseName($cm)];
        $database->expects($this->once())
            ->method('createCollection')
            ->with(
                'CmsArticle',
                $this->writeOptions($options + $expectedWriteOptions)
            );

        $this->schemaManager->createDocumentCollection(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateDocumentCollectionForFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $database = $this->documentDatabases[$this->getDatabaseName($this->dm->getClassMetadata(File::class))];
        $database
            ->expects($this->exactly(2))
            ->method('createCollection')
            ->withConsecutive(
                ['fs.files', $this->writeOptions($expectedWriteOptions)],
                ['fs.chunks', $this->writeOptions($expectedWriteOptions)]
            );

        $this->schemaManager->createDocumentCollection(File::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateView(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $cm = $this->dm->getClassMetadata(UserName::class);

        $options = [];

        $database = $this->documentDatabases[$this->getDatabaseName($cm)];
        $database
            ->expects($this->never())
            ->method('createCollection');

        $database->expects($this->once())
            ->method('command')
            ->with(
                [
                    'create' => 'user-name',
                    'viewOn' => 'CmsUser',
                    'pipeline' => [
                        [
                            '$project' => ['username' => true],
                        ],
                    ],
                ],
                $this->writeOptions($options + $expectedWriteOptions)
            );

        $rootCollection = $this->documentCollections['CmsUser'];
        $rootCollection
            ->method('getCollectionName')
            ->willReturn('CmsUser');

        $this->schemaManager->createDocumentCollection(UserName::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testCreateCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentDatabases as $class => $database) {
            $database
                ->expects($this->atLeastOnce())
                ->method('createCollection')
                ->with($this->anything(), $this->writeOptions($expectedWriteOptions));

            $database
                ->expects($this->atLeastOnce())
                ->method('command')
                ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
        }

        $this->schemaManager->createCollections($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentCollections as $collection) {
            $collection->expects($this->atLeastOnce())
                ->method('drop')
                ->with($this->writeOptions($expectedWriteOptions));
        }

        $this->schemaManager->dropCollections($maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDocumentCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $cmsArticleCollectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $cmsArticleCollectionName) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
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
        foreach ($this->documentCollections as $collection) {
            $collection->expects($this->never())->method('drop');
        }

        $fileBucketName = $this->dm->getClassMetadata(File::class)->getBucketName();
        foreach ($this->documentBuckets as $bucketName => $bucket) {
            if ($bucketName === $fileBucketName) {
                $bucket->getFilesCollection()
                    ->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
                $bucket->getChunksCollection()
                    ->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
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
    public function testDropView(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $viewName = $this->dm->getClassMetadata(UserName::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $viewName) {
                $collection->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
            } else {
                $collection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(UserName::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDocumentDatabase(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $cmsArticleDatabaseName = $this->getDatabaseName($this->dm->getClassMetadata(CmsArticle::class));
        foreach ($this->documentDatabases as $databaseName => $database) {
            if ($databaseName === $cmsArticleDatabaseName) {
                $database
                    ->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
            } else {
                $database->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentDatabase(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /**
     * @dataProvider getWriteOptions
     */
    public function testDropDatabases(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        foreach ($this->documentDatabases as $database) {
            $database
                ->expects($this->atLeastOnce())
                ->method('drop')
                ->with($this->writeOptions($expectedWriteOptions));
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

        $this->assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex(new IndexInfo($mongoIndex), $documentIndex));
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
            'compoundIndexKeysSame' => [
                'expected' => true,
                'mongoIndex' => ['key' => ['foo' => 1, 'baz' => 1]],
                'documentIndex' => ['keys' => ['foo' => 1, 'baz' => 1]],
            ],
            'compoundIndexKeysSameDifferentOrder' => [
                'expected' => false,
                'mongoIndex' => ['key' => ['foo' => 1, 'baz' => 1]],
                'documentIndex' => ['keys' => ['baz' => 1, 'foo' => 1]],
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
            // geoHaystack index options
            'geoHaystackOptionsDifferent' => [
                'expected' => false,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['bucketSize' => 16]],
            ],
            // index name autogenerated
            'indexNameAutogenerated' => [
                'expected' => true,
                'mongoIndex' => ['name' => 'foo_1_bar_1'],
                'documentIndex' => [],
            ],
            // background option
            'backgroundOptionOnlyInMongoIndex' => [
                'expected' => true,
                'mongoIndex' => ['background' => false],
                'documentIndex' => [],
            ],
            'backgroundOptionOnlyInDocumentIndex' => [
                'expected' => true,
                'mongoIndex' => [],
                'documentIndex' => ['options' => ['background' => false]],
            ],
            'backgroundOptionOnlyInBothIndexesMismatch' => [
                'expected' => true,
                'mongoIndex' => ['background' => false],
                'documentIndex' => ['options' => ['background' => true]],
            ],
            'backgroundOptionOnlyInBothIndexesSame' => [
                'expected' => true,
                'mongoIndex' => ['background' => true],
                'documentIndex' => ['options' => ['background' => true]],
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

        $this->assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex(new IndexInfo($mongoIndex), $documentIndex));
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
                    'key' => ['a' => -1, '_fts' => 'text', '_ftsx' => 1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1, 'b' => 'text', 'c' => 'text',  'd' => 1],
                    'options' => ['weights' => ['b' => 1, 'c' => 2]],
                ],
            ],
            'compoundIndexKeysDifferentOrder' => [
                'expected' => false,
                'mongoIndex' => [
                    'key' => ['_fts' => 'text', '_ftsx' => 1, 'a' => -1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1, 'b' => 'text', 'c' => 'text', 'd' => 1],
                    'options' => ['weights' => ['b' => 1, 'c' => 2]],
                ],
            ],
            'compoundIndexKeysSameAndWeightsDiffer' => [
                'expected' => false,
                'mongoIndex' => [
                    'key' => ['a' => -1, '_fts' => 'text', '_ftsx' => 1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1, 'b' => 'text', 'c' => 'text', 'd' => 1],
                    'options' => ['weights' => ['b' => 3, 'c' => 2]],
                ],
            ],
            'compoundIndexKeysDifferAndWeightsSame' => [
                'expected' => false,
                'mongoIndex' => [
                    'key' => ['a' => 1, '_fts' => 'text', '_ftsx' => 1, 'd' => 1],
                    'weights' => ['b' => 1, 'c' => 2],
                ],
                'documentIndex' => [
                    'keys' => ['a' => -1, 'b' => 'text', 'c' => 'text', 'd' => 1],
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

    private function getDatabaseName(ClassMetadata $cm): string
    {
        return $cm->getDatabase() ?: $this->dm->getConfiguration()->getDefaultDB() ?: 'doctrine';
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
        $db = $this->createMock(Database::class);
        $db->method('selectCollection')->willReturnCallback(function (string $collection) {
            return $this->documentCollections[$collection];
        });
        $db->method('selectGridFSBucket')->willReturnCallback(function (array $options) {
            return $this->documentBuckets[$options['bucketName']];
        });

        return $db;
    }

    private function writeOptions(array $expectedWriteOptions): Constraint
    {
        if (class_exists(ArraySubset::class)) {
            return new ArraySubset($expectedWriteOptions);
        }

        return new Callback(static function ($value) use ($expectedWriteOptions) {
            foreach ($expectedWriteOptions as $writeOption => $expectedValue) {
                if (! (new ArrayHasKey($writeOption))->evaluate($value, '', true)) {
                    return false;
                }

                if (! (new IsEqual($expectedValue))->evaluate($value[$writeOption], '', true)) {
                    return false;
                }
            }

            return true;
        });
    }
}
