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

use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Connection;
use Doctrine\MongoDB\Cursor as BaseCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;

/**
 * Wrapper for the Doctrine\MongoDB\Cursor class.
 *
 * This class composes a Doctrine\MongoDB\Cursor instance and wraps its methods
 * in order to return results as hydrated document class instances. Hydration
 * behavior may be controlled with the {@link Cursor::hydrate()} method.
 *
 * For compatibility, this class also extends Doctrine\MongoDB\Cursor.
 *
 * @since  1.0
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jeremy Mikola <jmikola@gmail.com>
 */
class Cursor extends BaseCursor
{
    /**
     * The Doctrine\MongoDB\Cursor instance being wrapped.
     *
     * @var BaseCursor
     */
    private $baseCursor;

    /**
     * The ClassMetadata instance for the document class being queried.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Whether or not to hydrate results as document class instances.
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
     * @param BaseCursor    $baseCursor  Doctrine\MongoDB\Cursor instance being wrapped
     * @param UnitOfWork    $unitOfWork  UnitOfWork for result hydration and query preparation
     * @param ClassMetadata $class       ClassMetadata for the document class being queried
     */
    public function __construct(BaseCursor $baseCursor, UnitOfWork $unitOfWork, ClassMetadata $class)
    {
        parent::__construct($baseCursor->collection, $baseCursor->getMongoCursor(), $baseCursor->query, $baseCursor->fields, $baseCursor->numRetries);
        $this->baseCursor = $baseCursor;
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
    }

    /**
     * Return the wrapped Doctrine\MongoDB\Cursor instance.
     *
     * @return BaseCursor
     */
    public function getBaseCursor()
    {
        return $this->baseCursor;
    }

    /**
     * Return the database connection for this cursor.
     *
     * @see \Doctrine\MongoDB\Cursor::getConnection()
     * @return Connection
     */
    public function getConnection()
    {
        return $this->baseCursor->getConnection();
    }

    /**
     * Return the collection for this cursor.
     *
     * @see \Doctrine\MongoDB\Cursor::getCollection()
     * @return Collection
     */
    public function getCollection()
    {
        return $this->baseCursor->getCollection();
    }

    /**
     * Return the selected fields (projection).
     *
     * @see \Doctrine\MongoDB\Cursor::getFields()
     * @return array
     */
    public function getFields()
    {
        return $this->baseCursor->getFields();
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
     * Return the query criteria.
     *
     * @see \Doctrine\MongoDB\Cursor::getQuery()
     * @return array
     */
    public function getQuery()
    {
        return $this->baseCursor->getQuery();
    }

    /**
     * Recreates the internal MongoCursor.
     *
     * @see \Doctrine\MongoDB\Cursor::recreate()
     */
    public function recreate()
    {
        $this->baseCursor->recreate();
        $this->mongoCursor = $this->baseCursor->getMongoCursor();
    }

    /**
     * Wrapper method for MongoCursor::addOption().
     *
     * @see \Doctrine\MongoDB\Cursor::addOption()
     * @see http://php.net/manual/en/mongocursor.addoption.php
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addOption($key, $value)
    {
        $this->baseCursor->addOption($key, $value);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::batchSize().
     *
     * @see \Doctrine\MongoDB\Cursor::batchSize()
     * @see http://php.net/manual/en/mongocursor.batchsize.php
     * @param integer $num
     * @return self
     */
    public function batchSize($num)
    {
        $this->baseCursor->batchSize($num);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::current().
     *
     * If configured, the result may be a hydrated document class instance.
     *
     * @see \Doctrine\MongoDB\Cursor::current()
     * @see http://php.net/manual/en/iterator.current.php
     * @see http://php.net/manual/en/mongocursor.current.php
     * @return array|object|null
     */
    public function current()
    {
        $current = $this->baseCursor->current();

        if ($current !== null && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $current, $this->unitOfWorkHints);
        }

        return $current;
    }

    /**
     * Wrapper method for MongoCursor::fields().
     *
     * @see \Doctrine\MongoDB\Cursor::fields()
     * @see http://php.net/manual/en/mongocursor.fields.php
     * @return self
     */
    public function fields(array $f)
    {
        $this->baseCursor->fields($f);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::getNext().
     *
     * If configured, the result may be a hydrated document class instance.
     *
     * @see \Doctrine\MongoDB\Cursor::getNext()
     * @see http://php.net/manual/en/mongocursor.getnext.php
     * @return array|object|null
     */
    public function getNext()
    {
        $next = $this->baseCursor->getNext();

        if ($next !== null && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $next, $this->unitOfWorkHints);
        }

        return $next;
    }

    /**
     * Wrapper method for MongoCursor::getReadPreference().
     *
     * @see \Doctrine\MongoDB\Cursor::getReadPreference()
     * @see http://php.net/manual/en/mongocursor.getreadpreference.php
     * @return array
     */
    public function getReadPreference()
    {
        return $this->baseCursor->getReadPreference();
    }

    /**
     * Wrapper method for MongoCursor::setReadPreference().
     *
     * @see \Doctrine\MongoDB\Cursor::setReadPreference()
     * @see http://php.net/manual/en/mongocursor.setreadpreference.php
     * @param string $readPreference
     * @param array  $tags
     * @return self
     */
    public function setReadPreference($readPreference, array $tags = null)
    {
        $this->baseCursor->setReadPreference($readPreference, $tags);
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE] = $readPreference;
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE_TAGS] = $tags;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::hint().
     *
     * This method is intended for setting MongoDB query hints, which are
     * unrelated to UnitOfWork hints.
     *
     * @see \Doctrine\MongoDB\Cursor::hint()
     * @see http://php.net/manual/en/mongocursor.hint.php
     * @param array|string $keyPattern
     * @return self
     */
    public function hint(array $keyPattern)
    {
        $this->baseCursor->hint($keyPattern);
        return $this;
    }

    /**
     * Set whether to hydrate results as document class instances.
     *
     * @param boolean $bool
     * @return self
     */
    public function hydrate($hydrate = true)
    {
        $this->hydrate = (boolean) $hydrate;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::immortal().
     *
     * @see \Doctrine\MongoDB\Cursor::immortal()
     * @see http://php.net/manual/en/mongocursor.immortal.php
     * @param boolean $liveForever
     * @return self
     */
    public function immortal($liveForever = true)
    {
        $this->baseCursor->immortal($liveForever);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::limit().
     *
     * @see \Doctrine\MongoDB\Cursor::limit()
     * @see http://php.net/manual/en/mongocursor.limit.php
     * @param integer $num
     * @return self
     */
    public function limit($num)
    {
        $this->baseCursor->limit($num);
        return $this;
    }

    /**
     * Set whether to refresh hydrated documents that are already in the
     * identity map.
     *
     * This option has no effect if hydration is disabled.
     *
     * @param boolean $refresh
     * @return self
     */
    public function refresh($refresh = true)
    {
        $this->unitOfWorkHints[Query::HINT_REFRESH] = (boolean) $refresh;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::skip().
     *
     * @see \Doctrine\MongoDB\Cursor::skip()
     * @see http://php.net/manual/en/mongocursor.skip.php
     * @param integer $num
     * @return self
     */
    public function skip($num)
    {
        $this->baseCursor->skip($num);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::slaveOkay().
     *
     * @see \Doctrine\MongoDB\Cursor::slaveOkay()
     * @see http://php.net/manual/en/mongocursor.slaveokay.php
     * @param boolean $ok
     * @return self
     */
    public function slaveOkay($ok = true)
    {
        $ok = (boolean) $ok;
        $this->baseCursor->slaveOkay($ok);
        $this->unitOfWorkHints[Query::HINT_SLAVE_OKAY] = $ok;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::snapshot().
     *
     * @see \Doctrine\MongoDB\Cursor::snapshot()
     * @see http://php.net/manual/en/mongocursor.snapshot.php
     * @return self
     */
    public function snapshot()
    {
        $this->baseCursor->snapshot();
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::sort().
     *
     * Field names will be prepared according to the document mapping.
     *
     * @see \Doctrine\MongoDB\Cursor::sort()
     * @see http://php.net/manual/en/mongocursor.sort.php
     * @param array $fields
     * @return self
     */
    public function sort($fields)
    {
        $fields = $this->unitOfWork
            ->getDocumentPersister($this->class->name)
            ->prepareSortOrProjection($fields);

        $this->baseCursor->sort($fields);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::tailable().
     *
     * @see \Doctrine\MongoDB\Cursor::tailable()
     * @see http://php.net/manual/en/mongocursor.tailable.php
     * @param boolean $tail
     * @return self
     */
    public function tailable($tail = true)
    {
        $this->baseCursor->tailable($tail);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::timeout().
     *
     * @see \Doctrine\MongoDB\Cursor::timeout()
     * @see http://php.net/manual/en/mongocursor.timeout.php
     * @param integer $ms
     * @return self
     */
    public function timeout($ms)
    {
        $this->baseCursor->timeout($ms);
        return $this;
    }
}
