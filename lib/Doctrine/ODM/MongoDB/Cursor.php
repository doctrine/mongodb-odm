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
use Doctrine\MongoDB\CursorInterface;
use Doctrine\MongoDB\EagerCursor as BaseEagerCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;

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
 */
class Cursor implements CursorInterface
{
    /**
     * The Doctrine\MongoDB\Cursor instance being wrapped.
     *
     * @var CursorInterface
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
     * ReferencePrimer object for priming references
     *
     * @var ReferencePrimer
     */
    private $referencePrimer;

    /**
     * Primers
     *
     * @var array
     */
    private $primers = array();

    /**
     * Whether references have been primed
     *
     * @var bool
     */
    private $referencesPrimed = false;

    /**
     * Constructor.
     *
     * @param CursorInterface $baseCursor  Cursor instance being wrapped
     * @param UnitOfWork      $unitOfWork  UnitOfWork for result hydration and query preparation
     * @param ClassMetadata   $class       ClassMetadata for the document class being queried
     */
    public function __construct(CursorInterface $baseCursor, UnitOfWork $unitOfWork, ClassMetadata $class)
    {
        $this->baseCursor = $baseCursor;
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
    }

    /**
     * Return the wrapped Doctrine\MongoDB\Cursor instance.
     *
     * @return CursorInterface
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
        return $this->baseCursor->getCollection()->getDatabase()->getConnection();
    }

    /**
     * Return the collection for this cursor.
     *
     * @see CursorInterface::getCollection()
     * @return Collection
     */
    public function getCollection()
    {
        return $this->baseCursor->getCollection();
    }

    /**
     * Return the selected fields (projection).
     *
     * @see CursorInterface::getFields()
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
     * @see CursorInterface::getQuery()
     * @return array
     */
    public function getQuery()
    {
        return $this->baseCursor->getQuery();
    }

    /**
     * Wrapper method for MongoCursor::addOption().
     *
     * @see CursorInterface::addOption()
     * @see http://php.net/manual/en/mongocursor.addoption.php
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addOption($key, $value)
    {
        $this->baseCursor->addOption($key, $value);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::batchSize().
     *
     * @see CursorInterface::batchSize()
     * @see http://php.net/manual/en/mongocursor.batchsize.php
     * @param integer $num
     * @return $this
     */
    public function batchSize($num)
    {
        $this->baseCursor->batchSize($num);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::count().
     *
     * @see CursorInterface::count()
     * @see http://php.net/manual/en/countable.count.php
     * @see http://php.net/manual/en/mongocursor.count.php
     * @param boolean $foundOnly
     * @return integer
     */
    public function count($foundOnly = false)
    {
        return $this->baseCursor->count($foundOnly);
    }

    /**
     * Wrapper method for MongoCursor::current().
     *
     * If configured, the result may be a hydrated document class instance.
     *
     * @see CursorInterface::current()
     * @see http://php.net/manual/en/iterator.current.php
     * @see http://php.net/manual/en/mongocursor.current.php
     * @return array|object|null
     */
    public function current()
    {
        $this->primeReferences();

        return $this->hydrateDocument($this->baseCursor->current());
    }

    /**
     * Wrapper method for MongoCursor::dead().
     *
     * @see CursorInterface::dead()
     * @see http://php.net/manual/en/mongocursor.dead.php
     * @return boolean
     */
    public function dead()
    {
        return $this->baseCursor->dead();
    }

    /**
     * Wrapper method for MongoCursor::explain().
     *
     * @see CursorInterface::explain()
     * @see http://php.net/manual/en/mongocursor.explain.php
     * @return array
     */
    public function explain()
    {
        return $this->baseCursor->explain();
    }

    /**
     * Wrapper method for MongoCursor::fields().
     *
     * @param array $f Fields to return (or not return).
     *
     * @see CursorInterface::fields()
     * @see http://php.net/manual/en/mongocursor.fields.php
     * @return $this
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
     * @see CursorInterface::getNext()
     * @see http://php.net/manual/en/mongocursor.getnext.php
     * @return array|object|null
     */
    public function getNext()
    {
        $this->primeReferences();

        return $this->hydrateDocument($this->baseCursor->getNext());
    }

    /**
     * Wrapper method for MongoCursor::getReadPreference().
     *
     * @see CursorInterface::getReadPreference()
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
     * @see CursorInterface::setReadPreference()
     * @see http://php.net/manual/en/mongocursor.setreadpreference.php
     * @param string $readPreference
     * @param array  $tags
     * @return $this
     */
    public function setReadPreference($readPreference, array $tags = null)
    {
        $this->baseCursor->setReadPreference($readPreference, $tags);
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE] = $readPreference;
        $this->unitOfWorkHints[Query::HINT_READ_PREFERENCE_TAGS] = $tags;
        return $this;
    }

    /**
     * Reset the cursor and return its first result.
     *
     * The cursor will be reset both before and after the single result is
     * fetched. The original cursor limit (if any) will remain in place.
     *
     * @see Iterator::getSingleResult()
     * @return array|object|null
     */
    public function getSingleResult()
    {
        $document = $this->hydrateDocument($this->baseCursor->getSingleResult());
        $this->primeReferencesForSingleResult($document);

        return $document;
    }

    /**
     * {@inheritDoc}
     */
    public function getUseIdentifierKeys()
    {
        return $this->baseCursor->getUseIdentifierKeys();
    }

    /**
     * {@inheritDoc}
     */
    public function setUseIdentifierKeys($useIdentifierKeys)
    {
        $this->baseCursor->setUseIdentifierKeys($useIdentifierKeys);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasNext()
    {
        return $this->baseCursor->hasNext();
    }

    /**
     * Wrapper method for MongoCursor::hint().
     *
     * This method is intended for setting MongoDB query hints, which are
     * unrelated to UnitOfWork hints.
     *
     * @see CursorInterface::hint()
     * @see http://php.net/manual/en/mongocursor.hint.php
     * @param array|string $keyPattern
     * @return $this
     */
    public function hint($keyPattern)
    {
        $this->baseCursor->hint($keyPattern);
        return $this;
    }

    /**
     * Set whether to hydrate results as document class instances.
     *
     * @param boolean $hydrate
     * @return $this
     */
    public function hydrate($hydrate = true)
    {
        $this->hydrate = (boolean) $hydrate;
        return $this;
    }

    /**
     * @param array $document
     * @return array|object|null
     */
    private function hydrateDocument($document)
    {
        if ($document !== null && $this->hydrate) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints);
        }

        return $document;
    }

    /**
     * Wrapper method for MongoCursor::immortal().
     *
     * @see CursorInterface::immortal()
     * @see http://php.net/manual/en/mongocursor.immortal.php
     * @param boolean $liveForever
     * @return $this
     */
    public function immortal($liveForever = true)
    {
        $this->baseCursor->immortal($liveForever);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::info().
     *
     * @see CursorInterface::info()
     * @see http://php.net/manual/en/mongocursor.info.php
     * @return array
     */
    public function info()
    {
        return $this->baseCursor->info();
    }

    /**
     * Wrapper method for MongoCursor::key().
     *
     * @see CursorInterface::key()
     * @see http://php.net/manual/en/iterator.key.php
     * @see http://php.net/manual/en/mongocursor.key.php
     * @return string
     */
    public function key()
    {
        return $this->baseCursor->key();
    }

    /**
     * Wrapper method for MongoCursor::limit().
     *
     * @see CursorInterface::limit()
     * @see http://php.net/manual/en/mongocursor.limit.php
     * @param integer $num
     * @return $this
     */
    public function limit($num)
    {
        $this->baseCursor->limit($num);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::next().
     *
     * @see CursorInterface::next()
     * @see http://php.net/manual/en/iterator.next.php
     * @see http://php.net/manual/en/mongocursor.next.php
     */
    public function next()
    {
        $this->baseCursor->next();
    }

    /**
     * Recreates the internal MongoCursor.
     *
     * @see CursorInterface::recreate()
     */
    public function recreate()
    {
        $this->baseCursor->recreate();
    }

    /**
     * Set whether to refresh hydrated documents that are already in the
     * identity map.
     *
     * This option has no effect if hydration is disabled.
     *
     * @param boolean $refresh
     * @return $this
     */
    public function refresh($refresh = true)
    {
        $this->unitOfWorkHints[Query::HINT_REFRESH] = (boolean) $refresh;
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::reset().
     *
     * @see CursorInterface::reset()
     * @see http://php.net/manual/en/iterator.reset.php
     * @see http://php.net/manual/en/mongocursor.reset.php
     */
    public function reset()
    {
        $this->baseCursor->reset();
    }

    /**
     * Wrapper method for MongoCursor::rewind().
     *
     * @see CursorInterface::rewind()
     * @see http://php.net/manual/en/iterator.rewind.php
     * @see http://php.net/manual/en/mongocursor.rewind.php
     */
    public function rewind()
    {
        $this->baseCursor->rewind();
    }

    /**
     * Wrapper method for MongoCursor::skip().
     *
     * @see CursorInterface::skip()
     * @see http://php.net/manual/en/mongocursor.skip.php
     * @param integer $num
     * @return $this
     */
    public function skip($num)
    {
        $this->baseCursor->skip($num);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::slaveOkay().
     *
     * @see CursorInterface::slaveOkay()
     * @see http://php.net/manual/en/mongocursor.slaveokay.php
     * @param boolean $ok
     * @return $this
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
     * @see CursorInterface::snapshot()
     * @see http://php.net/manual/en/mongocursor.snapshot.php
     * @return $this
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
     * @see CursorInterface::sort()
     * @see http://php.net/manual/en/mongocursor.sort.php
     * @param array $fields
     * @return $this
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
     * @see CursorInterface::tailable()
     * @see http://php.net/manual/en/mongocursor.tailable.php
     * @param boolean $tail
     * @return $this
     */
    public function tailable($tail = true)
    {
        $this->baseCursor->tailable($tail);
        return $this;
    }

    /**
     * Wrapper method for MongoCursor::timeout().
     *
     * @see CursorInterface::timeout()
     * @see http://php.net/manual/en/mongocursor.timeout.php
     * @param integer $ms
     * @return $this
     */
    public function timeout($ms)
    {
        $this->baseCursor->timeout($ms);
        return $this;
    }

    /**
     * Return the cursor's results as an array.
     *
     * If documents in the result set use BSON objects for their "_id", the
     * $useKeys parameter may be set to false to avoid errors attempting to cast
     * arrays (i.e. BSON objects) to string keys.
     *
     * @see Iterator::toArray()
     * @param boolean $useIdentifierKeys
     * @return array
     */
    public function toArray($useIdentifierKeys = null)
    {
        $originalUseIdentifierKeys = $this->getUseIdentifierKeys();
        $useIdentifierKeys = isset($useIdentifierKeys) ? (boolean) $useIdentifierKeys : $this->baseCursor->getUseIdentifierKeys();

        /* Let iterator_to_array() decide to use keys or not. This will avoid
         * superfluous MongoCursor::info() from the key() method until the
         * cursor position is tracked internally.
         */
        $this->setUseIdentifierKeys(true);

        $results = iterator_to_array($this, $useIdentifierKeys);

        $this->setUseIdentifierKeys($originalUseIdentifierKeys);

        return $results;
    }

    /**
     * Wrapper method for MongoCursor::valid().
     *
     * @see CursorInterface::valid()
     * @see http://php.net/manual/en/iterator.valid.php
     * @see http://php.net/manual/en/mongocursor.valid.php
     * @return boolean
     */
    public function valid()
    {
        return $this->baseCursor->valid();
    }

    /**
     * @param array $primers
     * @param ReferencePrimer $referencePrimer
     * @return $this
     */
    public function enableReferencePriming(array $primers, ReferencePrimer $referencePrimer)
    {
        if ( ! $this->baseCursor instanceof BaseEagerCursor) {
            throw new \BadMethodCallException("Can't enable reference priming when not using eager cursors.");
        }

        $this->referencePrimer = $referencePrimer;
        $this->primers = $primers;
        return $this;
    }

    /**
     * Prime references
     */
    protected function primeReferences()
    {
        if ($this->referencesPrimed || ! $this->hydrate || empty($this->primers)) {
            return;
        }

        $this->referencesPrimed = true;

        foreach ($this->primers as $fieldName => $primer) {
            $primer = is_callable($primer) ? $primer : null;
            $this->referencePrimer->primeReferences($this->class, $this, $fieldName, $this->unitOfWorkHints, $primer);
        }

        $this->rewind();
    }

    /**
     * Primes all references for a single document only. This avoids iterating
     * over the entire cursor when getSingleResult() is called.
     *
     * @param object $document
     */
    protected function primeReferencesForSingleResult($document)
    {
        if ($this->referencesPrimed || ! $this->hydrate || empty($this->primers) || null === $document) {
            return;
        }

        foreach ($this->primers as $fieldName => $primer) {
            $primer = is_callable($primer) ? $primer : null;
            $this->referencePrimer->primeReferences($this->class, array($document), $fieldName, $this->unitOfWorkHints, $primer);
        }
    }
}
