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

use Doctrine\ODM\MongoDB\Cursor as BaseCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;

/**
 * EagerCursor extends the default Doctrine\MongoDB\EagerCursor implementation.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class EagerCursor implements \Doctrine\MongoDB\Iterator
{
    /**
     * Whether or not to hydrate the data to documents.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     *
     * @var boolean
     */
    private $refresh = false;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var \Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $unitOfWork;

    /**
     * The ClassMetadata instance.
     *
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * The array of hints for the UnitOfWork.
     *
     * @var array
     */
    private $hints = array();

    /**
     * @var object Doctrine\ODM\MongoDB\Cursor
     */
    private $cursor;

    /**
     * Array of data from Cursor to iterate over
     *
     * @var array
     */
    private $data = array();

    /**
     * Whether or not the EagerCursor has been initialized
     *
     * @var bool $initialized
     */
    private $initialized = false;

    /** @override */
    public function __construct(BaseCursor $cursor, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->cursor = $cursor;
        $this->unitOfWork = $uow;
        $this->class = $class;
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
     * @return Cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Get the full set of data and set as initialized (because it's eager)
     */
    public function initialize()
    {
        if ($this->initialized === false) {
            $this->data = $this->cursor->getBaseCursor()->toArray();
        }
        $this->initialized = true;
    }

    /**
     * Rewind the iterator
     *
     * @see http://php.net/class.iterator.php
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->data);
    }

    /**
     * Get the current element
     *
     * @see http://php.net/class.iterator.php
     * @return array|object|null
     */
    public function current()
    {
        $this->initialize();
        $current = current($this->data);
        if ($current && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $current, $this->hints);
        }
        return $current ? $current : null;
    }

    /**
     * Get the key of the current element
     *
     * @see http://php.net/class.iterator.php
     * @return mixed
     */
    public function key()
    {
        $this->initialize();
        return key($this->data);
    }

    /**
     * Advance the pointer of the iterator
     *
     * @see http://php.net/class.iterator.php
     */
    public function next()
    {
        $this->initialize();
        return next($this->data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->initialize();
        $key = key($this->data);
        return ($key !== null && $key !== false);
    }

    /**
     * Get the count of objects
     *
     * @return int
     */
    public function count()
    {
        $this->initialize();
        return count($this->data);
    }

    /**
     * Turn this cursor into an array
     *
     * @return array
     */
    public function toArray()
    {
        $this->initialize();
        return iterator_to_array($this);
    }

    /**
     * Get a result from the cursor
     *
     * @return array|object|null
     */
    public function getSingleResult()
    {
        $this->initialize();
        return $this->current();
    }

    /**
     * Set whether to hydrate the documents to objects or not.
     *
     * @param boolean $bool
     * @return self
     */
    public function hydrate($bool = true)
    {
        $this->hydrate = $bool;
        return $this;
    }

    /**
     * Sets whether to refresh the documents data if it already exists in the identity map.
     *
     * @param bool $bool
     * @return self
     */
    public function refresh($bool = true)
    {
        $this->refresh = $bool;
        if ($this->refresh) {
            $this->hints[Query::HINT_REFRESH] = true;
        } else {
            unset($this->hints[Query::HINT_REFRESH]);
        }
        return $this;
    }

    /** @override */
    public function slaveOkay($okay = true)
    {
        if ($okay) {
            $this->hints[Query::HINT_SLAVE_OKAY] = true;
        } else {
            unset($this->hints[Query::HINT_SLAVE_OKAY]);
        }
        $this->cursor->slaveOkay($okay);
        return $this;
    }
}
