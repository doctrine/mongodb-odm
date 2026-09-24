<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorException;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\PrimingIterator;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\Types\Versionable;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception as DriverException;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use MongoDB\GridFS\Bucket;
use SortDirection;
use stdClass;

use function array_combine;
use function array_fill;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function assert;
use function count;
use function gettype;
use function is_array;
use function is_scalar;
use function is_string;
use function spl_object_id;
use function sprintf;
use function strpos;
use function trigger_deprecation;

/**
 * The DocumentPersister is responsible for persisting documents.
 *
 * @internal
 *
 * @template T of object = object
 *
 * @phpstan-type CommitOptions array{
 *      fsync?: bool,
 *      safe?: int,
 *      session?: ?Session,
 *      w?: int,
 *      withTransaction?: bool,
 *      writeConcern?: WriteConcern
 * }
 * @phpstan-import-type Hints from UnitOfWork
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @phpstan-import-type SortMeta from Sort
 * @phpstan-import-type SortShape from Sort
 */
final class DocumentPersister
{
    private ?Collection $collection = null;

    private ?Bucket $bucket = null;

    /**
     * Array of queued inserts for the persister to insert.
     *
     * @var array<int, object>
     */
    private array $queuedInserts = [];

    /**
     * Array of queued inserts for the persister to insert.
     *
     * @var array<int, object>
     */
    private array $queuedUpserts = [];

    private CriteriaMerger $cm;

    private CollectionPersister $cp;

    /** @phpstan-var CriteriaPreparer<T> */
    private CriteriaPreparer $criteriaPreparer;

    /** @phpstan-param ClassMetadata<T> $class */
    public function __construct(
        private PersistenceBuilder $pb,
        private DocumentManager $dm,
        private UnitOfWork $uow,
        private HydratorFactory $hydratorFactory,
        private ClassMetadata $class,
        ?CriteriaMerger $cm = null,
    ) {
        $this->cm               = $cm ?: new CriteriaMerger();
        $this->cp               = $this->uow->getCollectionPersister();
        $this->criteriaPreparer = new CriteriaPreparer($this->dm, $this->pb, $this->class, $this->cm);

        if ($class->isEmbeddedDocument || $class->isQueryResultDocument) {
            return;
        }

        $this->collection = $dm->getDocumentCollection($class->name);

        if (! $class->isFile) {
            return;
        }

        $this->bucket = $dm->getDocumentBucket($class->name);
    }

    /** @return array<int, object> */
    public function getInserts(): array
    {
        return $this->queuedInserts;
    }

    public function isQueuedForInsert(object $document): bool
    {
        return isset($this->queuedInserts[spl_object_id($document)]);
    }

    /**
     * Adds a document to the queued insertions.
     * The document remains queued until {@link executeInserts} is invoked.
     */
    public function addInsert(object $document): void
    {
        $this->queuedInserts[spl_object_id($document)] = $document;
    }

    /** @return array<int, object> */
    public function getUpserts(): array
    {
        return $this->queuedUpserts;
    }

    public function isQueuedForUpsert(object $document): bool
    {
        return isset($this->queuedUpserts[spl_object_id($document)]);
    }

    /**
     * Adds a document to the queued upserts.
     * The document remains queued until {@link executeUpserts} is invoked.
     */
    public function addUpsert(object $document): void
    {
        $this->queuedUpserts[spl_object_id($document)] = $document;
    }

    /**
     * Gets the ClassMetadata instance of the document class this persister is
     * used for.
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->class;
    }

    /**
     * Executes all queued document insertions.
     *
     * Queued documents without an ID will inserted in a batch and queued
     * documents with an ID will be upserted individually.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @phpstan-param CommitOptions $options
     *
     * @throws DriverException
     */
    public function executeInserts(array $options = []): void
    {
        if (! $this->queuedInserts) {
            return;
        }

        $inserts = [];
        $options = $this->getWriteOptions($options);
        foreach ($this->queuedInserts as $oid => $document) {
            $data = $this->pb->prepareInsertData($document);

            // Set the initial version for each insert
            if ($this->class->isVersioned) {
                $versionMapping = $this->class->fieldMappings[$this->class->versionField];
                $nextVersion    = $this->class->propertyAccessors[$this->class->versionField]->getValue($document);
                $type           = $this->class->getFieldType($this->class->versionField);
                assert($type instanceof Versionable);
                if ($nextVersion === null) {
                    $nextVersion = $type->getNextVersion(null);
                    $this->class->propertyAccessors[$this->class->versionField]->setValue($document, $nextVersion);
                }

                $data[$versionMapping['name']] = $type->convertToDatabaseValue($nextVersion);
            }

            $inserts[] = $data;
        }

        try {
            assert($this->collection instanceof Collection);
            $this->collection->insertMany($inserts, $options);
        } catch (DriverException $e) {
            $this->queuedInserts = [];

            throw $e;
        }

        /* All collections except for ones using addToSet have already been
         * saved. We have left these to be handled separately to avoid checking
         * collection for uniqueness on PHP side.
         */
        foreach ($this->queuedInserts as $document) {
            $this->handleCollections($document, $options);
        }

        $this->queuedInserts = [];
    }

    /**
     * Executes all queued document upserts.
     *
     * Queued documents with an ID are upserted individually.
     *
     * If no upserts are queued, invoking this method is a NOOP.
     *
     * @phpstan-param CommitOptions $options
     */
    public function executeUpserts(array $options = []): void
    {
        if (! $this->queuedUpserts) {
            return;
        }

        $options = $this->getWriteOptions($options);
        foreach ($this->queuedUpserts as $oid => $document) {
            try {
                $this->executeUpsert($document, $options);
                $this->handleCollections($document, $options);
                unset($this->queuedUpserts[$oid]);
            } catch (BulkWriteException $e) {
                unset($this->queuedUpserts[$oid]);

                throw $e;
            }
        }
    }

    /**
     * Executes a single upsert in {@link executeUpserts}
     *
     * @param array<string, mixed> $options
     */
    private function executeUpsert(object $document, array $options): void
    {
        $options['upsert'] = true;
        $criteria          = $this->getQueryForDocument($document);

        $data = $this->pb->prepareUpsertData($document);

        // Set the initial version for each upsert
        if ($this->class->isVersioned) {
            $versionMapping = $this->class->fieldMappings[$this->class->versionField];
            $nextVersion    = $this->class->propertyAccessors[$this->class->versionField]->getValue($document);
            $type           = $this->class->getFieldType($this->class->versionField);
            assert($type instanceof Versionable);
            if ($nextVersion === null) {
                $nextVersion = $type->getNextVersion(null);
                $this->class->propertyAccessors[$this->class->versionField]->setValue($document, $nextVersion);
            }

            $data['$set'][$versionMapping['name']] = $type->convertToDatabaseValue($nextVersion);
        }

        foreach (array_keys($criteria) as $field) {
            unset($data['$set'][$field]);
            unset($data['$inc'][$field]);
            unset($data['$setOnInsert'][$field]);
        }

        // Do not send empty update operators
        foreach (['$set', '$inc', '$setOnInsert'] as $operator) {
            if (! empty($data[$operator])) {
                continue;
            }

            unset($data[$operator]);
        }

        /* If there are no modifiers remaining, we're upserting a document with
         * an identifier as its only field. Since a document with the identifier
         * may already exist, the desired behavior is "insert if not exists" and
         * NOOP otherwise. MongoDB 2.6+ does not allow empty modifiers, so $set
         * the identifier to the same value in our criteria.
         *
         * This will fail for versions before MongoDB 2.6, which require an
         * empty $set modifier. The best we can do (without attempting to check
         * server versions in advance) is attempt the 2.6+ behavior and retry
         * after the relevant exception.
         *
         * See: https://jira.mongodb.org/browse/SERVER-12266
         */
        if (empty($data)) {
            $retry = true;
            $data  = ['$set' => ['_id' => $criteria['_id']]];
        }

        assert($this->collection instanceof Collection);
        try {
            $this->collection->updateOne($criteria, $data, $options);

            return;
        } catch (BulkWriteException $e) {
            if (empty($retry) || strpos($e->getMessage(), 'Mod on _id not allowed') === false) {
                throw $e;
            }
        }

        $this->collection->updateOne($criteria, ['$set' => new stdClass()], $options);
    }

    /**
     * Updates the already persisted document if it has any new changesets.
     *
     * @phpstan-param CommitOptions $options
     *
     * @throws LockException
     */
    public function update(object $document, array $options = []): void
    {
        $update = $this->pb->prepareUpdateData($document);

        $query = $this->getQueryForDocument($document);

        foreach (array_keys($query) as $field) {
            unset($update['$set'][$field]);
        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }

        // Include versioning logic to set the new version value in the database
        // and to ensure the version has not changed since this document object instance
        // was fetched from the database
        $nextVersion = null;
        if ($this->class->isVersioned) {
            $versionMapping = $this->class->fieldMappings[$this->class->versionField];
            $currentVersion = $this->class->propertyAccessors[$this->class->versionField]->getValue($document);
            $type           = $this->class->getFieldType($this->class->versionField);
            assert($type instanceof Versionable);
            $nextVersion                             = $type->getNextVersion($currentVersion);
            $update['$set'][$versionMapping['name']] = $type->convertToDatabaseValue($nextVersion);
            $query[$versionMapping['name']]          = $type->convertToDatabaseValue($currentVersion);
        }

        if (! empty($update)) {
            // Include locking logic so that if the document object in memory is currently
            // locked then it will remove it, otherwise it ensures the document is not locked.
            if ($this->class->isLockable) {
                $isLocked    = $this->class->propertyAccessors[$this->class->lockField]->getValue($document);
                $lockMapping = $this->class->fieldMappings[$this->class->lockField];
                if ($isLocked) {
                    $update['$unset'] = [$lockMapping['name'] => true];
                } else {
                    $query[$lockMapping['name']] = ['$exists' => false];
                }
            }

            $options = $this->getWriteOptions($options);

            assert($this->collection instanceof Collection);
            $result = $this->collection->updateOne($query, $update, $options);

            if (($this->class->isVersioned || $this->class->isLockable) && $result->getModifiedCount() !== 1) {
                throw LockException::lockFailed($document);
            }

            if ($this->class->isVersioned) {
                $this->class->propertyAccessors[$this->class->versionField]->setValue($document, $nextVersion);
            }
        }

        $this->handleCollections($document, $options);
    }

    /**
     * Removes document from mongo
     *
     * @phpstan-param CommitOptions $options
     *
     * @throws LockException
     */
    public function delete(object $document, array $options = []): void
    {
        if ($this->bucket instanceof Bucket) {
            $documentIdentifier = $this->dm->getDocumentRegistry()->getDocumentIdentifier($document);
            $databaseIdentifier = $this->class->getDatabaseIdentifierValue($documentIdentifier);

            $this->bucket->delete($databaseIdentifier);

            return;
        }

        $query = $this->getQueryForDocument($document);

        if ($this->class->isLockable) {
            $query[$this->class->lockField] = ['$exists' => false];
        }

        $options = $this->getWriteOptions($options);

        assert($this->collection instanceof Collection);
        $result = $this->collection->deleteOne($query, $options);

        if (($this->class->isVersioned || $this->class->isLockable) && ! $result->getDeletedCount()) {
            throw LockException::lockFailed($document);
        }
    }

    /**
     * Refreshes a managed document.
     */
    public function refresh(object $document): void
    {
        assert($this->collection instanceof Collection);
        $query = $this->getQueryForDocument($document);
        $data  = $this->collection->findOne($query);
        if ($data === null) {
            throw MongoDBException::cannotRefreshDocument();
        }

        $data = $this->hydratorFactory->hydrate($document, (array) $data);
        $this->dm->getDocumentRegistry()->setOriginalDocumentData($document, $data);
        $this->uow->clearDocumentChangeSet($document);
    }

    /**
     * Finds a document by a set of criteria.
     *
     * If a scalar or MongoDB\BSON\ObjectId is provided for $criteria, it will
     * be used to match an _id value.
     *
     * @param array<string, mixed>|scalar|ObjectId|null                          $criteria Query criteria
     * @param array<string, int|string|SortDirection|array<string, string>>|null $sort
     * @param T|null                                                             $document
     * @phpstan-param SortShape|null $sort
     * @phpstan-param Hints $hints
     *
     * @return T|null
     *
     * @throws LockException
     *
     * @todo Check identity map? loadById method? Try to guess whether
     *     $criteria is the id?
     */
    public function load($criteria, ?object $document = null, array $hints = [], int $lockMode = 0, ?array $sort = null): ?object
    {
        // TODO: remove this
        if ($criteria === null || is_scalar($criteria) || $criteria instanceof ObjectId) {
            $criteria = ['_id' => $criteria];
        }

        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);

        $options = [];
        if ($sort !== null) {
            $options['sort'] = $this->prepareSort($sort, ['textScore']);
        }

        assert($this->collection instanceof Collection);
        $result = $this->collection->findOne($criteria, $options);
        $result = $result !== null ? (array) $result : null;

        if ($this->class->isLockable) {
            $lockMapping = $this->class->fieldMappings[$this->class->lockField];
            if (isset($result[$lockMapping['name']]) && $result[$lockMapping['name']] === LockMode::PESSIMISTIC_WRITE) {
                throw LockException::lockFailed($document);
            }
        }

        if ($result === null) {
            return null;
        }

        return $this->createDocument($result, $document, $hints);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param array<string, mixed>                                               $criteria
     * @param array<string, int|string|SortDirection|array<string, string>>|null $sort
     * @phpstan-param SortShape|null $sort
     *
     * @return Iterator<T>
     */
    public function loadAll(array $criteria = [], ?array $sort = null, ?int $limit = null, ?int $skip = null): Iterator
    {
        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);

        $options = [];
        if ($sort !== null) {
            $options['sort'] = $this->prepareSort($sort, ['textScore']);
        }

        if ($limit !== null) {
            $options['limit'] = $limit;
        }

        if ($skip !== null) {
            $options['skip'] = $skip;
        }

        assert($this->collection instanceof Collection);
        $baseCursor = $this->collection->find($criteria, $options);

        return $this->wrapCursor($baseCursor);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MongoDBException
     */
    private function getShardKeyQuery(object $document): array
    {
        if (! $this->class->isSharded()) {
            return [];
        }

        $shardKey = $this->class->getShardKey();
        $keys     = array_keys($shardKey['keys']);
        $data     = $this->uow->getDocumentActualData($document);

        $shardKeyQueryPart = [];
        foreach ($keys as $key) {
            assert(is_string($key));
            $mapping = $this->class->getFieldMappingByDbFieldName($key);
            $this->guardMissingShardKey($document, $key, $data);

            if (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $reference = $this->criteriaPreparer->prepareReference(
                    $key,
                    $data[$mapping['fieldName']],
                    $mapping,
                    false,
                );
                foreach ($reference as $keyValue) {
                    $shardKeyQueryPart[$keyValue[0]] = $keyValue[1];
                }
            } else {
                $shardKeyQueryPart[$key] = $this->class->getFieldType($mapping['fieldName'])->convertToDatabaseValue($data[$mapping['fieldName']]);
            }
        }

        return $shardKeyQueryPart;
    }

    /**
     * Wraps the supplied base cursor in the corresponding ODM class.
     *
     * @return Iterator<T>
     */
    private function wrapCursor(CursorInterface $baseCursor): Iterator
    {
        return new CachingIterator(new HydratingIterator($baseCursor, $this->dm->getUnitOfWork(), $this->class));
    }

    /**
     * Checks whether the given managed document exists in the database.
     */
    public function exists(object $document): bool
    {
        $id = $this->class->getIdentifierObject($document);
        assert($this->collection instanceof Collection);

        return (bool) $this->collection->findOne(['_id' => $id], ['_id']);
    }

    /**
     * Locks document by storing the lock mode on the mapped lock field.
     */
    public function lock(object $document, int $lockMode): void
    {
        $id          = $this->dm->getDocumentRegistry()->getDocumentIdentifier($document);
        $criteria    = ['_id' => $this->class->getDatabaseIdentifierValue($id)];
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        assert($this->collection instanceof Collection);
        $this->collection->updateOne($criteria, ['$set' => [$lockMapping['name'] => $lockMode]]);
        $this->class->propertyAccessors[$this->class->lockField]->setValue($document, $lockMode);
    }

    /**
     * Releases any lock that exists on this document.
     */
    public function unlock(object $document): void
    {
        $id          = $this->dm->getDocumentRegistry()->getDocumentIdentifier($document);
        $criteria    = ['_id' => $this->class->getDatabaseIdentifierValue($id)];
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        assert($this->collection instanceof Collection);
        $this->collection->updateOne($criteria, ['$unset' => [$lockMapping['name'] => true]]);
        $this->class->propertyAccessors[$this->class->lockField]->setValue($document, null);
    }

    /**
     * Creates or fills a single document object from an query result.
     *
     * @param array<string, mixed> $result   The query result.
     * @param object|null          $document The document object to fill, if any.
     * @param array                $hints    Hints for document creation.
     * @phpstan-param Hints $hints
     * @phpstan-param T|null $document
     *
     * @return object The filled and managed document object.
     * @phpstan-return T
     */
    private function createDocument(array $result, ?object $document = null, array $hints = []): object
    {
        if ($document !== null) {
            $hints[Query::HINT_REFRESH] = true;
            $id                         = $this->class->getPHPIdentifierValue($result['_id']);
            $this->uow->registerManaged($document, $id, $result);
        }

        return $this->uow->getOrCreateDocument($this->class->name, $result, $hints, $document);
    }

    /**
     * Loads a PersistentCollection data. Used in the initialize() method.
     *
     * @param PersistentCollectionInterface<array-key, object> $collection
     */
    public function loadCollection(PersistentCollectionInterface $collection): void
    {
        $mapping = $collection->getMapping();
        switch ($mapping['association']) {
            case ClassMetadata::EMBED_MANY:
                $this->loadEmbedManyCollection($collection);
                break;

            case ClassMetadata::REFERENCE_MANY:
                if (isset($mapping['repositoryMethod']) && $mapping['repositoryMethod']) {
                    $this->loadReferenceManyWithRepositoryMethod($collection);
                } else {
                    if ($mapping['isOwningSide']) {
                        $this->loadReferenceManyCollectionOwningSide($collection);
                    } else {
                        $this->loadReferenceManyCollectionInverseSide($collection);
                    }
                }

                break;
        }
    }

    /** @param PersistentCollectionInterface<array-key, object> $collection */
    private function loadEmbedManyCollection(PersistentCollectionInterface $collection): void
    {
        $embeddedDocuments = $collection->getMongoData();
        $mapping           = $collection->getMapping();
        $owner             = $collection->getOwner();

        if (! $embeddedDocuments) {
            return;
        }

        if ($owner === null) {
            throw PersistentCollectionException::ownerRequiredToLoadCollection();
        }

        foreach ($embeddedDocuments as $key => $embeddedDocument) {
            $className              = $this->dm->getClassNameForAssociation($mapping, $embeddedDocument);
            $embeddedMetadata       = $this->dm->getClassMetadata($className);
            $embeddedDocumentObject = $embeddedMetadata->newInstance();

            if (! is_array($embeddedDocument)) {
                throw HydratorException::associationItemTypeMismatch($owner::class, $mapping['name'], $key, 'array', gettype($embeddedDocument));
            }

            $this->dm->getDocumentRegistry()->setParentAssociation($embeddedDocumentObject, $mapping, $owner, $mapping['name'] . '.' . $key);

            $data = $this->hydratorFactory->hydrate($embeddedDocumentObject, $embeddedDocument, $collection->getHints());
            $id   = $data[$embeddedMetadata->identifier ?? ''] ?? null;

            if (empty($collection->getHints()[Query::HINT_READ_ONLY])) {
                $this->uow->registerManaged($embeddedDocumentObject, $id, $data);
            }

            if (CollectionHelper::isHash($mapping['strategy'])) {
                $collection->set($key, $embeddedDocumentObject);
            } else {
                $collection->add($embeddedDocumentObject);
            }
        }
    }

    /** @param PersistentCollectionInterface<array-key, object> $collection */
    private function loadReferenceManyCollectionOwningSide(PersistentCollectionInterface $collection): void
    {
        $hints      = $collection->getHints();
        $mapping    = $collection->getMapping();
        $owner      = $collection->getOwner();
        $groupedIds = [];

        if ($owner === null) {
            throw PersistentCollectionException::ownerRequiredToLoadCollection();
        }

        $sorted = isset($mapping['sort']) && $mapping['sort'];

        foreach ($collection->getMongoData() as $key => $reference) {
            $className = $this->dm->getClassNameForAssociation($mapping, $reference);

            if ($mapping['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID && ! is_array($reference)) {
                throw HydratorException::associationItemTypeMismatch($owner::class, $mapping['name'], $key, 'array', gettype($reference));
            }

            $identifier = ClassMetadata::getReferenceId($reference, $mapping['storeAs']);
            $id         = $this->dm->getClassMetadata($className)->getPHPIdentifierValue($identifier);

            // create a reference to the class and id
            $reference = $this->dm->getReference($className, $id);

            // no custom sort so add the references right now in the order they are embedded
            if (! $sorted) {
                if (CollectionHelper::isHash($mapping['strategy'])) {
                    $collection->set($key, $reference);
                } else {
                    $collection->add($reference);
                }
            }

            // only query for the referenced object if it is not already initialized or the collection is sorted
            if (! $this->uow->isUninitializedObject($reference) && ! $sorted) {
                continue;
            }

            $groupedIds[$className][] = $identifier;
        }

        foreach ($groupedIds as $className => $ids) {
            $class           = $this->dm->getClassMetadata($className);
            $mongoCollection = $this->dm->getDocumentCollection($className);
            $criteria        = $this->cm->merge(
                ['_id' => ['$in' => $ids]],
                $this->dm->getFilterCollection()->getFilterCriteria($class),
                $mapping['criteria'] ?? [],
            );
            $criteria        = $this->uow->getDocumentPersister($className)->prepareQueryOrNewObj($criteria);

            $options = [];
            if (isset($mapping['sort'])) {
                $options['sort'] = $this->prepareSort($mapping['sort']);
            }

            if (isset($mapping['limit'])) {
                $options['limit'] = $mapping['limit'];
            }

            if (isset($mapping['skip'])) {
                $options['skip'] = $mapping['skip'];
            }

            if (! empty($hints[Query::HINT_READ_PREFERENCE])) {
                $options['readPreference'] = $hints[Query::HINT_READ_PREFERENCE];
            }

            $cursor    = $mongoCollection->find($criteria, $options);
            $documents = $cursor->toArray();
            foreach ($documents as $documentData) {
                $document = $this->dm->getDocumentRegistry()->getById($documentData['_id'], $class);
                if ($this->uow->isUninitializedObject($document)) {
                    $data = $this->hydratorFactory->hydrate($document, $documentData);
                    $this->dm->getDocumentRegistry()->setOriginalDocumentData($document, $data);
                    $this->uow->clearDocumentChangeSet($document);
                }

                if (! $sorted) {
                    continue;
                }

                $collection->add($document);
            }
        }
    }

    /** @param PersistentCollectionInterface<array-key, object> $collection */
    private function loadReferenceManyCollectionInverseSide(PersistentCollectionInterface $collection): void
    {
        $query    = $this->createReferenceManyInverseSideQuery($collection);
        $iterator = $query->execute();
        assert($iterator instanceof Iterator);
        $documents = $iterator->toArray();
        foreach ($documents as $key => $document) {
            $collection->add($document);
        }
    }

    /** @param PersistentCollectionInterface<array-key, object> $collection */
    public function createReferenceManyInverseSideQuery(PersistentCollectionInterface $collection): Query
    {
        $hints   = $collection->getHints();
        $mapping = $collection->getMapping();
        $owner   = $collection->getOwner();

        if ($owner === null) {
            throw PersistentCollectionException::ownerRequiredToLoadCollection();
        }

        $ownerClass        = $this->dm->getClassMetadata($owner::class);
        $targetClass       = $this->dm->getClassMetadata($mapping['targetDocument']);
        $mappedByMapping   = $targetClass->fieldMappings[$mapping['mappedBy']] ?? [];
        $mappedByFieldName = ClassMetadata::getReferenceFieldName($mappedByMapping['storeAs'] ?? ClassMetadata::REFERENCE_STORE_AS_DB_REF, $mapping['mappedBy']);

        $criteria = $this->cm->merge(
            [$mappedByFieldName => $ownerClass->getIdentifierObject($owner)],
            $this->dm->getFilterCollection()->getFilterCriteria($targetClass),
            $mapping['criteria'] ?? [],
        );
        $criteria = $this->uow->getDocumentPersister($mapping['targetDocument'])->prepareQueryOrNewObj($criteria);
        $qb       = $this->dm->createQueryBuilder($mapping['targetDocument'])
            ->setQueryArray($criteria);

        if (isset($mapping['sort'])) {
            $qb->sort($mapping['sort']);
        }

        if (isset($mapping['limit'])) {
            $qb->limit($mapping['limit']);
        }

        if (isset($mapping['skip'])) {
            $qb->skip($mapping['skip']);
        }

        if (! empty($hints[Query::HINT_READ_PREFERENCE])) {
            $qb->setReadPreference($hints[Query::HINT_READ_PREFERENCE]);
        }

        foreach ($mapping['prime'] as $field) {
            $qb->field($field)->prime(true);
        }

        return $qb->getQuery();
    }

    /** @param PersistentCollectionInterface<array-key, object> $collection */
    private function loadReferenceManyWithRepositoryMethod(PersistentCollectionInterface $collection): void
    {
        $cursor    = $this->createReferenceManyWithRepositoryMethodCursor($collection);
        $mapping   = $collection->getMapping();
        $documents = $cursor->toArray();
        foreach ($documents as $key => $obj) {
            if (CollectionHelper::isHash($mapping['strategy'])) {
                $collection->set($key, $obj);
            } else {
                $collection->add($obj);
            }
        }
    }

    /**
     * @param PersistentCollectionInterface<array-key, object> $collection
     *
     * @return Iterator<object>
     */
    public function createReferenceManyWithRepositoryMethodCursor(PersistentCollectionInterface $collection): Iterator
    {
        $mapping          = $collection->getMapping();
        $repositoryMethod = $mapping['repositoryMethod'];
        $cursor           = $this->dm->getRepository($mapping['targetDocument'])
            ->$repositoryMethod($collection->getOwner());

        if (! $cursor instanceof Iterator) {
            throw new BadMethodCallException(sprintf('Expected repository method %s to return an iterable object', $repositoryMethod));
        }

        if (! empty($mapping['prime'])) {
            $referencePrimer = new ReferencePrimer($this->dm, $this->dm->getUnitOfWork());
            $primers         = array_combine($mapping['prime'], array_fill(0, count($mapping['prime']), true));
            $class           = $this->dm->getClassMetadata($mapping['targetDocument']);

            $cursor = new PrimingIterator($cursor, $class, $referencePrimer, $primers, $collection->getHints());
        }

        return $cursor;
    }

    /**
     * Prepare a projection array by converting keys, which are PHP property
     * names, to MongoDB field names.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public function prepareProjection(array $fields): array
    {
        return $this->criteriaPreparer->prepareProjection($fields);
    }

    /**
     * Prepare a sort specification array by converting keys to MongoDB field
     * names and changing direction strings to int.
     *
     * @param array<string, int|string|SortDirection|array<string, string>> $fields
     * @param list<string>                                                  $allowedMetaSort
     * @phpstan-param SortShape $fields
     *
     * @phpstan-return array<string, -1|1|SortMeta>
     *
     * @throws InvalidArgumentException if a sort direction is invalid.
     */
    public function prepareSort(array $fields, array $allowedMetaSort = []): array
    {
        return $this->criteriaPreparer->prepareSort($fields, $allowedMetaSort);
    }

    /**
     * Prepare a mongodb field name and convert the PHP property names to
     * MongoDB field names.
     */
    public function prepareFieldName(string $fieldName): string
    {
        return $this->criteriaPreparer->prepareFieldName($fieldName);
    }

    /**
     * Adds discriminator criteria to an already-prepared query.
     *
     * If the class we're querying has a discriminator field set, we add all
     * possible discriminator values to the query. The list of possible
     * discriminator values is based on the discriminatorValue of the class
     * itself as well as those of all its subclasses.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called before
     * {@link DocumentPerister::addFilterToPreparedQuery()}.
     *
     * @param array<string, mixed> $preparedQuery
     *
     * @return array<string, mixed>
     */
    public function addDiscriminatorToPreparedQuery(array $preparedQuery): array
    {
        return $this->criteriaPreparer->addDiscriminatorToPreparedQuery($preparedQuery);
    }

    /**
     * Adds filter criteria to an already-prepared query.
     *
     * This method should be used once for query criteria and not be used for
     * nested expressions. It should be called after
     * {@link DocumentPerister::addDiscriminatorToPreparedQuery()}.
     *
     * @param array<string, mixed> $preparedQuery
     *
     * @return array<string, mixed>
     */
    public function addFilterToPreparedQuery(array $preparedQuery): array
    {
        return $this->criteriaPreparer->addFilterToPreparedQuery($preparedQuery);
    }

    /**
     * Prepares the query criteria or new document object.
     *
     * PHP field names and types will be converted to those used by MongoDB.
     *
     * @param array<string|int, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function prepareQueryOrNewObj(array $query, bool $isNewObj = false): array
    {
        return $this->criteriaPreparer->prepareQueryOrNewObj($query, $isNewObj);
    }

    /** @param array<string, mixed> $options */
    private function handleCollections(object $document, array $options): void
    {
        // Collection deletions (deletions of complete collections)
        $collections = [];
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            if (! $this->uow->isCollectionScheduledForDeletion($coll)) {
                continue;
            }

            $collections[] = $coll;
        }

        if (! empty($collections)) {
            $this->cp->delete($document, $collections, $options);
        }

        // Collection updates (deleteRows, updateRows, insertRows)
        $collections = [];
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            if (! $this->uow->isCollectionScheduledForUpdate($coll)) {
                continue;
            }

            $collections[] = $coll;
        }

        if (empty($collections)) {
            return;
        }

        $this->cp->update($document, $collections, $options);
    }

    /**
     * If the document is new, ignore shard key field value, otherwise throw an
     * exception. Also, shard key field should be present in actual document
     * data.
     *
     * @param array<string, mixed> $actualDocumentData
     *
     * @throws MongoDBException
     */
    private function guardMissingShardKey(object $document, string $shardKeyField, array $actualDocumentData): void
    {
        $dcs      = $this->uow->getDocumentChangeSet($document);
        $isUpdate = $this->uow->isScheduledForUpdate($document);

        $fieldMapping = $this->class->getFieldMappingByDbFieldName($shardKeyField);
        $fieldName    = $fieldMapping['fieldName'];

        if ($isUpdate && isset($dcs[$fieldName]) && $dcs[$fieldName][0] !== $dcs[$fieldName][1]) {
            throw MongoDBException::shardKeyFieldCannotBeChanged($shardKeyField, $this->class->getName());
        }

        if (! isset($actualDocumentData[$fieldName])) {
            throw MongoDBException::shardKeyFieldMissing($shardKeyField, $this->class->getName());
        }
    }

    /**
     * Get shard key aware query for single document.
     *
     * @return array<string, mixed>
     */
    private function getQueryForDocument(object $document): array
    {
        $id = $this->dm->getDocumentRegistry()->getDocumentIdentifier($document);
        $id = $this->class->getDatabaseIdentifierValue($id);

        $shardKeyQueryPart = $this->getShardKeyQuery($document);

        return array_merge(['_id' => $id], $shardKeyQueryPart);
    }

    /**
     * @phpstan-param CommitOptions $options
     *
     * @phpstan-return CommitOptions
     */
    private function getWriteOptions(array $options = []): array
    {
        $defaultOptions  = $this->dm->getConfiguration()->getDefaultCommitOptions();
        $documentOptions = [];
        if ($this->class->hasWriteConcern()) {
            $documentOptions['writeConcern'] = new WriteConcern($this->class->getWriteConcern());
        }

        $writeOptions = array_merge($defaultOptions, $documentOptions, $options);
        if (array_key_exists('w', $writeOptions)) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.2',
                'The "w" option as commit option is deprecated, please pass "%s" object in "writeConcern" option.',
                WriteConcern::class,
            );
            $writeOptions['writeConcern'] = new WriteConcern($writeOptions['w']);
            unset($writeOptions['w']);
        }

        return $this->isInTransaction($options)
            ? $this->uow->stripTransactionOptions($writeOptions)
            : $writeOptions;
    }

    /** @param array<string, mixed> $options */
    private function isInTransaction(array $options): bool
    {
        if (! isset($options['session'])) {
            return false;
        }

        $session = $options['session'];
        if (! $session instanceof Session) {
            return false;
        }

        return $session->isInTransaction();
    }
}
