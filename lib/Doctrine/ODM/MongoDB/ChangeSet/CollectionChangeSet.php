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

namespace Doctrine\ODM\MongoDB\ChangeSet;

use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;

/**
 * Class represents changes in a collection (i.e. EmbedMany or ReferenceMany).
 *
 * CollectionChangeSet::getOldValue and CollectionChangeSet::getNewValue will return same instance of collection
 * since only its value was changed.
 */
final class CollectionChangeSet implements ChangedValue
{
    /**
     * @var PersistentCollectionInterface
     */
    private $collection;

    /**
     * @var array
     */
    private $changes;

    /**
     * @param PersistentCollectionInterface $collection
     * @param ObjectChangeSet[] $changes
     */
    public function __construct(PersistentCollectionInterface $collection, array $changes)
    {
        $this->collection = $collection;
        foreach ($changes as $change) {
            $this->registerChange($change);
        }
    }

    /**
     * Gets change sets for updated objects in the collection. This does not include deleted objects but in certain
     * cases may include inserted ones.
     *
     * Returns empty array if collection is holding references (i.e. is used to hold ReferenceMany field's data).
     *
     * @return ObjectChangeSet[]
     */
    public function getChangedObjects()
    {
        // 1. if object at index 1 was replaced then it'll be here as well as in getInsertedObjects (but not yet)
        // 2. if change set represents references then this returns empty array
        return array_values($this->changes);
    }

    /**
     * Gets list of objects removed from collection.
     *
     * @return object[]
     */
    public function getDeletedObjects()
    {
        return $this->collection->getDeletedDocuments();
    }

    /**
     * Gets list of objects added to collection.
     *
     * @return object[]
     */
    public function getInsertedObjects()
    {
        return $this->collection->getInsertedDocuments();
    }

    /**
     * Registers change of one of collection's elements.
     *
     * @param ObjectChangeSet $changeSet
     */
    public function registerChange(ObjectChangeSet $changeSet)
    {
        $this->changes[spl_object_hash($changeSet->getObject())] = $changeSet;
    }

    /** {@inheritdoc} */
    public function getNewValue()
    {
        return $this->collection;
    }

    /** {@inheritdoc} */
    public function getOldValue()
    {
        return $this->collection;
    }
}
