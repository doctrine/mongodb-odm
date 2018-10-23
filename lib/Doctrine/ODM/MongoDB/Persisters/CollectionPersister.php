<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

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
 * @since       1.0
 */
class CollectionPersister
{

    /**
     * Validation map that is used for strategy validation in insertCollections method.
     */
    const INSERT_STRATEGIES_MAP = [
        ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL => true,
        ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET => true,
    ];

    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The PersistenceBuilder instance.
     *
     * @var PersistenceBuilder
     */
    private $pb;

    /**
     * Constructs a new CollectionPersister instance.
     *
     * @param DocumentManager $dm
     * @param PersistenceBuilder $pb
     * @param UnitOfWork $uow
     */
    public function __construct(DocumentManager $dm, PersistenceBuilder $pb, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->pb = $pb;
        $this->uow = $uow;
    }

    /**
     * Deletes a PersistentCollection instances completely from a document using $unset. Collections can belong to
     * different parents. Collections that is belong to one parent will be deleted in one query.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array $options
     */
    public function deleteAll(array $collections, array $options)
    {
        $parents = [];
        $unsetPathsMap = [];

        foreach ($collections as $coll) {
            $mapping = $coll->getMapping();
            if ($mapping['isInverseSide']) {
                continue; // ignore inverse side
            }
            if (CollectionHelper::isAtomic($mapping['strategy'])) {
                throw new \UnexpectedValueException($mapping['strategy'] . ' delete collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');
            }
            list($propertyPath, $parent) = $this->getPathAndParent($coll);
            $oid = \spl_object_hash($parent);
            $parents[$oid] = $parent;
            $unsetPathsMap[$oid][$propertyPath] = true;
        }

        foreach ($unsetPathsMap as $oid => $paths) {
            $unsetPaths = array_fill_keys($this->excludeSubPaths(array_keys($paths)), true);
            $query      = ['$unset' => $unsetPaths];
            $this->executeQuery($parents[$oid], $query, $options);
        }
    }

    /**
     * Deletes a PersistentCollection instance completely from a document using $unset.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     */
    public function delete(PersistentCollectionInterface $coll, array $options)
    {
        $mapping = $coll->getMapping();
        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }
        if (CollectionHelper::isAtomic($mapping['strategy'])) {
            throw new \UnexpectedValueException($mapping['strategy'] . ' delete collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');
        }
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        $query = array('$unset' => array($propertyPath => true));
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Updates a PersistentCollection instance deleting removed rows and
     * inserting new rows.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     */
    public function update(PersistentCollectionInterface $coll, array $options)
    {
        $mapping = $coll->getMapping();

        if ($mapping['isInverseSide']) {
            return; // ignore inverse side
        }

        switch ($mapping['strategy']) {
            case ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET:
            case ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET_ARRAY:
                throw new \UnexpectedValueException($mapping['strategy'] . ' update collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');

            case ClassMetadataInfo::STORAGE_STRATEGY_SET:
            case ClassMetadataInfo::STORAGE_STRATEGY_SET_ARRAY:
                $this->setCollection($coll, $options);
                break;

            case ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET:
            case ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL:
                $coll->initialize();
                $this->deleteElements($coll, $options);
                $this->insertElements($coll, $options);
                break;

            default:
                throw new \UnexpectedValueException('Unsupported collection strategy: ' . $mapping['strategy']);
        }
    }

    /**
     * Updates a list PersistentCollection instances deleting removed rows and inserting new rows.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    public function updateAll(array $collections, array $options)
    {

        $setStrategyColls = [];
        $addPushStrategyColls = [];

        foreach ($collections as $coll) {
            $mapping = $coll->getMapping();

            if ($mapping['isInverseSide']) {
                continue; // ignore inverse side
            }
            switch ($mapping['strategy']) {
                case ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET:
                case ClassMetadataInfo::STORAGE_STRATEGY_ATOMIC_SET_ARRAY:
                    throw new \UnexpectedValueException($mapping['strategy'] . ' update collection strategy should have been handled by DocumentPersister. Please report a bug in issue tracker');

                case ClassMetadataInfo::STORAGE_STRATEGY_SET:
                case ClassMetadataInfo::STORAGE_STRATEGY_SET_ARRAY:
                    $setStrategyColls[] = $coll;
                    break;

                case ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET:
                case ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL:
                    $addPushStrategyColls[] = $coll;
                    break;

                default:
                    throw new \UnexpectedValueException('Unsupported collection strategy: ' . $mapping['strategy']);
            }
        }

        if (!empty($setStrategyColls)) {
            $this->setCollections($setStrategyColls, $options);
        }
        if (!empty($addPushStrategyColls)) {
            $this->deleteCollections($addPushStrategyColls, $options);
            $this->insertCollections($addPushStrategyColls, $options); // TODO
        }
    }

    /**
     * Sets a PersistentCollection instance.
     *
     * This method is intended to be used with the "set" or "setArray"
     * strategies. The "setArray" strategy will ensure that the collection is
     * set as a BSON array, which means the collection elements will be
     * reindexed numerically before storage.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     */
    private function setCollection(PersistentCollectionInterface $coll, array $options)
    {
        list($propertyPath, $parent) = $this->getPathAndParent($coll);
        $coll->initialize();
        $mapping = $coll->getMapping();
        $setData = $this->pb->prepareAssociatedCollectionValue($coll, CollectionHelper::usesSet($mapping['strategy']));
        $query = array('$set' => array($propertyPath => $setData));
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Sets a list of PersistentCollection instances.
     *
     * This method is intended to be used with the "set" or "setArray"
     * strategies. The "setArray" strategy will ensure that the collection is
     * set as a BSON array, which means the collection elements will be
     * reindexed numerically before storage.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function setCollections(array $collections, array $options)
    {
        $parents = [];
        $pathCollMap = [];
        $pathsMap = [];
        foreach ($collections as $coll) {
            list($propertyPath, $parent) = $this->getPathAndParent($coll);
            $oid = \spl_object_hash($parent);
            $parents[$oid] = $parent;
            $pathCollMap[$oid][$propertyPath] = $coll;
            $pathsMap[$oid][] = $propertyPath;
        }

        foreach ($pathsMap as $oid => $paths) {
            $paths = $this->excludeSubPaths($paths);
            /** @var PersistentCollectionInterface[] $setColls */
            $setColls = \array_intersect_key($pathCollMap[$oid], \array_flip($paths));
            $setPayload = [];
            foreach ($setColls as $propertyPath => $coll) {
                $coll->initialize();
                $mapping = $coll->getMapping();
                $setData = $this->pb->prepareAssociatedCollectionValue($coll,
                    CollectionHelper::usesSet($mapping['strategy']));
                $setPayload[$propertyPath] = $setData;
            }
            if (!empty($setPayload)) {
                $query = ['$set' => $setPayload];
                $this->executeQuery($parents[$oid], $query, $options);
            }
        }
    }

    /**
     * Deletes removed elements from a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     */
    private function deleteElements(PersistentCollectionInterface $coll, array $options)
    {
        $deleteDiff = $coll->getDeleteDiff();

        if (empty($deleteDiff)) {
            return;
        }

        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $query = array('$unset' => array());

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
        $this->executeQuery($parent, array('$pull' => array($propertyPath => null)), $options);
    }

    /**
     * Deletes removed elements from a list of PersistentCollection instances.
     *
     * This method is intended to be used with the "pushAll" and "addToSet" strategies.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function deleteCollections(array $collections, array $options)
    {
        $parents = [];
        $pathCollMap = [];
        $pathsMap = [];
        $deleteDiffMap = [];

        foreach ($collections as $coll) {
            $coll->initialize();
            if (!$this->uow->isCollectionScheduledForUpdate($coll)) {
                continue;
            }
            $deleteDiff = $coll->getDeleteDiff();

            if (empty($deleteDiff)) {
                continue;
            }
            list($propertyPath, $parent) = $this->getPathAndParent($coll);

            $oid = \spl_object_hash($parent);
            $parents[$oid] = $parent;
            $pathCollMap[$oid][$propertyPath] = $coll;
            $pathsMap[$oid][] = $propertyPath;
            $deleteDiffMap[$oid][$propertyPath] = $deleteDiff;
        }

        foreach ($pathsMap as $oid => $paths) {
            $paths = $this->excludeSubPaths($paths);
            $deleteColls = \array_intersect_key($pathCollMap[$oid], \array_flip($paths));
            $unsetPayload = [];
            $pullPayload = [];
            foreach ($deleteColls as $propertyPath => $coll) {
                $deleteDiff = $deleteDiffMap[$oid][$propertyPath];
                foreach ($deleteDiff as $key => $document) {
                    $unsetPayload[$propertyPath . '.' . $key] = true;
                }
                $pullPayload[$propertyPath] = null;
            }

            if (!empty($unsetPayload)) {
                $this->executeQuery($parents[$oid], ['$unset' => $unsetPayload], $options);
            }
            if (!empty($pullPayload)) {
                /**
                 * @todo This is a hack right now because we don't have a proper way to
                 * remove an element from an array by its key. Unsetting the key results
                 * in the element being left in the array as null so we have to pull
                 * null values.
                 */
                $this->executeQuery($parents[$oid], ['$pull' => $pullPayload], $options);
            }

        }
    }

    /**
     * Inserts new elements for a PersistentCollection instance.
     *
     * This method is intended to be used with the "pushAll" and "addToSet"
     * strategies.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     */
    private function insertElements(PersistentCollectionInterface $coll, array $options)
    {
        $insertDiff = $coll->getInsertDiff();

        if (empty($insertDiff)) {
            return;
        }

        $mapping = $coll->getMapping();

        switch ($mapping['strategy']) {
            case ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL:
                $operator = 'push';
                break;

            case ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET:
                $operator = 'addToSet';
                break;

            default:
                throw new \LogicException("Invalid strategy {$mapping['strategy']} given for insertElements");
        }

        list($propertyPath, $parent) = $this->getPathAndParent($coll);

        $callback = isset($mapping['embedded'])
            ? function($v) use ($mapping) { return $this->pb->prepareEmbeddedDocumentValue($mapping, $v); }
            : function($v) use ($mapping) { return $this->pb->prepareReferencedDocumentValue($mapping, $v); };

        $value = array_values(array_map($callback, $insertDiff));

        $query = ['$' . $operator => [$propertyPath => ['$each' => $value]]];

        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Inserts new elements for a list of PersistentCollection instances.
     *
     * This method is intended to be used with the "pushAll" and "addToSet" strategies.
     *
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function insertCollections(array $collections, array $options)
    {
        $parents = [];
        $pushAllPathCollMap = [];
        $addToSetPathCollMap = [];
        $pushAllPathsMap = [];
        $addToSetPathsMap = [];
        $diffsMap = [];

        foreach ($collections as $coll) {
            $coll->initialize();
            if (!$this->uow->isCollectionScheduledForUpdate($coll)) {
                continue;
            }
            $insertDiff = $coll->getInsertDiff();

            if (empty($insertDiff)) {
                continue;
            }

            $mapping = $coll->getMapping();
            $strategy = $mapping['strategy'];

            if (empty(self::INSERT_STRATEGIES_MAP[$strategy])) {
                throw new \LogicException("Invalid strategy {$strategy} given for insertCollections");
            }

            list($propertyPath, $parent) = $this->getPathAndParent($coll);
            $oid = \spl_object_hash($parent);
            $parents[$oid] = $parent;
            $diffsMap[$oid][$propertyPath] = $insertDiff;

            switch ($strategy) {
                case ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL:
                    $pushAllPathCollMap[$oid][$propertyPath] = $coll;
                    $pushAllPathsMap[$oid][] = $propertyPath;
                    break;

                case ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET:
                    $addToSetPathCollMap[$oid][$propertyPath] = $coll;
                    $addToSetPathsMap[$oid][] = $propertyPath;
                    break;

                default:
                    throw new \LogicException("Invalid strategy {$strategy} given for insertCollections");
            }
        }

        foreach ($parents as $oid => $parent) {
            if(!empty($pushAllPathsMap[$oid])) {
                $this->pushAllCollections(
                    $parent,
                    $pushAllPathsMap[$oid],
                    $pushAllPathCollMap[$oid],
                    $diffsMap[$oid],
                    $options
                );
            }
            if(!empty($addToSetPathsMap[$oid])) {
                $this->addToSetCollections(
                    $parent,
                    $addToSetPathsMap[$oid],
                    $addToSetPathCollMap[$oid],
                    $diffsMap[$oid],
                    $options
                );
            }
        }
    }

    /**
     * Perform collections update for 'pushAll' strategy.
     *
     * @param object $parent Parent object to which passed collections is belong.
     * @param array  $collsPaths Paths of collections that is passed.
     * @param array  $pathCollsMap List of collections indexed by their paths.
     * @param array  $diffsMap List of collection diffs indexed by collections paths.
     * @param array  $options
     */
    private function pushAllCollections($parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options)
    {
        $pushAllPaths = $this->excludeSubPaths($collsPaths);
        /** @var PersistentCollectionInterface[] $pushAllColls */
        $pushAllColls = \array_intersect_key($pathCollsMap, \array_flip($pushAllPaths));
        $pushAllPayload = [];
        foreach ($pushAllColls as $propertyPath => $coll) {
            $callback = $this->getValuePrepareCallback($coll);
            $value = \array_values(\array_map($callback, $diffsMap[$propertyPath]));
            $pushAllPayload[$propertyPath] = ['$each' => $value];
        }

        if (!empty($pushAllPayload)) {
            $this->executeQuery($parent, ['$push' => $pushAllPayload], $options);
        }

        $pushAllColls = \array_diff_key($pathCollsMap, \array_flip($pushAllPaths));
        foreach ($pushAllColls as $propertyPath => $coll) {
            $callback = $this->getValuePrepareCallback($coll);
            $value = \array_values(\array_map($callback, $diffsMap[$propertyPath]));
            $query = ['$push' => [$propertyPath => ['$each' => $value]]];
            $this->executeQuery($parent, $query, $options);
        }
    }

    /**
     * Perform collections update by 'addToSet' strategy.
     *
     * @param object $parent Parent object to which passed collections is belong.
     * @param array  $collsPaths Paths of collections that is passed.
     * @param array  $pathCollsMap List of collections indexed by their paths.
     * @param array  $diffsMap List of collection diffs indexed by collections paths.
     * @param array  $options
     */
    private function addToSetCollections($parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options)
    {
        $addToSetPaths = $this->excludeSubPaths($collsPaths);
        /** @var PersistentCollectionInterface[] $addToSetColls */
        $addToSetColls = \array_intersect_key($pathCollsMap, \array_flip($addToSetPaths));

        $addToSetPayload = [];
        foreach ($addToSetColls as $propertyPath => $coll) {
            $callback = $this->getValuePrepareCallback($coll);
            $value = \array_values(\array_map($callback, $diffsMap[$propertyPath]));
            $addToSetPayload[$propertyPath] = ['$each' => $value];
        }

        if (!empty($addToSetPayload)) {
            $this->executeQuery($parent, ['$addToSet' => $addToSetPayload], $options);
        }
    }

    /**
     * Return callback instance for specified collection. This callback will prepare values for query from documents
     * that collection contain.
     *
     * @param PersistentCollectionInterface $coll
     *
     * @return \Closure
     */
    private function getValuePrepareCallback(PersistentCollectionInterface $coll)
    {
        $mapping = $coll->getMapping();
        if(isset($mapping['embedded'])) {
            return function ($v) use ($mapping) {
                return $this->pb->prepareEmbeddedDocumentValue($mapping, $v);
            };
        } else {
            return function ($v) use ($mapping) {
                return $this->pb->prepareReferencedDocumentValue($mapping, $v);
            };
        }
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
     *
     * @param PersistentCollectionInterface $coll
     * @return array $pathAndParent
     */
    private function getPathAndParent(PersistentCollectionInterface $coll)
    {
        $mapping = $coll->getMapping();
        $fields = array();
        $parent = $coll->getOwner();
        while (null !== ($association = $this->uow->getParentAssociation($parent))) {
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
        return array($path, $parent);
    }

    /**
     * Executes a query updating the given document.
     *
     * @param object $document
     * @param array $newObj
     * @param array $options
     */
    private function executeQuery($document, array $newObj, array $options)
    {
        $className = get_class($document);
        $class = $this->dm->getClassMetadata($className);
        $id = $class->getDatabaseIdentifierValue($this->uow->getDocumentIdentifier($document));
        $query = array('_id' => $id);
        if ($class->isVersioned) {
            $query[$class->fieldMappings[$class->versionField]['name']] = $class->reflFields[$class->versionField]->getValue($document);
        }
        $collection = $this->dm->getDocumentCollection($className);
        $result = $collection->update($query, $newObj, $options);
        if ($class->isVersioned && ! $result['n']) {
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
    private function excludeSubPaths(array $paths)
    {
        $checkedPaths = [];
        $pathsAmount = \count($paths);
        $paths = \array_unique($paths);
        for ($i = 0; $i < $pathsAmount; $i++) {
            $isSubPath = false;
            $j         = 0;
            for (; $j < $pathsAmount; $j++) {
                if ($i !== $j && \strpos($paths[$i], $paths[$j]) === 0) {
                    $isSubPath = true;
                    break;
                }
            }
            if ($isSubPath) {
                continue;
            }
            $checkedPaths[] = $paths[$i];
        }
        return $checkedPaths;
    }
}
