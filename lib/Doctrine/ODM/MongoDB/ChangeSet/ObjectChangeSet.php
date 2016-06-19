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

/**
 * Class aggregates all changes in an object.
 */
final class ObjectChangeSet implements \IteratorAggregate, \ArrayAccess, \Countable, ChangedValue
{
    /**
     * @var ChangedValue[]
     */
    private $changes = [];

    /**
     * @var object
     */
    private $object;

    /**
     * @param object $object
     * @param ChangedValue[] $changes
     */
    public function __construct($object, array $changes)
    {
        $this->object = $object;
        $this->changes = $changes;
    }

    /**
     * Gets change set for specific field.
     * 
     * @param string $field
     * @return ChangedValue
     * 
     * @throws \InvalidArgumentException when field has not been changed
     */
    public function getChange($field)
    {
        if (empty($this->changes[$field])) {
            throw new \InvalidArgumentException(sprintf('Field "%s" has not been changed.', $field));
        }

        return $this->changes[$field];
    }

    /**
     * Gets all changes.
     * 
     * @return ChangedValue[]
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Gets object for which changes are held.
     * 
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Checks if field's value has been changed.
     * 
     * @param string $field
     * @return bool
     */
    public function hasChanged($field)
    {
        return isset($this->changes[$field]);
    }

    /**
     * Registers change for specific field.
     * 
     * @param string $field
     * @param ChangedValue $change
     */
    public function registerChange($field, ChangedValue $change)
    {
        $this->changes[$field] = $change;
    }
    
    /** ChangedValue implementation */

    /** {@inheritdoc} */
    public function getNewValue()
    {
        return $this->object;
    }

    /** {@inheritdoc} */
    public function getOldValue()
    {
        return $this->object;
    }

    /** IteratorAggregate implementation */

    public function getIterator()
    {
        return new \ArrayIterator($this->changes);
    }

    /** Countable implementation */

    /** {@inheritdoc} */
    public function count()
    {
        return count($this->changes);
    }

    /** ArrayAccess Implementation */

    /** {@inheritdoc} */
    public function offsetExists($offset)
    {
        return $this->hasChanged($offset);
    }

    /** {@inheritdoc} */
    public function offsetGet($offset)
    {
        return $this->getChange($offset);
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value)
    {
        $this->registerChange($offset, $value);
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not allowed.');
    }
}
