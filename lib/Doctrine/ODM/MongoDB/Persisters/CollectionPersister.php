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

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use UnexpectedValueException;
use const E_USER_DEPRECATED;
use function array_diff_key;
use function array_fill_keys;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_values;
use function count;
use function end;
use function get_class;
use function implode;
use function sort;
use function sprintf;
use function strpos;
use function trigger_error;

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
 * @final
 * @since       1.0
 */
class CollectionPersister
{
    /** @var DocumentManager */
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
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in doctrine/mongodb-odm 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        $this->dm = $dm;
        $this->pb = $pb;
        $this->uow = $uow;
    }

    /**
     * Deletes a PersistentCollection instances completely from a document using $unset.
     *
     * @param object                          $parent
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    public function deleteAll($parent, array $collections, array $options)
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
            list($propertyPath)           = $this->getPathAndParent($collection);
            $unsetPathsMap[$propertyPath] = true;
        }

        if (empty($unsetPathsMap)) {
            return;
        }

        $unsetPaths = array_fill_keys($this->excludeSubPaths(array_keys($unsetPathsMap)), true);
        $query      = ['$unset' => $unsetPaths];
        $this->executeQuery($parent, $query, $options);
    }

    /**
     * Deletes a PersistentCollection instance completely from a document using $unset.
     *
     * @param PersistentCollectionInterface $coll
     * @param array $options
     *
     * @deprecated This method will be replaced with the deleteAll method
     */
    public function delete(PersistentCollectionInterface $coll, array $options)
    {
        @trigger_error(sprintf('The "%s" method is deprecated and will be changed to the signature of deleteAll in doctrine/mongodb-odm 2.0.', __METHOD__), E_USER_DEPRECATED);

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
     *
     * @deprecated This method will be replaced with the updateAll method
     */
    public function update(PersistentCollectionInterface $coll, array $options)
    {
        @trigger_error(sprintf('The "%s" method is deprecated and will be changed to the signature of updateAll in doctrine/mongodb-odm 2.0.', __METHOD__), E_USER_DEPRECATED);

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
     * Updates a list PersistentCollection instances deleting removed rows and inserting new rows.
     *
     * @param object                          $parent
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    public function updateAll($parent, array $collections, array $options)
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

        $this->deleteCollections($parent, $addPushStrategyColls, $options);
        $this->insertCollections($parent, $addPushStrategyColls, $options);
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
     * @param object                          $parent
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function setCollections($parent, array $collections, array $options)
    {
        $pathCollMap = [];
        $paths       = [];
        foreach ($collections as $coll) {
            list($propertyPath)         = $this->getPathAndParent($coll);
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
     * @param object                          $parent
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function deleteCollections($parent, array $collections, array $options)
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
            list($propertyPath) = $this->getPathAndParent($coll);

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
            case ClassMetadata::STORAGE_STRATEGY_PUSH_ALL:
                $operator = 'push';
                break;

            case ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET:
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
     * @param object                          $parent
     * @param PersistentCollectionInterface[] $collections
     * @param array                           $options
     */
    private function insertCollections($parent, array $collections, array $options)
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

            list($propertyPath)      = $this->getPathAndParent($coll);
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
    private function pushAllCollections($parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options)
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
    private function addToSetCollections($parent, array $collsPaths, array $pathCollsMap, array $diffsMap, array $options)
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
     *
     * @return Closure
     */
    private function getValuePrepareCallback(PersistentCollectionInterface $coll)
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
        if (empty($paths)) {
            return $paths;
        }
        sort($paths);
        $uniquePaths = [$paths[0]];
        for ($i = 1, $count = count($paths); $i < $count; ++$i) {
            if (strpos($paths[$i], end($uniquePaths)) === 0) {
                continue;
            }

            $uniquePaths[] = $paths[$i];
        }

        return $uniquePaths;
    }
}
