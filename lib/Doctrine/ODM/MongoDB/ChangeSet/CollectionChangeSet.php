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

final class CollectionChangeSet implements \ArrayAccess
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
        $this->changes = $changes;
    }
    
    public function getChangedObjects()
    {
        throw new \BadMethodCallException('Not implemented yet.');
        // 1. if object at index 1 was replaced then it'll be here as well as in getInsertedObjects
        // 2. if change set represents references then this returns empty array
        return $this->changes;
    }

    public function getDeletedObjects()
    {
        return $this->collection->getDeletedDocuments();
    }

    public function getInsertedObjects()
    {
        return $this->collection->getInsertedDocuments();
    }

    public function offsetExists($offset)
    {
        return in_array($offset, [0, 1]);
    }

    public function offsetGet($offset)
    {
        if (! $this->offsetExists($offset)) {
            throw new \OutOfBoundsException();
        }
        return $this->collection;
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not allowed.');
    }
}
