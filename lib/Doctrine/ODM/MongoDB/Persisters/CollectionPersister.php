<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use LogicException;
use UnexpectedValueException;

use function array_diff_key;
use function array_fill_keys;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_values;
use function assert;
use function count;
use function end;
use function get_class;
use function implode;
use function sort;
use function strpos;

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
 * @internal
 */
final class CollectionPersister
{
    /** @var DocumentManager */
    private $dm;

    /** @var PersistenceBuilder */
    private $pb;

    /** @var UnitOfWork */
    private $uow;

    public function __construct(DocumentManager $dm, PersistenceBuilder $pb, UnitOfWork $uow)
    {
        $this->dm  = $dm;
        $this->pb  = $pb;
        $this->uow = $uow;
    }

    /**
     * Deletes a PersistentCollection instances completely from a document using $unset.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    public function delete(object $parent, array $collections, array $options): void
    {
        $unsetPathsMap = [];

        foreach ($collections as $collection) {
            $mapping = $collection->getMapping();
            if ($mapping['isInverseSide']) {
                continue; // ignore inverse side
            }

            if (CollectionHelper::isAtomic($mapping['strategy'])) {
                throw new UnexpectedValueException($mapping['strategy'] . ' delete collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');
            }

            [$propertyPath]               = $this->getPathAndParent($collection);
            $unsetPathsMap[$propertyPath] = true;
        }

        if (empty($unsetPathsMap)) {
            return;
        }

        /** @var string[] $unsetPaths */
        $unsetPaths = array_keys($unsetPathsMap);

        $unsetPaths = array_fill_keys($this->excludeSubPaths($unsetPaths), true);
        $query      = ['$unset' => $unsetPaths];
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Updates a list PersistentCollection instances deleting removed rows and inserting new rows.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    public function update(object $parent, array $collections, array $options): void
    {
        $setStrategyColls     = [];
        $addPushStrategyColls = [];

        foreach ($collections as $coll) {
            $mapping = $coll->getMapping();

            if ($mapping['isInverseSide']) {
                continue; // ignore inverse side
            }

            switch ($mapping['strategy']) {
                case ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET:
                case ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY:
                    throw new UnexpectedValueException($mapping['strategy'] . ' update collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');

                case ClassMetadata::STORAGE_STRATEGY_SET:
                case ClassMetadata::STORAGE_STRATEGY_SET_ARRAY:
                    $setStrategyColls[] = $coll;
                    break;

                case ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET:
                case ClassMetadata::STORAGE_STRATEGY_PUSH_ALL:
                    $addPushStrategyColls[] = $coll;
                    break;

                default:
                    throw new UnexpectedValueException('Unsupported collection strategy: ' . $mapping['strategy']);
            }
        }

        if (! empty($setStrategyColls)) {
            $this->setCollections($parent, $setStrategyColls, $options);
        }

        if (empty($addPushStrategyColls)) {
            return;
        }

        $this->deleteElements($parent, $addPushStrategyColls, $options);
        $this->insertElements($parent, $addPushStrategyColls, $options);
    }

    /**
     * Sets a list of PersistentCollection instances.
     *
     * This method is intended to be used with the "set" or "setArray"
     * strategies. The "setArray" strategy will ensure that the collections is
     * set as a BSON array, which means the collections elements will be
     * reindexed numerically before storage.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function setCollections(object $parent, array $collections, array $options): void
    {
        $pathCollMap = [];
        $paths       = [];
        foreach ($collections as $coll) {
            [$propertyPath]             = $this->getPathAndParent($coll);
            $pathCollMap[$propertyPath] = $coll;
            $paths[]                    = $propertyPath;
        }

        $paths = $this->excludeSubPaths($paths);
        /** @var PersistentCollectionInterface[] $setColls */
        $setColls   = array_intersect_key($pathCollMap, array_flip($paths));
        $setPayload = [];
        foreach ($setColls as $propertyPath => $coll) {
            $coll->initialize();
            $mapping                   = $coll->getMapping();
            $setData                   = $this->pb->prepareAssociatedCollectionValue(
                $coll,
                CollectionHelper::usesSet($mapping['strategy'])
            );
            $setPayload[$propertyPath] = $setData;
        }

        if (empty($setPayload)) {
            return;
        }

        $query = ['$set' => $setPayload];
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Deletes removed elements from a list of PersistentCollection instances.
     *
     * This method is intended to be used with the "pushAll" and "addToSet" strategies.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function deleteElements(object $parent, array $collections, array $options): void
    {
        $pathCollMap   = [];
        $paths         = [];
        $deleteDiffMap = [];

        foreach ($collections as $coll) {
            $coll->initialize();
            if (! $this->uow->isCollectionScheduledForUpdate($coll)) {
                continue;
            }

            $deleteDiff = $coll->getDeleteDiff();

            if (empty($deleteDiff)) {
                continue;
            }

            [$propertyPath] = $this->getPathAndParent($coll);

            $pathCollMap[$propertyPath]   = $coll;
            $paths[]                      = $propertyPath;
            $deleteDiffMap[$propertyPath] = $deleteDiff;
        }

        $paths        = $this->excludeSubPaths($paths);
        $deleteColls  = array_intersect_key($pathCollMap, array_flip($paths));
        $unsetPayload = [];
        $pullPayload  = [];
        foreach ($deleteColls as $propertyPath => $coll) {
            $deleteDiff = $deleteDiffMap[$propertyPath];
            foreach ($deleteDiff as $key => $document) {
                $unsetPayload[$propertyPath . '.' . $key] = true;
            }

            $pullPayload[$propertyPath] = null;
        }

        if (! empty($unsetPayload)) {
            $this->executeQuery($parent, ['$unset' => $unsetPayload], $options);
        }

        if (empty($pullPayload)) {
            return;
        }

        /**
         * @todo This is a hack right now because we don't have a proper way to
         * remove an element from an array by its key. Unsetting the key results
         * in the element being left in the array as null so we have to pull
         * null values.
         */
        $this->executeQuery($parent, ['$pull' => $pullPayload], $options);
    }

    /**
     * Inserts new elements for a PersistentCollection instances.
     *
     * This method is intended to be used with the "pushAll" and "addToSet" strategies.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function insertElements(object $parent, array $collections, array $options): void
    {
        $pushAllPathCollMap  = [];
        $addToSetPathCollMap = [];
        $pushAllPaths        = [];
        $addToSetPaths       = [];
        $diffsMap            = [];

        foreach ($collections as $coll) {
            $coll->initialize();
            if (! $this->uow->isCollectionScheduledForUpdate($coll)) {
                continue;
            }

            $insertDiff = $coll->getInsertDiff();

            if (empty($insertDiff)) {
                continue;
            }

            $mapping  = $coll->getMapping();
            $strategy = $mapping['strategy'];

            [$propertyPath]          = $this->getPathAndParent($coll);
            $diffsMap[$propertyPath] = $insertDiff;

            switch ($strategy) {
                case ClassMetadata::STORAGE_STRATEGY_PUSH_ALL:
                    $pushAllPathCollMap[$propertyPath] = $coll;
                    $pushAllPaths[]                    = $propertyPath;
                    break;

                case ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET:
                    $addToSetPathCollMap[$propertyPath] = $coll;
                    $addToSetPaths[]                    = $propertyPath;
                    break;

                default:
                    throw new LogicException('Invalid strategy ' . $strategy . ' given for insertCollections');
            }
        }

        if (! empty($pushAllPaths)) {
            $this->pushAllCollections(
                $parent,
                $pushAllPaths,
                $pushAllPathCollMap,
                $diffsMap,
                $options
            );
        }

        if (empty($addToSetPaths)) {
            return;
        }

        $this->addToSetCollections(
            $parent,
            $addToSetPaths,
            $addToSetPathCollMap,
            $diffsMap,
            $options
        );
    }

    /**
     * Perform collections update for 'pushAll' strategy.
     *
     * @param object $parent       Parent object to which passed collections is belong.
     * @param array  $collsPaths   Paths of collections that is passed.
     * @param array  $pathCollsMap List of collections indexed by their paths.
     * @param array  $diffsMap     List of collection diffs indexed by collections paths.
     * @param array  $options
     */
    private function pushAllCollections(object $parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options): void
    {
        $pushAllPaths = $this->excludeSubPaths($collsPaths);
        /** @var PersistentCollectionInterface[] $pushAllColls */
        $pushAllColls   = array_intersect_key($pathCollsMap, array_flip($pushAllPaths));
        $pushAllPayload = [];
        foreach ($pushAllColls as $propertyPath => $coll) {
            $callback                      = $this->getValuePrepareCallback($coll);
            $value                         = array_values(array_map($callback, $diffsMap[$propertyPath]));
            $pushAllPayload[$propertyPath] = ['$each' => $value];
        }

        if (! empty($pushAllPayload)) {
            $this->executeQuery($parent, ['$push' => $pushAllPayload], $options);
        }

        $pushAllColls = array_diff_key($pathCollsMap, array_flip($pushAllPaths));
        foreach ($pushAllColls as $propertyPath => $coll) {
            $callback = $this->getValuePrepareCallback($coll);
            $value    = array_values(array_map($callback, $diffsMap[$propertyPath]));
            $query    = ['$push' => [$propertyPath => ['$each' => $value]]];
            $this->executeQuery($parent, $query, $options);
        }
    }

    /**
     * Perform collections update by 'addToSet' strategy.
     *
     * @param object $parent       Parent object to which passed collections is belong.
     * @param array  $collsPaths   Paths of collections that is passed.
     * @param array  $pathCollsMap List of collections indexed by their paths.
     * @param array  $diffsMap     List of collection diffs indexed by collections paths.
     * @param array  $options
     */
    private function addToSetCollections(object $parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options): void
    {
        $addToSetPaths = $this->excludeSubPaths($collsPaths);
        /** @var PersistentCollectionInterface[] $addToSetColls */
        $addToSetColls = array_intersect_key($pathCollsMap, array_flip($addToSetPaths));

        $addToSetPayload = [];
        foreach ($addToSetColls as $propertyPath => $coll) {
            $callback                       = $this->getValuePrepareCallback($coll);
            $value                          = array_values(array_map($callback, $diffsMap[$propertyPath]));
            $addToSetPayload[$propertyPath] = ['$each' => $value];
        }

        if (empty($addToSetPayload)) {
            return;
        }

        $this->executeQuery($parent, ['$addToSet' => $addToSetPayload], $options);
    }

    /**
     * Return callback instance for specified collection. This callback will prepare values for query from documents
     * that collection contain.
     */
    private function getValuePrepareCallback(PersistentCollectionInterface $coll): Closure
    {
        $mapping = $coll->getMapping();
        if (isset($mapping['embedded'])) {
            return function ($v) use ($mapping) {
                return $this->pb->prepareEmbeddedDocumentValue($mapping, $v);
            };
        }

        return function ($v) use ($mapping) {
            return $this->pb->prepareReferencedDocumentValue($mapping, $v);
        };
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
        $fields  = [];
        $parent  = $coll->getOwner();
        while (($association = $this->uow->getParentAssociation($parent)) !== null) {
            [$m, $owner, $field] = $association;
            if (isset($m['reference'])) {
                break;
            }

            $parent   = $owner;
            $fields[] = $field;
        }

        $propertyPath = implode('.', array_reverse($fields));
        $path         = $mapping['name'];
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
        $class     = $this->dm->getClassMetadata($className);
        $id        = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
        $query     = ['_id' => $id];
        if ($class->isVersioned) {
            $query[$class->fieldMappings[$class->versionField]['name']] = $class->reflFields[$class->versionField]->getValue($document);
        }

        $collection = $this->dm->getDocumentCollection($className);
        $result     = $collection->updateOne($query, $newObj, $options);
        if ($class->isVersioned && ! $result->getMatchedCount()) {
            throw LockException::lockFailed($document);
        }
    }

    /**
     * Remove from passed paths list all sub-paths.
     *
     * @param string[] $paths
     *
     * @return string[]
     */
    private function excludeSubPaths(array $paths): array
    {
        if (empty($paths)) {
            return $paths;
        }

        sort($paths);
        $uniquePaths = [$paths[0]];
        for ($i = 1, $count = count($paths); $i < $count; ++$i) {
            $lastUniquePath = end($uniquePaths);
            assert($lastUniquePath !== false);

            if (strpos($paths[$i], $lastUniquePath) === 0) {
                continue;
            }

            $uniquePaths[] = $paths[$i];
        }

        return $uniquePaths;
    }
}
