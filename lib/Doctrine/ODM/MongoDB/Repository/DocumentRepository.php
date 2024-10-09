<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ODM\MongoDB\Aggregation\Builder as AggregationBuilder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\ObjectRepository;

use function assert;
use function count;
use function is_array;
use function trigger_deprecation;

/**
 * A DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @template T of object
 * @template-implements Selectable<int,T>
 * @template-implements ObjectRepository<T>
 */
class DocumentRepository implements ObjectRepository, Selectable
{
    /** @var class-string<T> */
    protected $documentName;

    /** @var DocumentManager */
    protected $dm;

    /** @var UnitOfWork */
    protected $uow;

    /** @var ClassMetadata<T> */
    protected $class;

    /**
     * Initializes this instance with the specified document manager, unit of work and class metadata.
     *
     * @param DocumentManager $dm            The DocumentManager to use.
     * @param UnitOfWork      $uow           The UnitOfWork to use.
     * @param ClassMetadata   $classMetadata The class metadata.
     * @phpstan-param ClassMetadata<T>   $classMetadata The class metadata.
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $classMetadata)
    {
        $this->documentName = $classMetadata->name;
        $this->dm           = $dm;
        $this->uow          = $uow;
        $this->class        = $classMetadata;
    }

    /**
     * Creates a new Query\Builder instance that is preconfigured for this document name.
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return $this->dm->createQueryBuilder($this->documentName);
    }

    /**
     * Creates a new Aggregation\Builder instance that is prepopulated for this document name.
     */
    public function createAggregationBuilder(): AggregationBuilder
    {
        return $this->dm->createAggregationBuilder($this->documentName);
    }

    /**
     * Clears the repository, causing all managed documents to become detached.
     *
     * @deprecated Deprecated in 2.6, will be removed in 3.0
     */
    public function clear(): void
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.6',
            'The %s() method is deprecated and will be removed in Doctrine ODM 3.0.',
            __METHOD__,
        );

        $this->dm->clear($this->class->rootDocumentName);
    }

    /**
     * Finds a document matching the specified identifier. Optionally a lock mode and
     * expected version may be specified.
     *
     * @param mixed $id Identifier.
     *
     * @return T|null
     *
     * @throws MappingException
     * @throws LockException
     */
    public function find($id, int $lockMode = LockMode::NONE, ?int $lockVersion = null): ?object
    {
        if ($id === null) {
            return null;
        }

        /* TODO: What if the ID object has a field with the same name as the
         * class' mapped identifier field name?
         */
        if (is_array($id)) {
            [$identifierFieldName] = $this->class->getIdentifierFieldNames();

            if (isset($id[$identifierFieldName])) {
                $id = $id[$identifierFieldName];
            }
        }

        // Check identity map first
        $document = $this->uow->tryGetById($id, $this->class);
        if ($document) {
            if ($lockMode !== LockMode::NONE) {
                $this->dm->lock($document, $lockMode, $lockVersion);
            }

            return $document; // Hit!
        }

        $criteria = ['_id' => $id];

        if ($lockMode === LockMode::NONE) {
            return $this->getDocumentPersister()->load($criteria);
        }

        if ($lockMode === LockMode::OPTIMISTIC) {
            if (! $this->class->isVersioned) {
                throw LockException::notVersioned($this->documentName);
            }

            $document = $this->getDocumentPersister()->load($criteria);
            if ($document !== null) {
                $this->uow->lock($document, $lockMode, $lockVersion);
            }

            return $document;
        }

        return $this->getDocumentPersister()->load($criteria, null, [], $lockMode);
    }

    /**
     * Finds all documents in the repository.
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * Finds documents by a set of criteria.
     *
     * @param int|null $limit
     * @param int|null $offset
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->getDocumentPersister()->loadAll($criteria, $orderBy, $limit, $offset)->toArray();
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array<string, mixed>|null $sort
     * @param array<string, mixed>      $criteria
     *
     * @return T|null The object.
     */
    public function findOneBy(array $criteria, ?array $sort = null): ?object
    {
        return $this->getDocumentPersister()->load($criteria, null, [], 0, $sort);
    }

    /** @return class-string<T> */
    public function getDocumentName(): string
    {
        return $this->documentName;
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->dm;
    }

    /** @return ClassMetadata<T> */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->class;
    }

    /** @return class-string<T> */
    public function getClassName(): string
    {
        return $this->getDocumentName();
    }

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     *
     * @return ArrayCollection<array-key, T>
     */
    public function matching(Criteria $criteria): ArrayCollection
    {
        $visitor      = new QueryExpressionVisitor($this->createQueryBuilder());
        $queryBuilder = $this->createQueryBuilder();

        if ($criteria->getWhereExpression() !== null) {
            $expr = $visitor->dispatch($criteria->getWhereExpression());
            $queryBuilder->setQueryArray($expr->getQuery());
        }

        if ($criteria->getMaxResults() !== null) {
            $queryBuilder->limit($criteria->getMaxResults());
        }

        if ($criteria->getFirstResult() !== null) {
            $queryBuilder->skip($criteria->getFirstResult());
        }

        if (count($criteria->getOrderings())) {
            $queryBuilder->sort($criteria->getOrderings());
        }

        // @TODO: wrap around a specialized Collection for efficient count on large collections
        /** @var Iterator<T> $iterator */
        $iterator = $queryBuilder->getQuery()->execute();
        assert($iterator instanceof Iterator);

        return new ArrayCollection($iterator->toArray());
    }

    /** @return DocumentPersister<T> */
    protected function getDocumentPersister(): DocumentPersister
    {
        return $this->uow->getDocumentPersister($this->documentName);
    }
}
