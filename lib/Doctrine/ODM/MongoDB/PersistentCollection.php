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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class PersistentCollection implements BaseCollection
{
    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array
     */
    private $snapshot = array();

    /**
     * Collection's owning entity
     *
     * @var object
     */
    private $owner;

    /**
     * @var array
     */
    private $mapping;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    private $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     *
     * @var boolean
     */
    private $initialized = true;

    /**
     * The wrapped Collection instance.
     *
     * @var BaseCollection
     */
    private $coll;

    /**
     * The DocumentManager that manages the persistence of the collection.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork that manages the persistence of the collection.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * The raw mongo data that will be used to initialize this collection.
     *
     * @var array
     */
    private $mongoData = array();

    /**
     * Any hints to account for during reconstitution/lookup of the documents.
     *
     * @var array
     */
    private $hints = array();

    /**
     * @var ClassMetadata
     */
    private $typeClass;

    /**
     * @param BaseCollection $coll
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     */
    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow)
    {
        $this->coll = $coll;
        $this->dm = $dm;
        $this->uow = $uow;
    }

    /**
     * Sets the document manager and unit of work (used during merge operations).
     *
     * @param DocumentManager $dm
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->uow = $dm->getUnitOfWork();
    }

    /**
     * Sets the array of raw mongo data that will be used to initialize this collection.
     *
     * @param array $mongoData
     */
    public function setMongoData(array $mongoData)
    {
        $this->mongoData = $mongoData;
    }

    /**
     * Gets the array of raw mongo data that will be used to initialize this collection.
     *
     * @return array $mongoData
     */
    public function getMongoData()
    {
        return $this->mongoData;
    }

    /**
     * Set hints to account for during reconstitution/lookup of the documents.
     *
     * @param array $hints
     */
    public function setHints(array $hints)
    {
        $this->hints = $hints;
    }

    /**
     * Get hints to account for during reconstitution/lookup of the documents.
     *
     * @return array $hints
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if ($this->initialized || ! $this->mapping) {
            return;
        }

        $newObjects = array();

        if ($this->isDirty) {
            // Remember any NEW objects added through add()
            $newObjects = $this->coll->toArray();
        }

        $this->initialized = true;

        $this->coll->clear();
        $this->uow->loadCollection($this);
        $this->takeSnapshot();

        $this->mongoData = array();

        // Reattach any NEW objects added through add()
        if ($newObjects) {
            foreach ($newObjects as $key => $obj) {
                if (CollectionHelper::isHash($this->mapping['strategy'])) {
                    $this->coll->set($key, $obj);
                } else {
                    $this->coll->add($obj);
                }
            }

            $this->isDirty = true;
        }
    }

    /**
     * Marks this collection as changed/dirty.
     */
    private function changed()
    {
        if ($this->isDirty) {
            return;
        }

        $this->isDirty = true;

        if ($this->dm &&
            $this->mapping !== null &&
            $this->mapping['isOwningSide'] &&
            $this->owner &&
            $this->dm->getClassMetadata(get_class($this->owner))->isChangeTrackingNotify()) {
            $this->uow->scheduleForDirtyCheck($this->owner);
        }
    }

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return boolean TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty()
    {
        return $this->isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param boolean $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty)
    {
        $this->isDirty = $dirty;
    }

    /**
     * INTERNAL:
     * Sets the collection's owning entity together with the AssociationMapping that
     * describes the association between the owner and the elements of the collection.
     *
     * @param object $document
     * @param array $mapping
     */
    public function setOwner($document, array $mapping)
    {
        $this->owner = $document;
        $this->mapping = $mapping;

        if ( ! empty($this->mapping['targetDocument'])) {
            $this->typeClass = $this->dm->getClassMetadata($this->mapping['targetDocument']);
        }
    }

    /**
     * INTERNAL:
     * Tells this collection to take a snapshot of its current state reindexing
     * itself numerically if using save strategy that is enforcing BSON array.
     * Reindexing is safe as snapshot is taken only after synchronizing collection
     * with database or clearing it.
     */
    public function takeSnapshot()
    {
        if (CollectionHelper::isList($this->mapping['strategy'])) {
            $array = $this->coll->toArray();
            $this->coll->clear();
            foreach ($array as $document) {
                $this->coll->add($document);
            }
        }
        $this->snapshot = $this->coll->toArray();
        $this->isDirty = false;
    }

    /**
     * INTERNAL:
     * Clears the internal snapshot information and sets isDirty to true if the collection
     * has elements.
     */
    public function clearSnapshot()
    {
        $this->snapshot = array();
        $this->isDirty = $this->coll->count() ? true : false;
    }

    /**
     * INTERNAL:
     * Returns the last snapshot of the elements in the collection.
     *
     * @return array The last snapshot of the elements.
     */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /**
     * INTERNAL:
     * getDeleteDiff
     *
     * @return array
     */
    public function getDeleteDiff()
    {
        return array_udiff_assoc(
            $this->snapshot,
            $this->coll->toArray(),
            function ($a, $b) { return $a === $b ? 0 : 1; }
        );
    }

    /**
     * INTERNAL: get objects that were removed, unlike getDeleteDiff this doesn't care about indices.
     *
     * @return array
     */
    public function getDeletedDocuments()
    {
        $compare = function ($a, $b) {
            $compareA = is_object($a) ? spl_object_hash($a) : $a;
            $compareb = is_object($b) ? spl_object_hash($b) : $b;
            return $compareA === $compareb ? 0 : ($compareA > $compareb ? 1 : -1);
        };

        return array_udiff(
            $this->snapshot,
            $this->coll->toArray(),
            $compare
        );
    }

    /**
     * INTERNAL:
     * getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff_assoc(
            $this->coll->toArray(),
            $this->snapshot,
            function ($a, $b) { return $a === $b ? 0 : 1; }
        );
    }

    /**
     * INTERNAL:
     * Gets the collection owner.
     *
     * @return object
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return ClassMetadata
     * @throws MongoDBException
     */
    public function getTypeClass()
    {
        if (empty($this->typeClass)) {
            throw new MongoDBException('Specifying targetDocument is required for the ClassMetadata to be obtained.');
        }

        return $this->typeClass;
    }

    /**
     * Sets the initialized flag of the collection, forcing it into that state.
     *
     * @param boolean $bool
     */
    public function setInitialized($bool)
    {
        $this->initialized = $bool;
    }

    /**
     * Checks whether this collection has been initialized.
     *
     * @return boolean
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /** {@inheritdoc} */
    public function first()
    {
        $this->initialize();
        return $this->coll->first();
    }

    /** {@inheritdoc} */
    public function last()
    {
        $this->initialize();
        return $this->coll->last();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->initialize();
        $removed = $this->coll->remove($key);

        if ( ! $removed) {
            return $removed;
        }

        $this->changed();

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        $this->initialize();
        $removed = $this->coll->removeElement($element);

        if ( ! $removed) {
            return $removed;
        }

        $this->changed();

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->initialize();
        return $this->coll->containsKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        $this->initialize();
        return $this->coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(\Closure $p)
    {
        $this->initialize();
        return $this->coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        $this->initialize();
        return $this->coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->initialize();
        return $this->coll->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        $this->initialize();
        return $this->coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $this->initialize();
        return $this->coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = $this->coll->count();

        // If this collection is inversed and not initialized, add the count returned from the database
        if ($this->mapping['isInverseSide'] && ! $this->initialized) {
            $documentPersister = $this->uow->getDocumentPersister(get_class($this->owner));
            $count += empty($this->mapping['repositoryMethod'])
                ? $documentPersister->createReferenceManyInverseSideQuery($this)->count()
                : $documentPersister->createReferenceManyWithRepositoryMethodCursor($this)->count();
        }

        return count($this->mongoData) + $count;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->coll->set($key, $value);

        // Handle orphanRemoval
        if ($this->uow !== null && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        $this->changed();
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        /* Initialize the collection before calling add() so this append operation
         * uses the appropriate key. Otherwise, we risk overwriting original data
         * when $newObjects are re-added in a later call to initialize().
         */
        if (isset($this->mapping['strategy']) && CollectionHelper::isHash($this->mapping['strategy'])) {
            $this->initialize();
        }
        $this->coll->add($value);
        $this->changed();

        if ($this->uow !== null && $this->isOrphanRemovalEnabled() && $value !== null) {
            $this->uow->unscheduleOrphanRemoval($value);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->initialize();
        return $this->coll->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $func)
    {
        $this->initialize();
        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $p)
    {
        $this->initialize();
        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(\Closure $p)
    {
        $this->initialize();
        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $p)
    {
        $this->initialize();
        return $this->coll->partition($p);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->initialize();
        return $this->coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->initialized && $this->isEmpty()) {
            return;
        }

        if ($this->isOrphanRemovalEnabled()) {
            foreach ($this->coll as $element) {
                $this->uow->scheduleOrphanRemoval($element);
            }
        }

        $this->mongoData = array();
        $this->coll->clear();

        // Nothing to do for inverse-side collections
        if ( ! $this->mapping['isOwningSide']) {
            return;
        }

        // Nothing to do if the collection was initialized but contained no data
        if ($this->initialized && empty($this->snapshot)) {
            return;
        }

        $this->changed();
        $this->uow->scheduleCollectionDeletion($this);
        $this->takeSnapshot();
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        $this->initialize();
        return $this->coll->slice($offset, $length);
    }

    /**
     * Called by PHP when this collection is serialized. Ensures that only the
     * elements are properly serialized.
     *
     * @internal Tried to implement Serializable first but that did not work well
     *           with circular references. This solution seems simpler and works well.
     */
    public function __sleep()
    {
        return array('coll', 'initialized');
    }

    /* ArrayAccess implementation */

    /**
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * @see get()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }

        return $this->set($offset, $value);
    }

    /**
     * @see remove()
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    public function key()
    {
        return $this->coll->key();
    }

    /**
     * Gets the element of the collection at the current iterator position.
     */
    public function current()
    {
        return $this->coll->current();
    }

    /**
     * Moves the internal iterator position to the next element.
     */
    public function next()
    {
        return $this->coll->next();
    }

    /**
     * Retrieves the wrapped Collection instance.
     */
    public function unwrap()
    {
        return $this->coll;
    }

    /**
     * Cleanup internal state of cloned persistent collection.
     *
     * The following problems have to be prevented:
     * 1. Added documents are added to old PersistentCollection
     * 2. New collection is not dirty, if reused on other document nothing
     * changes.
     * 3. Snapshot leads to invalid diffs being generated.
     * 4. Lazy loading grabs entities from old owner object.
     * 5. New collection is connected to old owner and leads to duplicate keys.
     */
    public function __clone()
    {
        if (is_object($this->coll)) {
            $this->coll = clone $this->coll;
        }

        $this->initialize();

        $this->owner = null;
        $this->snapshot = array();

        $this->changed();
    }

    /**
     * Returns whether or not this collection has orphan removal enabled.
     *
     * Embedded documents are automatically considered as "orphan removal enabled" because they might have references
     * that require to trigger cascade remove operations.
     *
     * @return boolean
     */
    private function isOrphanRemovalEnabled()
    {
        if ($this->mapping === null) {
            return false;
        }

        if (isset($this->mapping['embedded'])) {
            return true;
        }

        if (isset($this->mapping['reference']) && $this->mapping['isOwningSide'] && $this->mapping['orphanRemoval']) {
            return true;
        }

        return false;
    }
}
