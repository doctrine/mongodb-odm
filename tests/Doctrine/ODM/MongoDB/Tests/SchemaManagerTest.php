<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use ArrayIterator;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;
use Doctrine\ODM\MongoDB\SchemaManager;
use Documents\BaseDocument;
use Documents\CmsAddress;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\CmsProduct;
use Documents\Comment;
use Documents\File;
use Documents\SchemaValidated;
use Documents\Sharded\ShardedOne;
use Documents\Sharded\ShardedOneWithDifferentKey;
use Documents\SimpleReferenceUser;
use Documents\TimeSeries\TimeSeriesDocument;
use Documents\Tournament\Tournament;
use Documents\UserName;
use InvalidArgumentException;
use MongoDB\BSON\Document;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Driver\WriteConcern;
use MongoDB\GridFS\Bucket;
use MongoDB\Model\CollectionInfo;
use MongoDB\Model\IndexInfo;
use MongoDB\Model\IndexInfoIteratorIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;

use function array_count_values;
use function array_map;
use function assert;
use function in_array;

/**
 * @phpstan-import-type IndexMapping from ClassMetadata
 * @phpstan-import-type IndexOptions from ClassMetadata
 */
class SchemaManagerTest extends BaseTestCase
{
    /** @var list<class-string> */
    private array $indexedClasses = [
        CmsAddress::class,
        CmsArticle::class,
        CmsComment::class,
        CmsProduct::class,
        Comment::class,
        SimpleReferenceUser::class,
        ShardedOne::class,
        ShardedOneWithDifferentKey::class,
    ];

    /** @var list<class-string> */
    private array $searchIndexedClasses = [
        CmsAddress::class,
        CmsArticle::class,
    ];

    /** @var list<class-string> */
    private array $views = [
        UserName::class,
    ];

    /** @var array<Collection&MockObject> */
    private array $documentCollections = [];

    /** @var array<Bucket&MockObject> */
    private array $documentBuckets = [];

    /** @var array<Database&MockObject> */
    private array $documentDatabases = [];

    private SchemaManager $schemaManager;

    public function setUp(): void
    {
        parent::setUp();

        $client   = $this->createMock(Client::class);
        $this->dm = DocumentManager::create($client, $this->dm->getConfiguration(), $this->createMock(EventManager::class));

        foreach ($this->dm->getMetadataFactory()->getAllMetadata() as $cm) {
            if ($cm->isMappedSuperclass || $cm->isEmbeddedDocument || $cm->isQueryResultDocument) {
                continue;
            }

            if ($cm->isFile) {
                $this->documentBuckets[$cm->getBucketName()] = $this->getMockBucket();
            } else {
                $this->documentCollections[$cm->getCollection()] = $this->getMockCollection($cm->getCollection());
            }

            $db = $this->getDatabaseName($cm);
            if (isset($this->documentDatabases[$db])) {
                continue;
            }

            $this->documentDatabases[$db] = $this->getMockDatabase();
        }

        $client->method('selectDatabase')->willReturnCallback(fn (string $db) => $this->documentDatabases[$db]);

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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getIndexCreationWriteOptions')]
    public function testEnsureIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
    {
        $indexedCollections = array_map(
            fn (string $fqcn) => $this->dm->getClassMetadata($fqcn)->getCollection(),
            $this->indexedClasses,
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
            $filesCollection = $bucket->getFilesCollection();
            assert($filesCollection instanceof Collection && $filesCollection instanceof MockObject);

            $chunksCollection = $bucket->getChunksCollection();
            assert($chunksCollection instanceof Collection && $chunksCollection instanceof MockObject);

            $filesCollection
                ->method('listIndexes')
                ->willReturn([]);
            $filesCollection
                ->expects($this->atLeastOnce())
                ->method('createIndex')
                ->with(['filename' => 1, 'uploadDate' => 1], $this->writeOptions($expectedWriteOptions));

            $chunksCollection
                ->method('listIndexes')
                ->willReturn([]);
            $chunksCollection
                ->expects($this->atLeastOnce())
                ->method('createIndex')
                ->with(['files_id' => 1, 'n' => 1], $this->writeOptions(['unique' => true] + $expectedWriteOptions));
        }

        $this->schemaManager->ensureIndexes($maxTimeMs, $writeConcern, $background);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getIndexCreationWriteOptions')]
    public function testEnsureDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getIndexCreationWriteOptions')]
    public function testEnsureDocumentIndexesForGridFSFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
    {
        foreach ($this->documentCollections as $class => $collection) {
            $collection->expects($this->never())->method('createIndex');
        }

        $fileBucket = $this->dm->getClassMetadata(File::class)->getBucketName();
        foreach ($this->documentBuckets as $class => $bucket) {
            $filesCollection = $bucket->getFilesCollection();
            assert($filesCollection instanceof Collection && $filesCollection instanceof MockObject);

            $chunksCollection = $bucket->getChunksCollection();
            assert($chunksCollection instanceof Collection && $chunksCollection instanceof MockObject);

            if ($class === $fileBucket) {
                $filesCollection
                    ->method('listIndexes')
                    ->willReturn([]);
                $filesCollection
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['filename' => 1, 'uploadDate' => 1], $this->writeOptions($expectedWriteOptions));

                $chunksCollection
                    ->method('listIndexes')
                    ->willReturn([]);
                $chunksCollection
                    ->expects($this->once())
                    ->method('createIndex')
                    ->with(['files_id' => 1, 'n' => 1], $this->writeOptions(['unique' => true] + $expectedWriteOptions));
            } else {
                $filesCollection->expects($this->never())->method('createIndex');
                $chunksCollection->expects($this->never())->method('createIndex');
            }
        }

        $this->schemaManager->ensureDocumentIndexes(File::class, $maxTimeMs, $writeConcern, $background);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getIndexCreationWriteOptions')]
    public function testEnsureDocumentIndexesWithTwoLevelInheritance(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
    {
        $collectionName = $this->dm->getClassMetadata(CmsProduct::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('createIndex')
            ->with($this->anything(), $this->writeOptions($expectedWriteOptions));

        $this->schemaManager->ensureDocumentIndexes(CmsProduct::class, $maxTimeMs, $writeConcern, $background);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testUpdateDocumentIndexesShouldCreateMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listIndexes')
            ->willReturn(new IndexInfoIteratorIterator(new ArrayIterator([])));
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testUpdateDocumentIndexesShouldDeleteUnmappedIndexesBeforeCreatingMappedIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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
            ->willReturn(new IndexInfoIteratorIterator(new ArrayIterator($indexes)));
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDeleteIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $views = array_map(
            fn (string $fqcn) => $this->dm->getClassMetadata($fqcn)->getCollection(),
            $this->views,
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDeleteDocumentIndexes(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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

    public function testCreateSearchIndexes(): void
    {
        $searchIndexedCollections = array_map(
            fn (string $fqcn) => $this->dm->getClassMetadata($fqcn)->getCollection(),
            $this->searchIndexedClasses,
        );
        foreach ($this->documentCollections as $collectionName => $collection) {
            if (in_array($collectionName, $searchIndexedCollections)) {
                $collection
                    ->expects($this->once())
                    ->method('createSearchIndexes')
                    ->with($this->anything())
                    ->willReturn(['default']);
            } else {
                $collection->expects($this->never())->method('createSearchIndexes');
            }
        }

        $this->schemaManager->createSearchIndexes();
    }

    public function testCreateDocumentSearchIndexes(): void
    {
        $cmsArticleCollectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $cmsArticleCollectionName) {
                $collection
                    ->expects($this->once())
                    ->method('createSearchIndexes')
                    ->with($this->anything())
                    ->willReturn(['default']);
            } else {
                $collection->expects($this->never())->method('createSearchIndexes');
            }
        }

        $this->schemaManager->createDocumentSearchIndexes(CmsArticle::class);
    }

    public function testCreateDocumentSearchIndexesNotSupported(): void
    {
        $exception = $this->createSearchIndexCommandException();

        $cmsArticleCollectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        foreach ($this->documentCollections as $collectionName => $collection) {
            if ($collectionName === $cmsArticleCollectionName) {
                $collection
                    ->expects($this->once())
                    ->method('createSearchIndexes')
                    ->with($this->anything())
                    ->willThrowException($exception);
            } else {
                $collection->expects($this->never())->method('createSearchIndexes');
            }
        }

        $this->expectExceptionObject($exception);
        $this->schemaManager->createDocumentSearchIndexes(CmsArticle::class);
    }

    public function testUpdateDocumentSearchIndexes(): void
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willReturn(new ArrayIterator([
                ['name' => 'default'],
                ['name' => 'foo'],
            ]));
        $collection
            ->expects($this->once())
            ->method('dropSearchIndex')
            ->with('foo');
        $collection
            ->expects($this->once())
            ->method('updateSearchIndex')
            ->with('default', $this->anything());

        $this->schemaManager->updateDocumentSearchIndexes(CmsArticle::class);
    }

    public function testUpdateDocumentSearchIndexesNotSupportedForClassWithoutSearchIndexes(): void
    {
        // Class has no search indexes, so if the server doesn't support them we assume everything is fine
        $collectionName = $this->dm->getClassMetadata(CmsProduct::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willThrowException($this->createSearchIndexCommandException());
        $collection
            ->expects($this->never())
            ->method('dropSearchIndex');
        $collection
            ->expects($this->never())
            ->method('updateSearchIndex');

        $this->schemaManager->updateDocumentSearchIndexes(CmsProduct::class);
    }

    public function testUpdateDocumentSearchIndexesNotSupportedForClassWithoutSearchIndexesOnOlderServers(): void
    {
        // Class has no search indexes, so if the server doesn't support them we assume everything is fine
        $collectionName = $this->dm->getClassMetadata(CmsProduct::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willThrowException($this->createSearchIndexCommandExceptionForOlderServers());
        $collection
            ->expects($this->never())
            ->method('dropSearchIndex');
        $collection
            ->expects($this->never())
            ->method('updateSearchIndex');

        $this->schemaManager->updateDocumentSearchIndexes(CmsProduct::class);
    }

    public function testUpdateDocumentSearchIndexesNotSupportedForClassWithSearchIndexes(): void
    {
        $exception = $this->createSearchIndexCommandException();

        // This class has search indexes, so we do expect an exception
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willThrowException($exception);
        $collection
            ->expects($this->never())
            ->method('dropSearchIndex');
        $collection
            ->expects($this->never())
            ->method('updateSearchIndex');

        $this->expectExceptionObject($exception);
        $this->schemaManager->updateDocumentSearchIndexes(CmsArticle::class);
    }

    public function testDeleteDocumentSearchIndexes(): void
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willReturn(new ArrayIterator([['name' => 'default']]));
        $collection
            ->expects($this->once())
            ->method('dropSearchIndex')
            ->with('default');

        $this->schemaManager->deleteDocumentSearchIndexes(CmsArticle::class);
    }

    public function testDeleteDocumentSearchIndexesNotSupported(): void
    {
        $collectionName = $this->dm->getClassMetadata(CmsArticle::class)->getCollection();
        $collection     = $this->documentCollections[$collectionName];
        $collection
            ->expects($this->once())
            ->method('listSearchIndexes')
            ->willThrowException($this->createSearchIndexCommandException());
        $collection
            ->expects($this->never())
            ->method('dropSearchIndex');

        $this->schemaManager->deleteDocumentSearchIndexes(CmsArticle::class);
    }

    public function testUpdateValidators(): void
    {
        $dbCommands = [];
        foreach ($this->dm->getMetadataFactory()->getAllMetadata() as $cm) {
            if ($cm->isMappedSuperclass || $cm->isEmbeddedDocument || $cm->isQueryResultDocument || $cm->isView() || $cm->isFile) {
                continue;
            }

            $databaseName              = $this->getDatabaseName($cm);
            $dbCommands[$databaseName] = empty($dbCommands[$databaseName]) ? 1 : $dbCommands[$databaseName] + 1;
        }

        foreach ($dbCommands as $databaseName => $nbCommands) {
            $this->documentDatabases[$databaseName]
                ->expects($this->exactly($nbCommands))
                ->method('command');
        }

        $this->schemaManager->updateValidators();
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testUpdateDocumentValidator(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $class                 = $this->dm->getClassMetadata(SchemaValidated::class);
        $database              = $this->documentDatabases[$this->getDatabaseName($class)];
        $expectedValidatorJson = <<<'EOT'
{
    "$jsonSchema": {
        "required": ["name"],
        "properties": {
            "name": {
                "bsonType": "string",
                "description": "must be a string and is required"
            }
        }
    },
    "$or": [
        { "phone": { "$type": "string" } },
        { "email": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } },
        { "status": { "$in": [ "Unknown", "Incomplete" ] } }
    ]
}
EOT;
        $expectedValidator     = Document::fromJSON($expectedValidatorJson)->toPHP();
        $database
            ->expects($this->once())
            ->method('command')
            ->with(
                [
                    'collMod' => $class->collection,
                    'validator' => $expectedValidator,
                    'validationAction' => ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
                    'validationLevel' => ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
                ],
                $expectedWriteOptions,
            );
        $this->schemaManager->updateDocumentValidator($class->name, $maxTimeMs, $writeConcern);
    }

    public function testUpdateDocumentValidatorShouldThrowExceptionForMappedSuperclass(): void
    {
        $class = $this->dm->getClassMetadata(BaseDocument::class);
        $this->expectException(InvalidArgumentException::class);
        $this->schemaManager->updateDocumentValidator($class->name);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testUpdateDocumentValidatorReset(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $class    = $this->dm->getClassMetadata(CmsArticle::class);
        $database = $this->documentDatabases[$this->getDatabaseName($class)];
        $database
            ->expects($this->once())
            ->method('command')
            ->with(
                [
                    'collMod' => $class->collection,
                    'validator' => (object) [],
                    'validationAction' => ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
                    'validationLevel' => ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
                ],
                $expectedWriteOptions,
            );
        $this->schemaManager->updateDocumentValidator($class->name, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateDocumentCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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
                $this->writeOptions($options + $expectedWriteOptions),
            );

        $this->schemaManager->createDocumentCollection(CmsArticle::class, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateDocumentCollectionForFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $database = $this->documentDatabases[$this->getDatabaseName($this->dm->getClassMetadata(File::class))];
        $database
            ->expects($this->exactly(2))
            ->method('createCollection')
            ->with(self::stringStartsWith('fs.'), $this->writeOptions($expectedWriteOptions));

        $this->schemaManager->createDocumentCollection(File::class, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateDocumentCollectionWithValidator(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $expectedValidatorJson = <<<'EOT'
{
    "$jsonSchema": {
        "required": ["name"],
        "properties": {
            "name": {
                "bsonType": "string",
                "description": "must be a string and is required"
            }
        }
    },
    "$or": [
        { "phone": { "$type": "string" } },
        { "email": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } },
        { "status": { "$in": [ "Unknown", "Incomplete" ] } }
    ]
}
EOT;
        $expectedValidator     = Document::fromJSON($expectedValidatorJson)->toPHP();
        $options               = [
            'capped' => false,
            'size' => null,
            'max' => null,
            'validator' => $expectedValidator,
            'validationAction' => ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
            'validationLevel' => ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
        ];
        $cm                    = $this->dm->getClassMetadata(SchemaValidated::class);
        $database              = $this->documentDatabases[$this->getDatabaseName($cm)];
        $database
            ->expects($this->once())
            ->method('createCollection')
            ->with('SchemaValidated', $options + $expectedWriteOptions);

        $this->schemaManager->createDocumentCollection($cm->name, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateView(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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
                $this->writeOptions($options + $expectedWriteOptions),
            );

        $rootCollection = $this->documentCollections['CmsUser'];
        $rootCollection
            ->method('getCollectionName')
            ->willReturn('CmsUser');

        $this->schemaManager->createDocumentCollection(UserName::class, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateTimeSeriesCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesDocument::class);

        $options = [
            'timeseries' => [
                'timeField' => 'time',
                'metaField' => 'metadata',
                'granularity' => Granularity::Seconds,
            ],
            'expireAfterSeconds' => 86400,
        ];

        $database = $this->documentDatabases[$this->getDatabaseName($metadata)];
        $database
            ->expects($this->once())
            ->method('createCollection')
            ->with(
                'TimeSeriesDocument',
                $this->writeOptions($options + $expectedWriteOptions),
            );

        $this->schemaManager->createDocumentCollection(TimeSeriesDocument::class, $maxTimeMs, $writeConcern);
    }

    /** @psalm-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testCreateCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $class = $this->dm->getClassMetadata(Tournament::class);
        self::assertSame(ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION, $class->inheritanceType);

        $createdCollections = [];
        foreach ($this->documentDatabases as $database) {
            $database
                ->expects($this->atLeastOnce())
                ->method('createCollection')
                ->with($this->anything(), $this->writeOptions($expectedWriteOptions))
                ->willReturnCallback(static function (string $collectionName) use (&$createdCollections): void {
                    $createdCollections[] = $collectionName;
                });

            $database
                ->expects($this->atLeastOnce())
                ->method('command')
                ->with($this->anything(), $this->writeOptions($expectedWriteOptions));
        }

        $this->schemaManager->createCollections($maxTimeMs, $writeConcern);
        self::assertSame(1, array_count_values($createdCollections)['Tournament']);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropCollections(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        foreach ($this->documentCollections as $collection) {
            $collection->expects($this->atLeastOnce())
                ->method('drop')
                ->with($this->writeOptions($expectedWriteOptions));
        }

        $this->schemaManager->dropCollections($maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropDocumentCollection(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropDocumentCollectionForGridFSFile(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        foreach ($this->documentCollections as $collection) {
            $collection->expects($this->never())->method('drop');
        }

        $fileBucketName = $this->dm->getClassMetadata(File::class)->getBucketName();
        foreach ($this->documentBuckets as $bucketName => $bucket) {
            $filesCollection = $bucket->getFilesCollection();
            assert($filesCollection instanceof Collection && $filesCollection instanceof MockObject);

            $chunksCollection = $bucket->getChunksCollection();
            assert($chunksCollection instanceof Collection && $chunksCollection instanceof MockObject);

            if ($bucketName === $fileBucketName) {
                $filesCollection
                    ->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
                $chunksCollection
                    ->expects($this->once())
                    ->method('drop')
                    ->with($this->writeOptions($expectedWriteOptions));
            } else {
                $filesCollection->expects($this->never())->method('drop');
                $chunksCollection->expects($this->never())->method('drop');
            }
        }

        $this->schemaManager->dropDocumentCollection(File::class, $maxTimeMs, $writeConcern);
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropView(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropDocumentDatabase(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    #[DataProvider('getWriteOptions')]
    public function testDropDatabases(array $expectedWriteOptions, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
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
     * @param array<string, mixed> $mongoIndex
     * @phpstan-param IndexMapping $documentIndex
     */
    #[DataProvider('dataIsMongoIndexEquivalentToDocumentIndex')]
    public function testIsMongoIndexEquivalentToDocumentIndex(bool $expected, array $mongoIndex, array $documentIndex): void
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

        self::assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex(new IndexInfo($mongoIndex), $documentIndex));
    }

    public static function dataIsMongoIndexEquivalentToDocumentIndex(): array
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
            // 2dsphereIndexVersion index options
            '2dsphereIndexVersionOptionsDifferent' => [
                'expected' => true,
                'mongoIndex' => ['2dsphereIndexVersion' => 3],
                'documentIndex' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $mongoIndex
     * @phpstan-param IndexMapping $documentIndex
     */
    #[DataProvider('dataIsMongoTextIndexEquivalentToDocumentIndex')]
    public function testIsMongoIndexEquivalentToDocumentIndexWithTextIndexes(bool $expected, array $mongoIndex, array $documentIndex): void
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

        self::assertSame($expected, $this->schemaManager->isMongoIndexEquivalentToDocumentIndex(new IndexInfo($mongoIndex), $documentIndex));
    }

    public static function dataIsMongoTextIndexEquivalentToDocumentIndex(): array
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

    /** @param ClassMetadata<object> $cm */
    private function getDatabaseName(ClassMetadata $cm): string
    {
        return ($cm->getDatabase() ?: $this->dm->getConfiguration()->getDefaultDB()) ?: 'doctrine';
    }

    /** @return Bucket&MockObject */
    private function getMockBucket()
    {
        $mock = $this->createMock(Bucket::class);
        $mock->method('getFilesCollection')->willReturn($this->getMockCollection());
        $mock->method('getChunksCollection')->willReturn($this->getMockCollection());

        return $mock;
    }

    /** @return Collection&MockObject */
    private function getMockCollection(?string $name = null)
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('getCollectionName')->willReturnCallback(static fn () => $name);

        return $collection;
    }

    /** @return Database&MockObject */
    private function getMockDatabase()
    {
        $db = $this->createMock(Database::class);
        $db->method('selectCollection')->willReturnCallback(fn (string $collection) => $this->documentCollections[$collection]);
        $db->method('selectGridFSBucket')->willReturnCallback(fn (array $options) => $this->documentBuckets[$options['bucketName']]);
        $db->method('listCollections')->willReturnCallback(function () {
            $collections = [];
            foreach ($this->documentCollections as $collectionName => $collection) {
                $collections[] = new CollectionInfo(['name' => $collectionName]);
            }

            return $collections;
        });

        return $db;
    }

    /** @phpstan-param IndexOptions $expectedWriteOptions */
    private function writeOptions(array $expectedWriteOptions): Constraint
    {
        return new Callback(static function (array $value) use ($expectedWriteOptions) {
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

    private function createSearchIndexCommandException(): CommandException
    {
        return new CommandException('PlanExecutor error during aggregation :: caused by :: Search index commands are only supported with Atlas.', 115);
    }

    private function createSearchIndexCommandExceptionForOlderServers(): CommandException
    {
        return new CommandException('Unrecognized pipeline stage name: \'$listSearchIndexes\'', 40234);
    }
}
