<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use BackedEnum;
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
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\Versionable;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\Persistence\Mapping\MappingException;
use InvalidArgumentException;
use Iterator as SplIterator;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Exception\Exception as DriverException;
use MongoDB\Driver\Exception\WriteException;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use MongoDB\GridFS\Bucket;
use stdClass;

use function array_combine;
use function array_fill;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function explode;
use function get_object_vars;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function spl_object_hash;
use function sprintf;
use function strpos;
use function strtolower;
use function trigger_deprecation;

/**
 * The DocumentPersister is responsible for persisting documents.
 *
 * @internal
 *
 * @template T of object
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
     * @var array<string, object>
     */
    private array $queuedInserts = [];

    /**
     * Array of queued inserts for the persister to insert.
     *
     * @var array<string, object>
     */
    private array $queuedUpserts = [];

    private CriteriaMerger $cm;

    private CollectionPersister $cp;

    /** @phpstan-param ClassMetadata<T> $class */
    public function __construct(
        private PersistenceBuilder $pb,
        private DocumentManager $dm,
        private UnitOfWork $uow,
        private HydratorFactory $hydratorFactory,
        private ClassMetadata $class,
        ?CriteriaMerger $cm = null,
    ) {
        $this->cm = $cm ?: new CriteriaMerger();
        $this->cp = $this->uow->getCollectionPersister();

        if ($class->isEmbeddedDocument || $class->isQueryResultDocument) {
            return;
        }

        $this->collection = $dm->getDocumentCollection($class->name);

        if (! $class->isFile) {
            return;
        }

        $this->bucket = $dm->getDocumentBucket($class->name);
    }

    /** @return array<string, object> */
    public function getInserts(): array
    {
        return $this->queuedInserts;
    }

    public function isQueuedForInsert(object $document): bool
    {
        return isset($this->queuedInserts[spl_object_hash($document)]);
    }

    /**
     * Adds a document to the queued insertions.
     * The document remains queued until {@link executeInserts} is invoked.
     */
    public function addInsert(object $document): void
    {
        $this->queuedInserts[spl_object_hash($document)] = $document;
    }

    /** @return array<string, object> */
    public function getUpserts(): array
    {
        return $this->queuedUpserts;
    }

    public function isQueuedForUpsert(object $document): bool
    {
        return isset($this->queuedUpserts[spl_object_hash($document)]);
    }

    /**
     * Adds a document to the queued upserts.
     * The document remains queued until {@link executeUpserts} is invoked.
     */
    public function addUpsert(object $document): void
    {
        $this->queuedUpserts[spl_object_hash($document)] = $document;
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
                $nextVersion    = $this->class->reflFields[$this->class->versionField]->getValue($document);
                $type           = Type::getType($versionMapping['type']);
                assert($type instanceof Versionable);
                if ($nextVersion === null) {
                    $nextVersion = $type->getNextVersion(null);
                    $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
                }

                $data[$versionMapping['name']] = $type->convertPHPToDatabaseValue($nextVersion);
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
            } catch (WriteException $e) {
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
            $nextVersion    = $this->class->reflFields[$this->class->versionField]->getValue($document);
            $type           = Type::getType($versionMapping['type']);
            assert($type instanceof Versionable);
            if ($nextVersion === null) {
                $nextVersion = $type->getNextVersion(null);
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
            }

            $data['$set'][$versionMapping['name']] = $type->convertPHPToDatabaseValue($nextVersion);
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

        try {
            assert($this->collection instanceof Collection);
            $this->collection->updateOne($criteria, $data, $options);

            return;
        } catch (WriteException $e) {
            if (empty($retry) || strpos($e->getMessage(), 'Mod on _id not allowed') === false) {
                throw $e;
            }
        }

        assert($this->collection instanceof Collection);
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
            $currentVersion = $this->class->reflFields[$this->class->versionField]->getValue($document);
            $type           = Type::getType($versionMapping['type']);
            assert($type instanceof Versionable);
            $nextVersion                             = $type->getNextVersion($currentVersion);
            $update['$set'][$versionMapping['name']] = Type::convertPHPToDatabaseValue($nextVersion);
            $query[$versionMapping['name']]          = Type::convertPHPToDatabaseValue($currentVersion);
        }

        if (! empty($update)) {
            // Include locking logic so that if the document object in memory is currently
            // locked then it will remove it, otherwise it ensures the document is not locked.
            if ($this->class->isLockable) {
                $isLocked    = $this->class->reflFields[$this->class->lockField]->getValue($document);
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
                $this->class->reflFields[$this->class->versionField]->setValue($document, $nextVersion);
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
            $documentIdentifier = $this->uow->getDocumentIdentifier($document);
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
        $this->uow->setOriginalDocumentData($document, $data);
    }

    /**
     * Finds a document by a set of criteria.
     *
     * If a scalar or MongoDB\BSON\ObjectId is provided for $criteria, it will
     * be used to match an _id value.
     *
     * @param array<string, mixed>|scalar|ObjectId|null            $criteria Query criteria
     * @param array<string, int|string|array<string, string>>|null $sort
     * @param T|null                                               $document
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
            $options['sort'] = $this->prepareSort($sort);
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
     * @param array<string, mixed>           $criteria
     * @param array<string, int|string>|null $sort
     */
    public function loadAll(array $criteria = [], ?array $sort = null, ?int $limit = null, ?int $skip = null): Iterator
    {
        $criteria = $this->prepareQueryOrNewObj($criteria);
        $criteria = $this->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->addFilterToPreparedQuery($criteria);

        $options = [];
        if ($sort !== null) {
            $options['sort'] = $this->prepareSort($sort);
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
                $reference = $this->prepareReference(
                    $key,
                    $data[$mapping['fieldName']],
                    $mapping,
                    false,
                );
                foreach ($reference as $keyValue) {
                    $shardKeyQueryPart[$keyValue[0]] = $keyValue[1];
                }
            } else {
                $value                   = Type::getType($mapping['type'])->convertToDatabaseValue($data[$mapping['fieldName']]);
                $shardKeyQueryPart[$key] = $value;
            }
        }

        return $shardKeyQueryPart;
    }

    /**
     * Wraps the supplied base cursor in the corresponding ODM class.
     */
    private function wrapCursor(SplIterator&CursorInterface $baseCursor): Iterator
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
        $id          = $this->uow->getDocumentIdentifier($document);
        $criteria    = ['_id' => $this->class->getDatabaseIdentifierValue($id)];
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        assert($this->collection instanceof Collection);
        $this->collection->updateOne($criteria, ['$set' => [$lockMapping['name'] => $lockMode]]);
        $this->class->reflFields[$this->class->lockField]->setValue($document, $lockMode);
    }

    /**
     * Releases any lock that exists on this document.
     */
    public function unlock(object $document): void
    {
        $id          = $this->uow->getDocumentIdentifier($document);
        $criteria    = ['_id' => $this->class->getDatabaseIdentifierValue($id)];
        $lockMapping = $this->class->fieldMappings[$this->class->lockField];
        assert($this->collection instanceof Collection);
        $this->collection->updateOne($criteria, ['$unset' => [$lockMapping['name'] => true]]);
        $this->class->reflFields[$this->class->lockField]->setValue($document, null);
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

            $this->uow->setParentAssociation($embeddedDocumentObject, $mapping, $owner, $mapping['name'] . '.' . $key);

            $data = $this->hydratorFactory->hydrate($embeddedDocumentObject, $embeddedDocument, $collection->getHints());
            $id   = $data[$embeddedMetadata->identifier] ?? null;

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
                ['_id' => ['$in' => array_values($ids)]],
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
                $document = $this->uow->getById($documentData['_id'], $class);
                if ($this->uow->isUninitializedObject($document)) {
                    $data = $this->hydratorFactory->hydrate($document, $documentData);
                    $this->uow->setOriginalDocumentData($document, $data);
                }

                if (! $sorted) {
                    continue;
                }

                $collection->add($document);
            }
        }
    }

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

            assert(is_array($primers));

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
        $preparedFields = [];

        foreach ($fields as $key => $value) {
            $preparedFields[$this->prepareFieldName($key)] = $value;
        }

        return $preparedFields;
    }

    /**
     * @param int|string $sort
     *
     * @return int|string
     */
    private function getSortDirection($sort)
    {
        switch (strtolower((string) $sort)) {
            case 'desc':
                return -1;

            case 'asc':
                return 1;
        }

        return $sort;
    }

    /**
     * Prepare a sort specification array by converting keys to MongoDB field
     * names and changing direction strings to int.
     *
     * @param array<string, int|string|array<string, string>> $fields
     * @phpstan-param SortShape $fields
     *
     * @phpstan-return array<string, -1|1|SortMeta>
     */
    public function prepareSort(array $fields): array
    {
        $sortFields = [];

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $sortFields[$this->prepareFieldName($key)] = $value;
            } else {
                $sortFields[$this->prepareFieldName($key)] = $this->getSortDirection($value);
            }
        }

        return $sortFields;
    }

    /**
     * Prepare a mongodb field name and convert the PHP property names to
     * MongoDB field names.
     */
    public function prepareFieldName(string $fieldName): string
    {
        $fieldNames = $this->prepareQueryElement($fieldName, null, null, false);

        return $fieldNames[0][0];
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
        if (isset($preparedQuery[$this->class->discriminatorField]) || $this->class->discriminatorField === null) {
            return $preparedQuery;
        }

        $discriminatorValues = $this->getClassDiscriminatorValues($this->class);

        if ($discriminatorValues === []) {
            return $preparedQuery;
        }

        if (count($discriminatorValues) === 1) {
            $preparedQuery[$this->class->discriminatorField] = $discriminatorValues[0];
        } else {
            $preparedQuery[$this->class->discriminatorField] = ['$in' => $discriminatorValues];
        }

        return $preparedQuery;
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
        /* If filter criteria exists for this class, prepare it and merge
         * over the existing query.
         *
         * @todo Consider recursive merging in case the filter criteria and
         * prepared query both contain top-level $and/$or operators.
         */
        $filterCriteria = $this->dm->getFilterCollection()->getFilterCriteria($this->class);
        if ($filterCriteria) {
            $preparedQuery = $this->cm->merge($preparedQuery, $this->prepareQueryOrNewObj($filterCriteria));
        }

        return $preparedQuery;
    }

    /**
     * Prepares the query criteria or new document object.
     *
     * PHP field names and types will be converted to those used by MongoDB.
     *
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function prepareQueryOrNewObj(array $query, bool $isNewObj = false): array
    {
        $preparedQuery = [];

        foreach ($query as $key => $value) {
            $key = (string) $key;

            // Recursively prepare logical query clauses
            if (in_array($key, ['$and', '$or', '$nor'], true) && is_array($value)) {
                foreach ($value as $k2 => $v2) {
                    $preparedQuery[$key][$k2] = $this->prepareQueryOrNewObj($v2, $isNewObj);
                }

                continue;
            }

            if (isset($key[0]) && $key[0] === '$' && is_array($value)) {
                $preparedQuery[$key] = $this->prepareQueryOrNewObj($value, $isNewObj);
                continue;
            }

            $preparedQueryElements = $this->prepareQueryElement($key, $value, null, true, $isNewObj);
            foreach ($preparedQueryElements as [$preparedKey, $preparedValue]) {
                $preparedValue               = $this->convertToDatabaseValue($key, $preparedValue);
                $preparedQuery[$preparedKey] = $preparedValue;
            }
        }

        return $preparedQuery;
    }

    /**
     * Converts a single value to its database representation based on the mapping type if possible.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function convertToDatabaseValue(string $fieldName, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($k === '$exists' || $k === '$type' || $k === '$currentDate') {
                    continue;
                }

                $value[$k] = $this->convertToDatabaseValue($fieldName, $v);
            }

            return $value;
        }

        if (! $this->class->hasField($fieldName)) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            return Type::convertPHPToDatabaseValue($value);
        }

        $mapping  = $this->class->fieldMappings[$fieldName];
        $typeName = $mapping['type'];

        if (! empty($mapping['reference']) || ! empty($mapping['embedded'])) {
            return $value;
        }

        if (! Type::hasType($typeName)) {
            throw new InvalidArgumentException(
                sprintf('Mapping type "%s" does not exist', $typeName),
            );
        }

        if ($value instanceof BackedEnum && isset($mapping['enumType'])) {
            $value = $value->value;
        }

        if (in_array($typeName, ['collection', 'hash'])) {
            return $value;
        }

        $type  = Type::getType($typeName);
        $value = $type->convertToDatabaseValue($value);

        return $value;
    }

    /**
     * Prepares a query value and converts the PHP value to the database value
     * if it is an identifier.
     *
     * It also handles converting $fieldName to the database name if they are
     * different.
     *
     * @param mixed $value
     *
     * @return array<array{string, mixed}>
     */
    private function prepareQueryElement(string $fieldName, $value = null, ?ClassMetadata $class = null, bool $prepareValue = true, bool $inNewObj = false): array
    {
        $class ??= $this->class;

        // @todo Consider inlining calls to ClassMetadata methods

        // Process all non-identifier fields by translating field names
        if ($class->hasField($fieldName) && ! $class->isIdentifier($fieldName)) {
            $mapping   = $class->fieldMappings[$fieldName];
            $fieldName = $mapping['name'];

            if (! $prepareValue) {
                return [[$fieldName, $value]];
            }

            // Prepare mapped, embedded objects
            if (
                ! empty($mapping['embedded']) && is_object($value) &&
                ! $this->dm->getMetadataFactory()->isTransient($value::class)
            ) {
                return [[$fieldName, $this->pb->prepareEmbeddedDocumentValue($mapping, $value)]];
            }

            if (! empty($mapping['reference']) && is_object($value) && ! ($value instanceof ObjectId)) {
                try {
                    return $this->prepareReference($fieldName, $value, $mapping, $inNewObj);
                } catch (MappingException) {
                    // do nothing in case passed object is not mapped document
                }
            }

            // No further preparation unless we're dealing with a simple reference
            if (empty($mapping['reference']) || $mapping['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID || empty((array) $value)) {
                return [[$fieldName, $value]];
            }

            // Additional preparation for one or more simple reference values
            $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if (! is_array($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if (! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $targetClass)]];
        }

        // Process identifier fields
        if (($class->hasField($fieldName) && $class->isIdentifier($fieldName)) || $fieldName === '_id') {
            $fieldName = '_id';

            if (! $prepareValue) {
                return [[$fieldName, $value]];
            }

            if (! is_array($value)) {
                return [[$fieldName, $class->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if (! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $class->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $class)]];
        }

        // No processing for unmapped, non-identifier, non-dotted field names
        if (strpos($fieldName, '.') === false) {
            return [[$fieldName, $value]];
        }

        /* Process "fieldName.objectProperty" queries (on arrays or objects).
         *
         * We can limit parsing here, since at most three segments are
         * significant: "fieldName.objectProperty" with an optional index or key
         * for collections stored as either BSON arrays or objects.
         */
        $e = explode('.', $fieldName, 4);

        // No further processing for unmapped fields
        if (! isset($class->fieldMappings[$e[0]])) {
            return [[$fieldName, $value]];
        }

        $mapping = $class->fieldMappings[$e[0]];
        $e[0]    = $mapping['name'];

        // Hash and raw fields will not be prepared beyond the field name
        if ($mapping['type'] === Type::HASH || $mapping['type'] === Type::RAW) {
            $fieldName = implode('.', $e);

            return [[$fieldName, $value]];
        }

        if (
            $mapping['type'] === ClassMetadata::MANY && CollectionHelper::isHash($mapping['strategy'])
                && isset($e[2])
        ) {
            $objectProperty       = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty   = implode('.', array_slice($e, 3));
        } elseif ($e[1] !== '$') {
            $fieldName            = $e[0] . '.' . $e[1];
            $objectProperty       = $e[1];
            $objectPropertyPrefix = '';
            $nextObjectProperty   = implode('.', array_slice($e, 2));
        } elseif (isset($e[2])) {
            $fieldName            = $e[0] . '.' . $e[1] . '.' . $e[2];
            $objectProperty       = $e[2];
            $objectPropertyPrefix = $e[1] . '.';
            $nextObjectProperty   = implode('.', array_slice($e, 3));
        } else {
            $fieldName = $e[0] . '.' . $e[1];

            return [[$fieldName, $value]];
        }

        // No further processing for fields without a targetDocument mapping
        if (! isset($mapping['targetDocument'])) {
            if ($nextObjectProperty) {
                $fieldName .= '.' . $nextObjectProperty;
            }

            return [[$fieldName, $value]];
        }

        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);

        // No further processing for unmapped targetDocument fields
        if (! $targetClass->hasField($objectProperty)) {
            if ($nextObjectProperty) {
                $fieldName .= '.' . $nextObjectProperty;
            }

            return [[$fieldName, $value]];
        }

        $targetMapping      = $targetClass->getFieldMapping($objectProperty);
        $objectPropertyIsId = $targetClass->isIdentifier($objectProperty);

        // Prepare DBRef identifiers or the mapped field's property path
        $fieldName = $objectPropertyIsId && ! empty($mapping['reference']) && $mapping['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID
            ? ClassMetadata::getReferenceFieldName($mapping['storeAs'], $e[0])
            : $e[0] . '.' . $objectPropertyPrefix . $targetMapping['name'];

        // Process targetDocument identifier fields
        if ($objectPropertyIsId) {
            if (! $prepareValue) {
                return [[$fieldName, $value]];
            }

            if (! is_array($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            // Objects without operators or with DBRef fields can be converted immediately
            if (! $this->hasQueryOperators($value) || $this->hasDBRefFields($value)) {
                return [[$fieldName, $targetClass->getDatabaseIdentifierValue($value)]];
            }

            return [[$fieldName, $this->prepareQueryExpression($value, $targetClass)]];
        }

        /* The property path may include a third field segment, excluding the
         * collection item pointer. If present, this next object property must
         * be processed recursively.
         */
        if ($nextObjectProperty) {
            // Respect the targetDocument's class metadata when recursing
            $nextTargetClass = isset($targetMapping['targetDocument'])
                ? $this->dm->getClassMetadata($targetMapping['targetDocument'])
                : null;

            if (empty($targetMapping['reference'])) {
                $fieldNames = $this->prepareQueryElement($nextObjectProperty, $value, $nextTargetClass, $prepareValue);
            } else {
                // No recursive processing for references as most probably somebody is querying DBRef or alike
                if ($nextObjectProperty[0] !== '$' && in_array($targetMapping['storeAs'], [ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB, ClassMetadata::REFERENCE_STORE_AS_DB_REF])) {
                    $nextObjectProperty = '$' . $nextObjectProperty;
                }

                $fieldNames = [[$nextObjectProperty, $value]];
            }

            return array_map(static function ($preparedTuple) use ($fieldName) {
                [$key, $value] = $preparedTuple;

                return [$fieldName . '.' . $key, $value];
            }, $fieldNames);
        }

        return [[$fieldName, $value]];
    }

    /**
     * @param array<string, mixed> $expression
     *
     * @return array<string, mixed>
     */
    private function prepareQueryExpression(array $expression, ClassMetadata $class): array
    {
        foreach ($expression as $k => $v) {
            // Ignore query operators whose arguments need no type conversion
            if (in_array($k, ['$exists', '$type', '$mod', '$size'])) {
                continue;
            }

            // Process query operators whose argument arrays need type conversion
            if (in_array($k, ['$in', '$nin', '$all']) && is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($v2 instanceof $class->name) {
                        // If a value in a query is a target document, e.g. ['referenceField' => $targetDocument],
                        // retreive id from target document and convert this id using it's type
                        $expression[$k][$k2] = $class->getDatabaseIdentifierValue($class->getIdentifierValue($v2));

                        continue;
                    }

                    // Otherwise if a value in a query is already id, e.g. ['referenceField' => $targetDocumentId],
                    // just convert id to it's database representation using it's type
                    $expression[$k][$k2] = $class->getDatabaseIdentifierValue($v2);
                }

                continue;
            }

            // Recursively process expressions within a $not or $elemMatch operator
            if ($k === '$elemMatch' && is_array($v)) {
                $expression[$k] = $this->prepareQueryOrNewObj($v, false);
                continue;
            }

            if ($k === '$not' && is_array($v)) {
                $expression[$k] = $this->prepareQueryExpression($v, $class);
                continue;
            }

            if ($v instanceof $class->name) {
                $expression[$k] = $class->getDatabaseIdentifierValue($class->getIdentifierValue($v));
            } else {
                $expression[$k] = $class->getDatabaseIdentifierValue($v);
            }
        }

        return $expression;
    }

    /**
     * Checks whether the value has DBRef fields.
     *
     * This method doesn't check if the the value is a complete DBRef object,
     * although it should return true for a DBRef. Rather, we're checking that
     * the value has one or more fields for a DBref. In practice, this could be
     * $elemMatch criteria for matching a DBRef.
     *
     * @param mixed $value
     */
    private function hasDBRefFields($value): bool
    {
        if (! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $value) {
            if ($key === '$ref' || $key === '$id' || $key === '$db') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the value has query operators.
     *
     * @param mixed $value
     */
    private function hasQueryOperators($value): bool
    {
        if (! is_array($value) && ! is_object($value)) {
            return false;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        foreach ($value as $key => $notUsedValue) {
            $key = (string) $key;

            if (isset($key[0]) && $key[0] === '$') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of discriminator values for the given ClassMetadata
     *
     * @return list<class-string|null>
     */
    private function getClassDiscriminatorValues(ClassMetadata $metadata): array
    {
        $discriminatorValues = [];

        if ($metadata->discriminatorValue !== null) {
            $discriminatorValues[] = $metadata->discriminatorValue;
        }

        foreach ($metadata->subClasses as $className) {
            $key = array_search($className, $metadata->discriminatorMap);
            if (! $key) {
                continue;
            }

            $discriminatorValues[] = $key;
        }

        // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
        if ($metadata->defaultDiscriminatorValue && in_array($metadata->defaultDiscriminatorValue, $discriminatorValues)) {
            $discriminatorValues[] = null;
        }

        return $discriminatorValues;
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
        $id = $this->uow->getDocumentIdentifier($document);
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

    /**
     * @phpstan-param FieldMapping $mapping
     *
     * @phpstan-return array<array{
     *     string,
     *     string|ObjectId|array<string, mixed>
     * }>
     */
    private function prepareReference(string $fieldName, object $value, array $mapping, bool $inNewObj): array
    {
        $reference = $this->dm->createReference($value, $mapping);
        if ($inNewObj || $mapping['storeAs'] === ClassMetadata::REFERENCE_STORE_AS_ID) {
            return [[$fieldName, $reference]];
        }

        switch ($mapping['storeAs']) {
            case ClassMetadata::REFERENCE_STORE_AS_REF:
                $keys = ['id' => true];
                break;

            case ClassMetadata::REFERENCE_STORE_AS_DB_REF:
            case ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                $keys = ['$ref' => true, '$id' => true, '$db' => true];

                if ($mapping['storeAs'] === ClassMetadata::REFERENCE_STORE_AS_DB_REF) {
                    unset($keys['$db']);
                }

                if (isset($mapping['targetDocument'])) {
                    unset($keys['$ref'], $keys['$db']);
                }

                break;

            default:
                throw new InvalidArgumentException(sprintf('Reference type %s is invalid.', $mapping['storeAs']));
        }

        if ($mapping['type'] === ClassMetadata::MANY) {
            return [[$fieldName, ['$elemMatch' => array_intersect_key($reference, $keys)]]];
        }

        return array_map(
            static fn ($key) => [$fieldName . '.' . $key, $reference[$key]],
            array_keys($keys),
        );
    }
}
