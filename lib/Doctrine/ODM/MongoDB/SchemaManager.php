<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Model\IndexInfo;
use function array_filter;
use function array_unique;
use function iterator_to_array;
use function ksort;
use function strpos;

class SchemaManager
{
    private const GRIDFS_FILE_COLLECTION_INDEX = ['files_id' => 1, 'n' => 1];

    private const GRIDFS_CHUNKS_COLLECTION_INDEX = ['filename' => 1, 'uploadDate' => 1];

    /** @var DocumentManager */
    protected $dm;

    /** @var ClassMetadataFactory */
    protected $metadataFactory;

    public function __construct(DocumentManager $dm, ClassMetadataFactory $cmf)
    {
        $this->dm = $dm;
        $this->metadataFactory = $cmf;
    }

    /**
     * Ensure indexes are created for all documents that can be loaded with the
     * metadata factory.
     */
    public function ensureIndexes(?int $timeout = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->ensureDocumentIndexes($class->name, $timeout);
        }
    }

    /**
     * Ensure indexes exist for all mapped document classes.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     */
    public function updateIndexes(?int $timeout = null): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->updateDocumentIndexes($class->name, $timeout);
        }
    }

    /**
     * Ensure indexes exist for the mapped document class.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     *
     * @throws \InvalidArgumentException
     */
    public function updateDocumentIndexes(string $documentName, ?int $timeout = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);

        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot update document indexes for mapped super classes, embedded documents or aggregation result documents.');
        }

        $documentIndexes = $this->getDocumentIndexes($documentName);
        $collection = $this->dm->getDocumentCollection($documentName);
        $mongoIndexes = iterator_to_array($collection->listIndexes());

        /* Determine which Mongo indexes should be deleted. Exclude the ID index
         * and those that are equivalent to any in the class metadata.
         */
        $self = $this;
        $mongoIndexes = array_filter($mongoIndexes, function (IndexInfo $mongoIndex) use ($documentIndexes, $self) {
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

            $collection->dropIndex($mongoIndex['name']);
        }

        $this->ensureDocumentIndexes($documentName, $timeout);
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

        $class = $this->dm->getClassMetadata($documentName);
        $indexes = $this->prepareIndexes($class);
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
                        $embeddedIndexes = $this->doGetDocumentIndexes($embed, $visited);
                        $embeddedDocumentIndexes[$embed] = $embeddedIndexes;
                    }

                    foreach ($embeddedIndexes as $embeddedIndex) {
                        foreach ($embeddedIndex['keys'] as $key => $value) {
                            $embeddedIndex['keys'][$fieldMapping['name'] . '.' . $key] = $value;
                            unset($embeddedIndex['keys'][$key]);
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
        $persister = $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
        $indexes = $class->getIndexes();
        $newIndexes = [];

        foreach ($indexes as $index) {
            $newIndex = [
                'keys' => [],
                'options' => $index['options'],
            ];

            foreach ($index['keys'] as $key => $value) {
                $key = $persister->prepareFieldName($key);
                if ($class->hasField($key)) {
                    $mapping = $class->getFieldMapping($key);
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
     * @throws \InvalidArgumentException
     */
    public function ensureDocumentIndexes(string $documentName, ?int $timeoutMs = null): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot create document indexes for mapped super classes, embedded documents or query result documents.');
        }

        if ($class->isFile) {
            $this->ensureGridFSIndexes($class);
        }

        $indexes = $this->getDocumentIndexes($documentName);
        if (! $indexes) {
            return;
        }

        $collection = $this->dm->getDocumentCollection($class->name);
        foreach ($indexes as $index) {
            $keys = $index['keys'];
            $options = $index['options'];

            if (! isset($options['timeout']) && isset($timeoutMs)) {
                $options['timeout'] = $timeoutMs;
            }

            $collection->createIndex($keys, $options);
        }
    }

    /**
     * Delete indexes for all documents that can be loaded with the
     * metadata factory.
     */
    public function deleteIndexes(): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->deleteDocumentIndexes($class->name);
        }
    }

    /**
     * Delete the given document's indexes.
     *
     * @throws \InvalidArgumentException
     */
    public function deleteDocumentIndexes(string $documentName): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot delete document indexes for mapped super classes, embedded documents or query result documents.');
        }

        $this->dm->getDocumentCollection($documentName)->dropIndexes();
    }

    /**
     * Create all the mapped document collections in the metadata factory.
     */
    public function createCollections(): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }
            $this->createDocumentCollection($class->name);
        }
    }

    /**
     * Create the document collection for a mapped class.
     *
     * @throws \InvalidArgumentException
     */
    public function createDocumentCollection(string $documentName): void
    {
        $class = $this->dm->getClassMetadata($documentName);

        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot create document collection for mapped super classes, embedded documents or query result documents.');
        }

        if ($class->isFile) {
            $this->dm->getDocumentDatabase($documentName)->createCollection($class->getBucketName() . '.files');
            $this->dm->getDocumentDatabase($documentName)->createCollection($class->getBucketName() . '.chunks');

            return;
        }

        $this->dm->getDocumentDatabase($documentName)->createCollection(
            $class->getCollection(),
            [
                'capped' => $class->getCollectionCapped(),
                'size' => $class->getCollectionSize(),
                'max' => $class->getCollectionMax(),
            ]
        );
    }

    /**
     * Drop all the mapped document collections in the metadata factory.
     */
    public function dropCollections(): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->dropDocumentCollection($class->name);
        }
    }

    /**
     * Drop the document collection for a mapped class.
     *
     * @throws \InvalidArgumentException
     */
    public function dropDocumentCollection(string $documentName): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot delete document indexes for mapped super classes, embedded documents or query result documents.');
        }

        $this->dm->getDocumentCollection($documentName)->drop();

        if (! $class->isFile) {
            return;
        }

        $this->dm->getDocumentBucket($documentName)->getChunksCollection()->drop();
    }

    /**
     * Drop all the mapped document databases in the metadata factory.
     */
    public function dropDatabases(): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
                continue;
            }

            $this->dropDocumentDatabase($class->name);
        }
    }

    /**
     * Drop the document database for a mapped class.
     *
     * @throws \InvalidArgumentException
     */
    public function dropDocumentDatabase(string $documentName): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument) {
            throw new \InvalidArgumentException('Cannot drop document database for mapped super classes, embedded documents or query result documents.');
        }

        $this->dm->getDocumentDatabase($documentName)->drop();
    }

    /**
     * Determine if an index returned by MongoCollection::getIndexInfo() can be
     * considered equivalent to an index in class metadata.
     *
     * Indexes are considered different if:
     *
     *   (a) Key/direction pairs differ or are not in the same order
     *   (b) Sparse or unique options differ
     *   (c) Mongo index is unique without dropDups and mapped index is unique
     *       with dropDups
     *   (d) Geospatial options differ (bits, max, min)
     *   (e) The partialFilterExpression differs
     *
     * Regarding (c), the inverse case is not a reason to delete and
     * recreate the index, since dropDups only affects creation of
     * the unique index. Additionally, the background option is only
     * relevant to index creation and is not considered.
     *
     * @param array|IndexInfo $mongoIndex Mongo index data.
     */
    public function isMongoIndexEquivalentToDocumentIndex($mongoIndex, array $documentIndex): bool
    {
        $documentIndexOptions = $documentIndex['options'];

        if (! $this->isEquivalentIndexKeys($mongoIndex, $documentIndex)) {
            return false;
        }

        if (empty($mongoIndex['sparse']) xor empty($documentIndexOptions['sparse'])) {
            return false;
        }

        if (empty($mongoIndex['unique']) xor empty($documentIndexOptions['unique'])) {
            return false;
        }

        if (! empty($mongoIndex['unique']) && empty($mongoIndex['dropDups']) &&
            ! empty($documentIndexOptions['unique']) && ! empty($documentIndexOptions['dropDups'])) {
            return false;
        }

        foreach (['bits', 'max', 'min'] as $option) {
            if (isset($mongoIndex[$option]) xor isset($documentIndexOptions[$option])) {
                return false;
            }

            if (isset($mongoIndex[$option], $documentIndexOptions[$option]) &&
                $mongoIndex[$option] !== $documentIndexOptions[$option]) {
                return false;
            }
        }

        if (empty($mongoIndex['partialFilterExpression']) xor empty($documentIndexOptions['partialFilterExpression'])) {
            return false;
        }

        if (isset($mongoIndex['partialFilterExpression'], $documentIndexOptions['partialFilterExpression']) &&
            $mongoIndex['partialFilterExpression'] !== $documentIndexOptions['partialFilterExpression']) {
            return false;
        }

        if (isset($mongoIndex['weights']) && ! $this->isEquivalentTextIndexWeights($mongoIndex, $documentIndex)) {
            return false;
        }

        foreach (['default_language', 'language_override', 'textIndexVersion'] as $option) {
            /* Text indexes will always report defaults for these options, so
             * only compare if we have explicit values in the document index. */
            if (isset($mongoIndex[$option], $documentIndexOptions[$option]) &&
                $mongoIndex[$option] !== $documentIndexOptions[$option]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the keys for a MongoDB index can be considered equivalent to
     * those for an index in class metadata.
     *
     * @param array|IndexInfo $mongoIndex Mongo index data.
     */
    private function isEquivalentIndexKeys($mongoIndex, array $documentIndex): bool
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

            $documentIndexKeys = array_filter($documentIndexKeys, function ($type) {
                return $type !== 'text';
            });
        }

        /* Avoid a strict equality check here. The numeric type returned by
         * MongoDB may differ from the document index without implying that the
         * indexes themselves are inequivalent. */
        // phpcs:disable SlevomatCodingStandard.ControlStructures.DisallowEqualOperators.DisallowedEqualOperator
        return $mongoIndexKeys == $documentIndexKeys;
    }

    /**
     * Determine if the text index weights for a MongoDB index can be considered
     * equivalent to those for an index in class metadata.
     *
     * @param array|IndexInfo $mongoIndex Mongo index data.
     */
    private function isEquivalentTextIndexWeights($mongoIndex, array $documentIndex): bool
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
        // phpcs:disable SlevomatCodingStandard.ControlStructures.DisallowEqualOperators.DisallowedEqualOperator
        return $mongoIndexWeights == $documentIndexWeights;
    }

    /**
     * Ensure collections are sharded for all documents that can be loaded with the
     * metadata factory.
     *
     * @param array $indexOptions Options for `ensureIndex` command. It's performed on an existing collections
     *
     * @throws MongoDBException
     */
    public function ensureSharding(array $indexOptions = []): void
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || ! $class->isSharded()) {
                continue;
            }

            $this->ensureDocumentSharding($class->name, $indexOptions);
        }
    }

    /**
     * Ensure sharding for collection by document name.
     *
     * @param array $indexOptions Options for `ensureIndex` command. It's performed on an existing collections.
     *
     * @throws MongoDBException
     */
    public function ensureDocumentSharding(string $documentName, array $indexOptions = []): void
    {
        $class = $this->dm->getClassMetadata($documentName);
        if (! $class->isSharded()) {
            return;
        }

        $this->enableShardingForDbByDocumentName($documentName);

        $try = 0;
        do {
            try {
                $result = $this->runShardCollectionCommand($documentName);
                $done = true;

                // Need to check error message because MongoDB 3.0 does not return a code for this error
                if (! (bool) $result['ok'] && strpos($result['errmsg'], 'please create an index that starts') !== false) {
                    // The proposed key is not returned when using mongo-php-adapter with ext-mongodb.
                    // See https://github.com/mongodb/mongo-php-driver/issues/296 for details
                    $key = $result['proposedKey'] ?? $this->dm->getClassMetadata($documentName)->getShardKey()['keys'];

                    $this->dm->getDocumentCollection($documentName)->ensureIndex($key, $indexOptions);
                    $done = false;
                }
            } catch (RuntimeException $e) {
                if ($e->getCode() === 20 || $e->getCode() === 23 || $e->getMessage() === 'already sharded') {
                    return;
                }

                throw $e;
            }
        } while (! $done && $try < 2);

        // Starting with MongoDB 3.2, this command returns code 20 when a collection is already sharded.
        // For older MongoDB versions, check the error message
        if ((bool) $result['ok'] || (isset($result['code']) && $result['code'] === 20) || $result['errmsg'] === 'already sharded') {
            return;
        }

        throw MongoDBException::failedToEnsureDocumentSharding($documentName, $result['errmsg']);
    }

    /**
     * Enable sharding for database which contains documents with given name.
     *
     * @throws MongoDBException
     */
    public function enableShardingForDbByDocumentName(string $documentName): void
    {
        $dbName = $this->dm->getDocumentDatabase($documentName)->getDatabaseName();
        $adminDb = $this->dm->getClient()->selectDatabase('admin');

        try {
            $adminDb->command(['enableSharding' => $dbName]);
        } catch (RuntimeException $e) {
            if ($e->getCode() !== 23 || $e->getMessage() === 'already enabled') {
                throw MongoDBException::failedToEnableSharding($dbName, $e->getMessage());
            }
        }
    }

    private function runShardCollectionCommand(string $documentName): array
    {
        $class = $this->dm->getClassMetadata($documentName);
        $dbName = $this->dm->getDocumentDatabase($documentName)->getDatabaseName();
        $shardKey = $class->getShardKey();
        $adminDb = $this->dm->getClient()->selectDatabase('admin');

        $result = $adminDb->command(
            [
                'shardCollection' => $dbName . '.' . $class->getCollection(),
                'key'             => $shardKey['keys'],
            ]
        )->toArray()[0];

        return $result;
    }

    private function ensureGridFSIndexes(ClassMetadata $class): void
    {
        $this->ensureChunksIndex($class);
        $this->ensureFilesIndex($class);
    }

    private function ensureChunksIndex(ClassMetadata $class): void
    {
        $chunksCollection = $this->dm->getDocumentBucket($class->getName())->getChunksCollection();
        foreach ($chunksCollection->listIndexes() as $index) {
            if ($index->isUnique() && $index->getKey() === self::GRIDFS_FILE_COLLECTION_INDEX) {
                return;
            }
        }

        $chunksCollection->createIndex(self::GRIDFS_FILE_COLLECTION_INDEX, ['unique' => true]);
    }

    private function ensureFilesIndex(ClassMetadata $class): void
    {
        $filesCollection = $this->dm->getDocumentCollection($class->getName());
        foreach ($filesCollection->listIndexes() as $index) {
            if ($index->getKey() === self::GRIDFS_CHUNKS_COLLECTION_INDEX) {
                return;
            }
        }

        $filesCollection->createIndex(self::GRIDFS_CHUNKS_COLLECTION_INDEX);
    }
}
