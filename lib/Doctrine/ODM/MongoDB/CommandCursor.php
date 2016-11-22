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

use BadMethodCallException;
use Doctrine\MongoDB\CommandCursor as BaseCommandCursor;
use Doctrine\MongoDB\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Wrapper for the CommandCursor class.
 *
 * @since  1.1
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class CommandCursor implements Iterator
{
    /**
     * The CommandCursor instance being wrapped.
     *
     * @var BaseCommandCursor
     */
    private $commandCursor;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @param BaseCommandCursor $commandCursor The ComamndCursor instance being wrapped
     * @param UnitOfWork $unitOfWork
     * @param ClassMetadata $class The class to use for hydration or null if results should not be hydrated
     */
    public function __construct(BaseCommandCursor $commandCursor, UnitOfWork $unitOfWork, ClassMetadata $class = null)
    {
        $this->commandCursor = $commandCursor;
        $this->unitOfWork = $unitOfWork;
        $this->class = $class;
    }

    /**
     * Wrapper method for MongoCommandCursor::batchSize().
     *
     * @see http://php.net/manual/en/mongocommandcursor.batchsize.php
     * @param integer $num
     * @return self
     */
    public function batchSize($num)
    {
        $this->commandCursor->batchSize($num);

        return $this;
    }

    /**
     * Recreates the command cursor and counts its results.
     *
     * @see http://php.net/manual/en/countable.count.php
     * @return integer
     */
    public function count()
    {
        return $this->commandCursor->count();
    }

    /**
     * Wrapper method for MongoCommandCursor::current().
     *
     * @see http://php.net/manual/en/iterator.current.php
     * @see http://php.net/manual/en/mongocommandcursor.current.php
     * @return object|array|null
     */
    public function current()
    {
        return $this->hydrateDocument($this->commandCursor->current());
    }

    /**
     * Wrapper method for MongoCommandCursor::dead().
     *
     * @see http://php.net/manual/en/mongocommandcursor.dead.php
     * @return boolean
     */
    public function dead()
    {
        return $this->commandCursor->dead();
    }

    /**
     * Returns the MongoCommandCursor instance being wrapped.
     *
     * @return BaseCommandCursor
     */
    public function getBaseCursor()
    {
        return $this->commandCursor;
    }

    /**
     * Rewinds the cursor and returns its first result.
     *
     * @see Iterator::getSingleResult()
     * @return object|array|null
     */
    public function getSingleResult()
    {
        return $this->hydrateDocument($this->commandCursor->getSingleResult());
    }

    /**
     * @param array $document
     * @return array|object|null
     */
    private function hydrateDocument($document)
    {
        if ($document !== null && $this->class !== null) {
            return $this->unitOfWork->getOrCreateDocument($this->class->name, $document);
        }

        return $document;
    }

    /**
     * Wrapper method for MongoCommandCursor::info().
     *
     * @see http://php.net/manual/en/mongocommandcursor.info.php
     * @return array
     */
    public function info()
    {
        return $this->commandCursor->info();
    }

    /**
     * Wrapper method for MongoCommandCursor::key().
     *
     * @see http://php.net/manual/en/iterator.key.php
     * @see http://php.net/manual/en/mongocommandcursor.key.php
     * @return integer
     */
    public function key()
    {
        return $this->commandCursor->key();
    }

    /**
     * Wrapper method for MongoCommandCursor::next().
     *
     * @see http://php.net/manual/en/iterator.next.php
     * @see http://php.net/manual/en/mongocommandcursor.next.php
     */
    public function next()
    {
        $cursor = $this;

        $cursor->commandCursor->next();
    }

    /**
     * Wrapper method for MongoCommandCursor::rewind().
     *
     * @see http://php.net/manual/en/iterator.rewind.php
     * @see http://php.net/manual/en/mongocommandcursor.rewind.php
     * @return array
     */
    public function rewind()
    {
        return $this->commandCursor->rewind();
    }

    /**
     * Wrapper method for MongoCommandCursor::timeout().
     *
     * @see http://php.net/manual/en/mongocommandcursor.timeout.php
     * @param integer $ms
     * @return self
     * @throws BadMethodCallException if MongoCommandCursor::timeout() is not available
     */
    public function timeout($ms)
    {
        $this->commandCursor->timeout($ms);

        return $this;
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
     * Wrapper method for MongoCommandCursor::valid().
     *
     * @see http://php.net/manual/en/iterator.valid.php
     * @see http://php.net/manual/en/mongocommandcursor.valid.php
     * @return boolean
     */
    public function valid()
    {
        return $this->commandCursor->valid();
    }
}
