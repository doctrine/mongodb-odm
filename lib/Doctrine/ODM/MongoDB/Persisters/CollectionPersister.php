<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use function array_map;
use function array_reverse;
use function array_values;
use function get_class;
use function implode;
use function sprintf;

/**
 * The CollectionPersister is responsible for persisting collections of embedded
 * or referenced documents. When a PersistentCollection is scheduledForDeletion
 * in the UnitOfWork by calling PersistentCollection::clear() or is
 * de-referenced in the domain application code, CollectionPersister::delete()
 * will be called. When documents within the PersistentCollection are added or
 * removed, CollectionPersister::update() will be called, which may set the
 * entire collection or delete/insert individual elements, depending on the
 * mapping strategy.
 *
 */
class CollectionPersister
{
    /** @var DocumentManager */
    private $dm;

    /** @var PersistenceBuilder */
    private $pb;

    /** @var UnitOfWork */
    private $uow;

    public function __construct(DocumentManager $dm, PersistenceBuilder $pb, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->pb = $pb;
        $this->uow = $uow;
    }

    /**
     * Deletes a PersistentCollection instance completely from a document using $unset.
     */
    public function delete(PersistentCollectionInterface $coll, array $options): void
    {
        $mapping = $coll->getMapping();
        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }
        if (CollectionHelper::isAtomic($mapping['strategy'])) {
            throw new \UnexpectedValueException($mapping['strategy'] . ' delete collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');
        }
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        $query = ['$unset' => [$propertyPath => true]];
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Updates a PersistentCollection instance deleting removed rows and
     * inserting new rows.
     */
    public function update(PersistentCollectionInterface $coll, array $options): void
    {
        $mapping = $coll->getMapping();

        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }

        switch ($mapping['strategy']) {
            case ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET:
            case ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY:
                throw new \UnexpectedValueException($mapping['strategy'] . ' update collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');

            case ClassMetadata::STORAGE_STRATEGY_SET:
            case ClassMetadata::STORAGE_STRATEGY_SET_ARRAY:
                $this->setCollection($coll, $options);
                break;

            case ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET:
            case ClassMetadata::STORAGE_STRATEGY_PUSH_ALL:
                $coll->initialize();
                $this->deleteElements($coll, $options);
                $this->insertElements($coll, $options);
                break;

            default:
                throw new \UnexpectedValueException('Unsupported collection strategy: ' . $mapping['strategy']);
        }
    }

    /**
     * Sets a PersistentCollection instance.
     *
     * This method is intended to be used with the "set" or "setArray"
     * strategies. The "setArray" strategy will ensure that the collection is
     * set as a BSON array, which means the collection elements will be
     * reindexed numerically before storage.
     */
    private function setCollection(PersistentCollectionInterface $coll, array $options): void
    {
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        $coll->initialize();
        $mapping = $coll->getMapping();
        $setData = $this->pb->prepareAssociatedCollectionValue($coll, CollectionHelper::usesSet($mapping['strategy']));
        $query = ['$set' => [$propertyPath => $setData]];
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Deletes removed elements from a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     */
    private function deleteElements(PersistentCollectionInterface $coll, array $options): void
    {
        $deleteDiff = $coll->getDeleteDiff();

        if (empty($deleteDiff)) {
            return;
        }

        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $query = ['$unset' => []];

        foreach ($deleteDiff as $key => $document) {
            $query['$unset'][$propertyPath . '.' . $key] = true;
        }

        $this->executeQuery($parent, $query, $options);

        /**
         * @todo This is a hack right now because we don't have a proper way to
         * remove an element from an array by its key. Unsetting the key results
         * in the element being left in the array as null so we have to pull
         * null values.
         */
        $this->executeQuery($parent, ['$pull' => [$propertyPath => null]], $options);
    }

    /**
     * Inserts new elements for a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     */
    private function insertElements(PersistentCollectionInterface $coll, array $options): void
    {
        $insertDiff = $coll->getInsertDiff();

        if (empty($insertDiff)) {
            return;
        }

        $mapping = $coll->getMapping();

        switch ($mapping['strategy']) {
            case ClassMetadata::STORAGE_STRATEGY_PUSH_ALL:
                $operator = 'push';
                break;

            case ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET:
                $operator = 'addToSet';
                break;

            default:
                throw new \LogicException(sprintf('Invalid strategy %s given for insertElements', $mapping['strategy']));
        }

        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $callback = isset($mapping['embedded'])
            ? function ($v) use ($mapping) {
                return $this->pb->prepareEmbeddedDocumentValue($mapping, $v);
            }
            : function ($v) use ($mapping) {
                return $this->pb->prepareReferencedDocumentValue($mapping, $v);
            };

        $value = array_values(array_map($callback, $insertDiff));

        $query = ['$' . $operator => [$propertyPath => ['$each' => $value]]];

        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Gets the parent information for a given PersistentCollection. It will
     * retrieve the top-level persistent Document that the PersistentCollection
     * lives in. We can use this to issue queries when updating a
     * PersistentCollection that is multiple levels deep inside an embedded
     * document.
     *
     *     <code>
     *     list($path, $parent) = $this->getPathAndParent($coll)
     *     </code>
     */
    private function getPathAndParent(PersistentCollectionInterface $coll): array
    {
        $mapping = $coll->getMapping();
        $fields = [];
        $parent = $coll->getOwner();
        while (($association = $this->uow->getParentAssociation($parent)) !== null) {
            list($m, $owner, $field) = $association;
            if (isset($m['reference'])) {
                break;
            }
            $parent = $owner;
            $fields[] = $field;
        }
        $propertyPath = implode('.', array_reverse($fields));
        $path = $mapping['name'];
        if ($propertyPath) {
            $path = $propertyPath . '.' . $path;
        }
        return [$path, $parent];
    }

    /**
     * Executes a query updating the given document.
     */
    private function executeQuery(object $document, array $newObj, array $options): void
    {
        $className = get_class($document);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
        $query = ['_id' => $id];
        if ($class->isVersioned) {
            $query[$class->fieldMappings[$class->versionField]['name']] = $class->reflFields[$class->versionField]->getValue($document);
        }
        $collection = $this->dm->getDocumentCollection($className);
        $result = $collection->updateOne($query, $newObj, $options);
        if ($class->isVersioned && ! $result->getMatchedCount()) {
            throw LockException::lockFailed($document);
        }
    }
}
