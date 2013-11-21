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

use Doctrine\MongoDB\EagerCursor as BaseEagerCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;

/**
 * EagerCursor wraps a Cursor instance and fetches all of its results upon
 * initialization.
 *
 * @since  1.0
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class EagerCursor extends BaseEagerCursor
{
    /**
     * The ClassMetadata instance for the document class being queried.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Whether to hydrate results as document class instances.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * The UnitOfWork instance used for result hydration and preparing arguments
     * for {@link Cursor::sort()}.
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * Hints for UnitOfWork behavior.
     *
     * @var array
     */
    private $unitOfWorkHints = array();

    /**
     * Constructor.
     *
     * @param Cursor        $cursor      Cursor instance being wrapped
     * @param UnitOfWork    $unitOfWork  UnitOfWork for result hydration and query preparation
     * @param ClassMetadata $class       ClassMetadata for the document class being queried
     */
    public function __construct(Cursor $cursor, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->cursor = $cursor;
        $this->unitOfWork = $uow;
        $this->class = $class;
    }

    /**
     * @see \Doctrine\MongoDB\EagerCursor::current()
     * @see http://php.net/manual/en/iterator.current.php
     */
    public function current()
    {
        $current = parent::current();

        if ($current === null || ! $this->hydrate) {
            return $current;
        }

        return $this->unitOfWork->getOrCreateDocument($this->class->name, $current, $this->unitOfWorkHints);
    }

    /**
     * Get hints for UnitOfWork behavior.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->unitOfWorkHints;
    }

    /**
     * Set hints for UnitOfWork behavior.
     *
     * @param array $hints
     */
    public function setHints(array $hints)
    {
        $this->unitOfWorkHints = $hints;
    }

    /**
     * Set whether to hydrate results as document class instances.
     *
     * @see Cursor::hydrate()
     * @param boolean $hydrate
     * @return self
     */
    public function hydrate($hydrate = true)
    {
        $this->hydrate = (boolean) $hydrate;
        return $this;
    }

    /**
     * Initialize the internal data by converting the Cursor to an array.
     */
    public function initialize()
    {
        if ($this->initialized === false) {
            $this->data = $this->cursor->getBaseCursor()->toArray();
        }
        $this->initialized = true;
    }

    /**
     * Set whether to refresh hydrated documents that are already in the
     * identity map.
     *
     * This option has no effect if hydration is disabled.
     *
     * @see Cursor::refresh()
     * @param boolean $refresh
     * @return self
     */
    public function refresh($refresh = true)
    {
        $this->unitOfWorkHints[Query::HINT_REFRESH] = (boolean) $refresh;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::setReadPreference().
     *
     * @see Cursor::setReadPreference()
     * @param string $readPreference
     * @param array  $tags
     * @return self
     */
    public function setReadPreference($readPreference, array $tags = null)
    {
        $this->cursor->setReadPreference($readPreference, $tags);
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE] = $readPreference;
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE_TAGS] = $tags;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::slaveOkay().
     *
     * @see Cursor::slaveOkay()
     * @param boolean $ok
     * @return self
     */
    public function slaveOkay($ok = true)
    {
        $ok = (boolean) $ok;
        $this->cursor->slaveOkay($ok);
        $this->unitOfWorkHints[Query::HINT_SLAVE_OKAY] = $ok;
        return $this;
    }

    /**
     * @see \Doctrine\MongoDB\EagerCursor::toArray()
     * @see \Doctrine\MongoDB\Iterator::toArray()
     */
    public function toArray()
    {
        $this->initialize();
        return iterator_to_array($this);
    }
}
