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
use Doctrine\MongoDB\EagerCursor as BaseEagerCursor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;

/**
 * @todo Reference priming is currently disabled since a cursor can only be iterated once
 */
class Cursor implements \Iterator
{
    /**
     * The cursor instance being wrapped.
     *
     * @var \MongoDB\Driver\Cursor
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
     * @var \Generator
     */
    private $iterator;

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
     * @param \MongoDB\Driver\Cursor $baseCursor Cursor instance being wrapped
     * @param UnitOfWork $unitOfWork UnitOfWork for result hydration and query preparation
     * @param ClassMetadata $class ClassMetadata for the document class being queried
     */
    public function __construct(\MongoDB\Driver\Cursor $baseCursor, UnitOfWork $unitOfWork, ClassMetadata $class = null)
    {
        $this->baseCursor = $baseCursor;
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
        $this->hydrate = $class !== null;
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
     * @return \Generator
     */
    private function ensureIterator()
    {
        if ($this->iterator === null) {
            $this->iterator = $this->wrapTraversable($this->baseCursor);
        }

        return $this->iterator;
    }

    /**
     * @param \Traversable $traversable
     * @return \Generator
     */
    private function wrapTraversable(\Traversable $traversable)
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
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
        return $this->hydrateDocument($this->ensureIterator()->current());
    }

    /**
     * @return boolean
     */
    public function isDead()
    {
        return $this->baseCursor->isDead();
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
        $document = $this->hydrateDocument($this->ensureIterator()->current());
        $this->primeReferencesForSingleResult($document);

        return $document;
    }

    /**
     * Set whether to hydrate results as document class instances.
     *
     * @param boolean $hydrate
     * @return $this
     */
    public function hydrate($hydrate = true)
    {
        if ($hydrate && $this->class === null) {
            throw new \RuntimeException('Cannot enable hydration when no class was given');
        }

        $this->hydrate = (boolean) $hydrate;
        return $this;
    }

    /**
     * @param array $document
     * @return array|object|null
     */
    private function hydrateDocument($document)
    {
        if ($document !== null && $this->hydrate && $this->class) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $document, $this->unitOfWorkHints);
        }

        return $document;
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
        return $this->ensureIterator()->key();
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
        $this->ensureIterator()->next();
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
     * @see http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        $this->ensureIterator()->rewind();
    }

    /**
     * Return the cursor's results as an array.
     *
     * @see Iterator::toArray()
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this);
    }

    /**
     * @see http://php.net/manual/en/iterator.valid.php
     * @return boolean
     */
    public function valid()
    {
        return $this->ensureIterator()->valid();
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
