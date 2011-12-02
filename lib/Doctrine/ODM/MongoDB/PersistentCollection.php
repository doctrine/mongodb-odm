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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection as BaseCollection,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Closure;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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

    private $owner;

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
     * @var Collection
     */
    private $coll;

    /**
     * The DocumentManager that manages the persistence of the collection.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork that manages the persistence of the collection.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

    /**
     * Mongo command prefix
     * @var string
     */
    private $cmd;

    /**
     * The raw mongo data that will be used to initialize this collection.
     *
     * @var array
     */
    private $mongoData = array();

    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow, $cmd)
    {
        $this->coll = $coll;
        $this->dm   = $dm;
        $this->uow  = $uow;
        $this->cmd  = $cmd;
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
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if ( ! $this->initialized && $this->mapping) {
            if ($this->isDirty) {
                // Has NEW objects added through add(). Remember them.
                $newObjects = $this->coll->toArray();
            }
            $this->coll->clear();
            $this->uow->loadCollection($this);
            $this->takeSnapshot();
            // Reattach NEW objects added through add(), if any.
            if (isset($newObjects)) {
                foreach ($newObjects as $key => $obj) {
                    if ($this->mapping['strategy'] === 'set') {
                        $this->coll->set($key, $obj);
                    } else {
                        $this->coll->add($obj);
                    }
                }
                $this->isDirty = true;
            }
            $this->mongoData = array();
            $this->initialized = true;
        }
    }

    /**
     * Marks this collection as changed/dirty.
     */
    private function changed()
    {
        if ( ! $this->isDirty) {
            $this->isDirty = true;
            if ($this->dm && $this->mapping !== null && $this->mapping['isOwningSide'] && $this->dm->getClassMetadata(get_class($this->owner))->isChangeTrackingNotify()) {
                $this->uow->scheduleForDirtyCheck($this->owner);
            }
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
     * @param AssociationMapping $mapping
     */
    public function setOwner($document, array $mapping)
    {
        $this->owner = $document;
        $this->mapping = $mapping;
    }

    /**
     * INTERNAL:
     * Tells this collection to take a snapshot of its current state.
     */
    public function takeSnapshot()
    {
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
        $this->isDirty = $this->count() ? true : false;
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
        return array_udiff_assoc($this->snapshot, $this->coll->toArray(),
                function($a, $b) {return $a === $b ? 0 : 1;});
    }

    /**
     * INTERNAL:
     * getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff_assoc($this->coll->toArray(), $this->snapshot,
                function($a, $b) {return $a === $b ? 0 : 1;});
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

    public function getMapping()
    {
        return $this->mapping;
    }

    public function getTypeClass()
    {
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
        if ($removed) {
            $this->changed();
            if ($this->mapping !== null && isset($this->mapping['embedded'])) {
                $this->uow->scheduleOrphanRemoval($removed);
            }
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        $this->initialize();
        $removed = $this->coll->removeElement($element);
        if ($removed) {
            $this->changed();
            if ($this->mapping !== null && isset($this->mapping['embedded'])) {
                $this->uow->scheduleOrphanRemoval($element);
            }
        }
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
    public function exists(Closure $p)
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
        if ($this->mapping['isInverseSide']) {
            $this->initialize();
        }
        return count($this->mongoData) + $this->coll->count();
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->coll->set($key, $value);
        $this->changed();
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        $this->coll->add($value);
        $this->changed();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->count() === 0 ? true : false;
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
    public function map(Closure $func)
    {
        $this->initialize();
        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        $this->initialize();
        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(Closure $p)
    {
        $this->initialize();
        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(Closure $p)
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
        if ($this->mapping !== null && isset($this->mapping['embedded'])) {
            foreach ($this->coll as $element) {
                $this->uow->scheduleOrphanRemoval($element);
            }
        }
        $this->mongoData = array();
        $this->coll->clear();
        if ($this->mapping['isOwningSide']) {
            $this->changed();
            $this->uow->scheduleCollectionDeletion($this);
            $this->takeSnapshot();
        }
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
}