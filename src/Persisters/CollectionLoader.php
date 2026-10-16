<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorException;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\PrimingIterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

use function array_combine;
use function array_fill;
use function assert;
use function count;
use function gettype;
use function is_array;
use function sprintf;

/**
 * CollectionLoader is responsible for loading the contents of a PersistentCollection,
 * the read-side counterpart to CollectionPersister. It is invoked from
 * PersistentCollectionInterface::initialize() (via UnitOfWork::loadCollection())
 * to lazily populate embedded or referenced collections.
 *
 * @internal
 */
final class CollectionLoader
{
    public function __construct(
        private DocumentManager $dm,
        private UnitOfWork $uow,
        private HydratorFactory $hydratorFactory,
        private CriteriaPreparer $criteriaPreparer,
        private CriteriaMerger $cm,
    ) {
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
            $criteria        = $this->uow->getCriteriaPreparer($className)->prepareQueryOrNewObj($criteria);

            $options = [];
            if (isset($mapping['sort'])) {
                $options['sort'] = $this->criteriaPreparer->prepareSort($mapping['sort']);
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
        $criteria = $this->uow->getCriteriaPreparer($mapping['targetDocument'])->prepareQueryOrNewObj($criteria);
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
}
