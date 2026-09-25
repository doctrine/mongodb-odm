<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\UnitOfWork;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;
use SortDirection;

use function assert;
use function is_scalar;

/**
 * DocumentLoader is responsible for loading single documents, the read-side
 * counterpart to DocumentPersister. It is obtained via
 * UnitOfWork::getDocumentLoader() to load, refresh, lock and check the
 * existence of managed documents.
 *
 * @internal
 *
 * @template T of object = object
 *
 * @phpstan-import-type Hints from UnitOfWork
 * @phpstan-import-type SortMeta from Sort
 * @phpstan-import-type SortShape from Sort
 */
final class DocumentLoader
{
    /** @phpstan-param ClassMetadata<T> $class */
    public function __construct(
        private DocumentManager $dm,
        private UnitOfWork $uow,
        private HydratorFactory $hydratorFactory,
        private ClassMetadata $class,
        private CriteriaPreparer $criteriaPreparer,
        private ShardKeyQueryBuilder $shardKeyQueryBuilder,
        private ?Collection $collection,
    ) {
    }

    /**
     * Refreshes a managed document.
     */
    public function refresh(object $document): void
    {
        assert($this->collection instanceof Collection);
        $query = $this->shardKeyQueryBuilder->getQueryForDocument($document);
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

        $criteria = $this->criteriaPreparer->prepareQueryOrNewObj($criteria);
        $criteria = $this->criteriaPreparer->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->criteriaPreparer->addFilterToPreparedQuery($criteria);

        $options = [];
        if ($sort !== null) {
            $options['sort'] = $this->criteriaPreparer->prepareSort($sort, ['textScore']);
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
        $criteria = $this->criteriaPreparer->prepareQueryOrNewObj($criteria);
        $criteria = $this->criteriaPreparer->addDiscriminatorToPreparedQuery($criteria);
        $criteria = $this->criteriaPreparer->addFilterToPreparedQuery($criteria);

        $options = [];
        if ($sort !== null) {
            $options['sort'] = $this->criteriaPreparer->prepareSort($sort, ['textScore']);
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
}
