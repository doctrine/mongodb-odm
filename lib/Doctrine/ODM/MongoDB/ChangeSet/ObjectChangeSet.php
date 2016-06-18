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

final class ObjectChangeSet implements \IteratorAggregate, \ArrayAccess, \Countable, ChangedValue
{
    private $changes = [];

    private $object;

    public function __construct($object, array $changes)
    {
        $this->object = $object;
        $this->changes = $changes;
    }

    public function getChange($field)
    {
        if (empty($this->changes[$field])) {
            throw new \InvalidArgumentException(sprintf('Field "%s" has not been changed.', $field));
        }

        return $this->changes[$field];
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getNewValue()
    {
        return $this->object;
    }

    public function getOldValue()
    {
        return $this->object;
    }

    public function hasChanged($field)
    {
        return isset($this->changes[$field]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->changes);
    }

    public function registerChange($field, ChangedValue $change)
    {
        $this->changes[$field] = $change;
    }

    public function offsetExists($offset)
    {
        return $this->hasChanged($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getChange($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->registerChange($offset, $value);
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not allowed.');
    }

    public function count()
    {
        return count($this->changes);
    }
}
