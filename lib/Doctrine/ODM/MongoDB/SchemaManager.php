<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use InvalidArgumentException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Exception\ServerException;
use MongoDB\Driver\WriteConcern;
use MongoDB\Model\IndexInfo;

use function array_diff_key;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function assert;
use function in_array;
use function is_string;
use function iterator_count;
use function iterator_to_array;
use function ksort;
use function sprintf;

final class SchemaManager
{
    private const GRIDFS_FILE_COLLECTION_INDEX = ['files_id' => 1, 'n' => 1];

    private const GRIDFS_CHUNKS_COLLECTION_INDEX = ['filename' => 1, 'uploadDate' => 1];

    private const CODE_SHARDING_ALREADY_INITIALIZED = 23;

    private const ALLOWED_MISSING_INDEX_OPTIONS = [
        'background',
        'partialFilterExpression',
        'sparse',
        'unique',
        'weights',
        'default_language',
        'language_override',
        'textIndexVersion',
        'name',
    ];

    /** @var DocumentManager */
    protected $dm;

    /** @var ClassMetadataFactory */
    protected $metadataFactory;

    public function __construct(DocumentManager $dm, ClassMetadataFactory $cmf)
    {
        $this->dm              = $dm;
        $this->metadataFactory = $cmf;
    }

    /**
     * Ensure indexes are created for all documents that can be loaded with the
     * metadata factory.
     */
    public function ensureIndexes(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, bool $background = false): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
                continue;
            }

            $this->ensureDocumentIndexes($class->name, $maxTimeMs, $writeConcern, $background);
        }
    }

    /**
     * Ensure indexes exist for all mapped document classes.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     */
    public function updateIndexes(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
                continue;
            }

            $this->updateDocumentIndexes($class->name, $maxTimeMs, $writeConcern);
        }
    }

    /**
     * Ensure indexes exist for the mapped document class.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     *
     * @throws InvalidArgumentException
     */
    public function updateDocumentIndexes(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);

        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
            throw new InvalidArgumentException('Cannot update document indexes for mapped super classes, embedded documents or aggregation result documents.');
        }

        $documentIndexes = $this->getDocumentIndexes($documentName);
        $collection      = $this->dm->getDocumentCollection($documentName);
        $mongoIndexes    = iterator_to_array($collection->listIndexes());

        /* Determine which Mongo indexes should be deleted. Exclude the ID index
         * and those that are equivalent to any in the class metadata.
         */
        $self         = $this;
        $mongoIndexes = array_filter($mongoIndexes, static function (IndexInfo $mongoIndex) use ($documentIndexes, $self) {
            if ($mongoIndex['name'] === '_id_') {
                return false;
            }

            foreach ($documentIndexes as $documentIndex) {
                if ($self->isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex)) {
                    return false;
                }
            }

            return true;
        });

        // Delete indexes that do not exist in class metadata
        foreach ($mongoIndexes as $mongoIndex) {
            if (! isset($mongoIndex['name'])) {
                continue;
            }

            $collection->dropIndex($mongoIndex['name'], $this->getWriteOptions($maxTimeMs, $writeConcern));
        }

        $this->ensureDocumentIndexes($documentName, $maxTimeMs, $writeConcern);
    }

    public function getDocumentIndexes(string $documentName): array
    {
        $visited = [];

        return $this->doGetDocumentIndexes($documentName, $visited);
    }

    private function doGetDocumentIndexes(string $documentName, array &$visited): array
    {
        if (isset($visited[$documentName])) {
            return [];
        }

        $visited[$documentName] = true;

        $class                   = $this->dm->getClassMetadata($documentName);
        $indexes                 = $this->prepareIndexes($class);
        $embeddedDocumentIndexes = [];

        // Add indexes from embedded & referenced documents
        foreach ($class->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['embedded'])) {
                if (isset($fieldMapping['targetDocument'])) {
                    $possibleEmbeds = [$fieldMapping['targetDocument']];
                } elseif (isset($fieldMapping['discriminatorMap'])) {
                    $possibleEmbeds = array_unique($fieldMapping['discriminatorMap']);
                } else {
                    continue;
                }

                foreach ($possibleEmbeds as $embed) {
                    if (isset($embeddedDocumentIndexes[$embed])) {
                        $embeddedIndexes = $embeddedDocumentIndexes[$embed];
                    } else {
                        $embeddedIndexes                 = $this->doGetDocumentIndexes($embed, $visited);
                        $embeddedDocumentIndexes[$embed] = $embeddedIndexes;
                    }

                    foreach ($embeddedIndexes as $embeddedIndex) {
                        foreach ($embeddedIndex['keys'] as $key => $value) {
                            $embeddedIndex['keys'][$fieldMapping['name'] . '.' . $key] = $value;
                            unset($embeddedIndex['keys'][$key]);
                        }

                        if (isset($embeddedIndex['options']['name'])) {
                            $embeddedIndex['options']['name'] = sprintf('%s_%s', $fieldMapping['name'], $embeddedIndex['options']['name']);
                        }

                        $indexes[] = $embeddedIndex;
                    }
                }
            } elseif (isset($fieldMapping['reference']) && isset($fieldMapping['targetDocument'])) {
                foreach ($indexes as $idx => $index) {
                    $newKeys = [];
                    foreach ($index['keys'] as $key => $v) {
                        if ($key === $fieldMapping['name']) {
                            $key = ClassMetadata::getReferenceFieldName($fieldMapping['storeAs'], $key);
                        }

                        $newKeys[$key] = $v;
                    }

                    $indexes[$idx]['keys'] = $newKeys;
                }
            }
        }

        return $indexes;
    }

    private function prepareIndexes(ClassMetadata $class): array
    {
        $persister  = $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
        $indexes    = $class->getIndexes();
        $newIndexes = [];

        foreach ($indexes as $index) {
            $newIndex = [
                'keys' => [],
                'options' => $index['options'],
            ];

            foreach ($index['keys'] as $key => $value) {
                $key = $persister->prepareFieldName($key);
                if ($class->hasField($key)) {
                    $mapping                            = $class->getFieldMapping($key);
                    $newIndex['keys'][$mapping['name']] = $value;
                } else {
                    $newIndex['keys'][$key] = $value;
                }
            }

            $newIndexes[] = $newIndex;
        }

        return $newIndexes;
    }

    /**
     * Ensure the given document's indexes are created.
     *
     * @throws InvalidArgumentException
     */
    public function ensureDocumentIndexes(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, bool $background = false): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
            throw new InvalidArgumentException('Cannot create document indexes for mapped super classes, embedded documents or query result documents.');
        }

        if ($class->isFile) {
            $this->ensureGridFSIndexes($class, $maxTimeMs, $writeConcern, $background);
        }

        $indexes = $this->getDocumentIndexes($documentName);
        if (! $indexes) {
            return;
        }

        $collection = $this->dm->getDocumentCollection($class->name);
        foreach ($indexes as $index) {
            $collection->createIndex($index['keys'], $this->getWriteOptions($maxTimeMs, $writeConcern, $index['options'] + ['background' => $background]));
        }
    }

    /**
     * Delete indexes for all documents that can be loaded with the
     * metadata factory.
     */
    public function deleteIndexes(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
                continue;
            }

            $this->deleteDocumentIndexes($class->name, $maxTimeMs, $writeConcern);
        }
    }

    /**
     * Delete the given document's indexes.
     *
     * @throws InvalidArgumentException
     */
    public function deleteDocumentIndexes(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
            throw new InvalidArgumentException('Cannot delete document indexes for mapped super classes, embedded documents or query result documents.');
        }

        $this->dm->getDocumentCollection($documentName)->dropIndexes($this->getWriteOptions($maxTimeMs, $writeConcern));
    }

    /**
     * Create all the mapped document collections in the metadata factory.
     */
    public function createCollections(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->createDocumentCollection($class->name, $maxTimeMs, $writeConcern);
        }
    }

    /**
     * Create the document collection for a mapped class.
     *
     * @throws InvalidArgumentException
     */
    public function createDocumentCollection(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);

        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new InvalidArgumentException('Cannot create document collection for mapped super classes, embedded documents or query result documents.');
        }

        if ($class->isView()) {
            $options = $this->getWriteOptions($maxTimeMs, $writeConcern);

            $rootClass = $class->getRootClass();
            assert(is_string($rootClass));

            $builder    = $this->dm->createAggregationBuilder($rootClass);
            $repository = $this->dm->getRepository($class->name);
            assert($repository instanceof ViewRepository);
            $repository->createViewAggregation($builder);

            $collectionName = $this->dm->getDocumentCollection($rootClass)->getCollectionName();
            $this->dm->getDocumentDatabase($documentName)
                ->command([
                    'create' => $class->collection,
                    'viewOn' => $collectionName,
                    'pipeline' => $builder->getPipeline(),
                ], $options);

            return;
        }

        if ($class->isFile) {
            $options = $this->getWriteOptions($maxTimeMs, $writeConcern);

            $this->dm->getDocumentDatabase($documentName)->createCollection($class->getBucketName() . '.files', $options);
            $this->dm->getDocumentDatabase($documentName)->createCollection($class->getBucketName() . '.chunks', $options);

            return;
        }

        $options = [
            'capped' => $class->getCollectionCapped(),
            'size' => $class->getCollectionSize(),
            'max' => $class->getCollectionMax(),
        ];

        $this->dm->getDocumentDatabase($documentName)->createCollection(
            $class->getCollection(),
            $this->getWriteOptions($maxTimeMs, $writeConcern, $options)
        );
    }

    /**
     * Drop all the mapped document collections in the metadata factory.
     */
    public function dropCollections(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->dropDocumentCollection($class->name, $maxTimeMs, $writeConcern);
        }
    }

    /**
     * Drop the document collection for a mapped class.
     *
     * @throws InvalidArgumentException
     */
    public function dropDocumentCollection(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new InvalidArgumentException('Cannot delete document indexes for mapped super classes, embedded documents or query result documents.');
        }

        $options = $this->getWriteOptions($maxTimeMs, $writeConcern);

        $this->dm->getDocumentCollection($documentName)->drop($options);

        if (! $class->isFile) {
            return;
        }

        $this->dm->getDocumentBucket($documentName)->getChunksCollection()->drop($options);
    }

    /**
     * Drop all the mapped document databases in the metadata factory.
     */
    public function dropDatabases(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->dropDocumentDatabase($class->name, $maxTimeMs, $writeConcern);
        }
    }

    /**
     * Drop the document database for a mapped class.
     *
     * @throws InvalidArgumentException
     */
    public function dropDocumentDatabase(string $documentName, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new InvalidArgumentException('Cannot drop document database for mapped super classes, embedded documents or query result documents.');
        }

        $this->dm->getDocumentDatabase($documentName)->drop($this->getWriteOptions($maxTimeMs, $writeConcern));
    }

    public function isMongoIndexEquivalentToDocumentIndex(IndexInfo $mongoIndex, array $documentIndex): bool
    {
        return $this->isEquivalentIndexKeys($mongoIndex, $documentIndex) && $this->isEquivalentIndexOptions($mongoIndex, $documentIndex);
    }

    /**
     * Determine if the keys for a MongoDB index can be considered equivalent to
     * those for an index in class metadata.
     */
    private function isEquivalentIndexKeys(IndexInfo $mongoIndex, array $documentIndex): bool
    {
        $mongoIndexKeys    = $mongoIndex['key'];
        $documentIndexKeys = $documentIndex['keys'];

        /* If we are dealing with text indexes, we need to unset internal fields
         * from the MongoDB index and filter out text fields from the document
         * index. This will leave only non-text fields, which we can compare as
         * normal. Any text fields in the document index will be compared later
         * with isEquivalentTextIndexWeights(). */
        if (isset($mongoIndexKeys['_fts']) && $mongoIndexKeys['_fts'] === 'text') {
            unset($mongoIndexKeys['_fts'], $mongoIndexKeys['_ftsx']);

            $documentIndexKeys = array_filter($documentIndexKeys, static function ($type) {
                return $type !== 'text';
            });
        }

        /* Avoid a strict equality check of the arrays here. The numeric type returned
         * by MongoDB may differ from the document index without implying that the
         * indexes themselves are inequivalent. The strict check of the keys asserts
         * that the order of the keys remained the same. */
        // phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        return $this->hasTextIndexesAtSamePosition($mongoIndex, $documentIndex) &&
            array_keys($mongoIndexKeys) === array_keys($documentIndexKeys) &&
            $mongoIndexKeys == $documentIndexKeys;
    }

    private function hasTextIndexesAtSamePosition(IndexInfo $mongoIndex, array $documentIndex): bool
    {
        $mongoIndexKeys    = $mongoIndex['key'];
        $documentIndexKeys = $documentIndex['keys'];

        if (! isset($mongoIndexKeys['_fts']) && ! in_array('text', $documentIndexKeys, true)) {
            return true;
        }

        /*
         * We unset _ftsx to avoid the uncertainty whether _fts really comes first and
         * therefore denotes the position of the text index.
         */
        unset($mongoIndexKeys['_ftsx']);

        $mongoIndexTextPosition    = array_search('_fts', array_keys($mongoIndexKeys), true);
        $documentIndexTextPosition = array_search('text', array_values($documentIndexKeys), true);

        return $mongoIndexTextPosition === $documentIndexTextPosition;
    }

    /**
     * Determine if an index returned by MongoCollection::getIndexInfo() can be
     * considered equivalent to an index in class metadata based on options.
     *
     * Indexes are considered different if:
     *
     *   (a) Key/direction pairs differ or are not in the same order
     *   (b) Sparse or unique options differ
     *   (c) Geospatial options differ (bits, max, min)
     *   (d) The partialFilterExpression differs
     *
     * The background option is only relevant to index creation and is not
     * considered.
     */
    private function isEquivalentIndexOptions(IndexInfo $mongoIndex, array $documentIndex): bool
    {
        $mongoIndexOptions = $mongoIndex->__debugInfo();
        unset($mongoIndexOptions['v'], $mongoIndexOptions['ns'], $mongoIndexOptions['key']);

        $documentIndexOptions = $documentIndex['options'];

        if ($this->indexOptionsAreMissing($mongoIndexOptions, $documentIndexOptions)) {
            return false;
        }

        if (empty($mongoIndexOptions['sparse']) xor empty($documentIndexOptions['sparse'])) {
            return false;
        }

        if (empty($mongoIndexOptions['unique']) xor empty($documentIndexOptions['unique'])) {
            return false;
        }

        foreach (['bits', 'max', 'min'] as $option) {
            if (
                isset($mongoIndexOptions[$option], $documentIndexOptions[$option]) &&
                $mongoIndexOptions[$option] !== $documentIndexOptions[$option]
            ) {
                return false;
            }
        }

        if (empty($mongoIndexOptions['partialFilterExpression']) xor empty($documentIndexOptions['partialFilterExpression'])) {
            return false;
        }

        if (
            isset($mongoIndexOptions['partialFilterExpression'], $documentIndexOptions['partialFilterExpression']) &&
            $mongoIndexOptions['partialFilterExpression'] !== $documentIndexOptions['partialFilterExpression']
        ) {
            return false;
        }

        if (isset($mongoIndexOptions['weights']) && ! $this->isEquivalentTextIndexWeights($mongoIndex, $documentIndex)) {
            return false;
        }

        foreach (['default_language', 'language_override', 'textIndexVersion'] as $option) {
            /* Text indexes will always report defaults for these options, so
             * only compare if we have explicit values in the document index. */
            if (
                isset($mongoIndexOptions[$option], $documentIndexOptions[$option]) &&
                $mongoIndexOptions[$option] !== $documentIndexOptions[$option]
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if any index options are missing.
     *
     * Options added to the ALLOWED_MISSING_INDEX_OPTIONS constant are ignored
     * and are expected to be checked later
     */
    private function indexOptionsAreMissing(array $mongoIndexOptions, array $documentIndexOptions): bool
    {
        foreach (self::ALLOWED_MISSING_INDEX_OPTIONS as $option) {
            unset($mongoIndexOptions[$option], $documentIndexOptions[$option]);
        }

        return array_diff_key($mongoIndexOptions, $documentIndexOptions) !== [] || array_diff_key($documentIndexOptions, $mongoIndexOptions) !== [];
    }

    /**
     * Determine if the text index weights for a MongoDB index can be considered
     * equivalent to those for an index in class metadata.
     */
    private function isEquivalentTextIndexWeights(IndexInfo $mongoIndex, array $documentIndex): bool
    {
        $mongoIndexWeights    = $mongoIndex['weights'];
        $documentIndexWeights = $documentIndex['options']['weights'] ?? [];

        // If not specified, assign a default weight for text fields
        foreach ($documentIndex['keys'] as $key => $type) {
            if ($type !== 'text' || isset($documentIndexWeights[$key])) {
                continue;
            }

            $documentIndexWeights[$key] = 1;
        }

        /* MongoDB returns the weights sorted by field name, but we'll sort both
         * arrays in case that is internal behavior not to be relied upon. */
        ksort($mongoIndexWeights);
        ksort($documentIndexWeights);

        /* Avoid a strict equality check here. The numeric type returned by
         * MongoDB may differ from the document index without implying that the
         * indexes themselves are inequivalent. */
        // phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        return $mongoIndexWeights == $documentIndexWeights;
    }

    /**
     * Ensure collections are sharded for all documents that can be loaded with the
     * metadata factory.
     *
     * @throws MongoDBException
     */
    public function ensureSharding(?WriteConcern $writeConcern = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || ! $class->isSharded()) {
                continue;
            }

            $this->ensureDocumentSharding($class->name, $writeConcern);
        }
    }

    /**
     * Ensure sharding for collection by document name.
     *
     * @throws MongoDBException
     */
    public function ensureDocumentSharding(string $documentName, ?WriteConcern $writeConcern = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if (! $class->isSharded()) {
            return;
        }

        if ($this->collectionIsSharded($documentName)) {
            return;
        }

        $this->enableShardingForDbByDocumentName($documentName);

        try {
            $this->runShardCollectionCommand($documentName, $writeConcern);
        } catch (RuntimeException $e) {
            throw MongoDBException::failedToEnsureDocumentSharding($documentName, $e->getMessage());
        }
    }

    /**
     * Enable sharding for database which contains documents with given name.
     *
     * @throws MongoDBException
     */
    public function enableShardingForDbByDocumentName(string $documentName): void
    {
        $dbName  = $this->dm->getDocumentDatabase($documentName)->getDatabaseName();
        $adminDb = $this->dm->getClient()->selectDatabase('admin');

        try {
            $adminDb->command(['enableSharding' => $dbName]);
        } catch (ServerException $e) {
            // Don't throw an exception if sharding is already enabled; there's just no other way to check this
            if ($e->getCode() !== self::CODE_SHARDING_ALREADY_INITIALIZED) {
                throw MongoDBException::failedToEnableSharding($dbName, $e->getMessage());
            }
        } catch (RuntimeException $e) {
            throw MongoDBException::failedToEnableSharding($dbName, $e->getMessage());
        }
    }

    private function runShardCollectionCommand(string $documentName, ?WriteConcern $writeConcern = null): array
    {
        $class    = $this->dm->getClassMetadata($documentName);
        $dbName   = $this->dm->getDocumentDatabase($documentName)->getDatabaseName();
        $shardKey = $class->getShardKey();
        $adminDb  = $this->dm->getClient()->selectDatabase('admin');

        $shardKeyPart = [];
        foreach ($shardKey['keys'] as $key => $order) {
            if ($class->hasField($key)) {
                $mapping   = $class->getFieldMapping($key);
                $fieldName = $mapping['name'];

                if ($class->isSingleValuedReference($key)) {
                    $fieldName = ClassMetadata::getReferenceFieldName($mapping['storeAs'], $fieldName);
                }
            } else {
                $fieldName = $key;
            }

            $shardKeyPart[$fieldName] = $order;
        }

        return $adminDb->command(
            array_merge(
                [
                    'shardCollection' => $dbName . '.' . $class->getCollection(),
                    'key'             => $shardKeyPart,
                ],
                $this->getWriteOptions(null, $writeConcern)
            )
        )->toArray()[0];
    }

    private function ensureGridFSIndexes(ClassMetadata $class, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, bool $background = false): void
    {
        $this->ensureChunksIndex($class, $maxTimeMs, $writeConcern, $background);
        $this->ensureFilesIndex($class, $maxTimeMs, $writeConcern, $background);
    }

    private function ensureChunksIndex(ClassMetadata $class, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, bool $background = false): void
    {
        $chunksCollection = $this->dm->getDocumentBucket($class->getName())->getChunksCollection();
        foreach ($chunksCollection->listIndexes() as $index) {
            if ($index->isUnique() && $index->getKey() === self::GRIDFS_FILE_COLLECTION_INDEX) {
                return;
            }
        }

        $chunksCollection->createIndex(
            self::GRIDFS_FILE_COLLECTION_INDEX,
            $this->getWriteOptions($maxTimeMs, $writeConcern, ['unique' => true, 'background' => $background])
        );
    }

    private function ensureFilesIndex(ClassMetadata $class, ?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, bool $background = false): void
    {
        $filesCollection = $this->dm->getDocumentCollection($class->getName());
        foreach ($filesCollection->listIndexes() as $index) {
            if ($index->getKey() === self::GRIDFS_CHUNKS_COLLECTION_INDEX) {
                return;
            }
        }

        $filesCollection->createIndex(self::GRIDFS_CHUNKS_COLLECTION_INDEX, $this->getWriteOptions($maxTimeMs, $writeConcern, ['background' => $background]));
    }

    private function collectionIsSharded(string $documentName): bool
    {
        $class = $this->dm->getClassMetadata($documentName);

        $database    = $this->dm->getDocumentDatabase($documentName);
        $collections = $database->listCollections(['filter' => ['name' => $class->getCollection()]]);
        if (! iterator_count($collections)) {
            return false;
        }

        $stats = $database->command(['collstats' => $class->getCollection()])->toArray()[0];

        return (bool) ($stats['sharded'] ?? false);
    }

    private function getWriteOptions(?int $maxTimeMs = null, ?WriteConcern $writeConcern = null, array $options = []): array
    {
        unset($options['maxTimeMs'], $options['writeConcern']);

        if ($maxTimeMs !== null) {
            $options['maxTimeMs'] = $maxTimeMs;
        }

        if ($writeConcern !== null) {
            $options['writeConcern'] = $writeConcern;
        }

        return $options;
    }
}
