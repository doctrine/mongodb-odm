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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Hydrator;

/**
 * Wrapper for the PHP MongoCursor class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoCursor implements MongoIterator
{
    /** The DocumentManager instance. */
    private $dm;

    /** The UnitOfWork instance. */
    private $uow;

    /** The ClassMetadata instance. */
    private $class;

    /** The PHP MongoCursor being wrapped */
    private $mongoCursor;

    /** Whether or not to try and hydrate the returned data */
    private $hydrate = true;

    /** A callable for logging statements. */
    private $loggerCallable;

    /**
     * Create a new MongoCursor which wraps around a given PHP MongoCursor.
     *
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     * @param Hydrator $hydrator
     * @param ClassMetadata $class
     * @param Configuration $c
     * @param MongoCursor $mongoCursor
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, Hydrator $hydrator, ClassMetadata $class, Configuration $c, \MongoCursor $mongoCursor)
    {
        $this->dm = $dm;
        $this->uow = $uow;
        $this->hydrator = $hydrator;
        $this->class = $class;
        $this->loggerCallable = $c->getLoggerCallable();
        $this->mongoCursor = $mongoCursor;
    }

    /**
     * Log something using the configured logger callable.
     *
     * @param array $log The array of data to log.
     */
    public function log(array $log)
    {
        if ( ! $this->loggerCallable) {
            return;
        }
        $log['class'] = $this->class->name;
        $log['db'] = $this->class->db;
        $log['collection'] = $this->class->collection;
        call_user_func_array($this->loggerCallable, array($log));
    }

    /**
     * Returns the MongoCursor instance being wrapped.
     *
     * @return MongoCursor $mongoCursor The MongoCursor instance being wrapped.
     */
    public function getMongoCursor()
    {
        return $this->mongoCursor;
    }

    /**
     * Whether or not to try and hydrate the returned data
     *
     * @param boolean $bool
     */
    public function hydrate($bool = null)
    {
        if ($bool !== null)
        {
            $this->hydrate = $bool;
            return $this;
        } else {
            return $this->hydrate;
        }
    }

    /** @override */
    public function current()
    {
        if ($this->mongoCursor instanceof \MongoGridFSCursor) {
            $file = $this->mongoCursor->current();
            $current = $file->file;
            $current[$this->class->file] = $file;
        } else {
            $current = $this->mongoCursor->current();
        }
        if ($this->hydrate) {
            return $this->uow->getOrCreateDocument($this->class->name, $current);
        } else {
            return $current;
        }
    }

    /** @proxy */
    public function key()
    {
        return $this->mongoCursor->key();
    }

    /** @proxy */
    public function dead()
    {
        return $this->mongoCursor->dead();
    }

    /** @proxy */
    public function explain()
    {
        return $this->mongoCursor->explain();
    }

    /** @proxy */
    public function fields(array $f)
    {
        $this->mongoCursor->fields($f);
        return $this;
    }

    /** @proxy */
    public function getNext()
    {
        return $this->mongoCursor->getNext();
    }

    /** @proxy */
    public function hasNext()
    {
        return $this->mongoCursor->hasNext();
    }

    /** @proxy */
    public function hint(array $keyPattern)
    {
        $this->mongoCursor->hint($keyPattern);
        return $this;
    }

    /** @proxy */
    public function immortal($liveForever = true)
    {
        $this->mongoCursor->immortal($liveForever);
        return $this;
    }

    /** @proxy */
    public function info()
    {
        return $this->mongoCursor->info();
    }

    /** @proxy */
    public function rewind()
    {
        return $this->mongoCursor->rewind();
    }

    /** @proxy */
    public function next()
    {
        return $this->mongoCursor->next();
    }

    /** @proxy */
    public function reset()
    {
        return $this->mongoCursor->reset();
    }

    /** @proxy */
    public function count($foundOnly = false)
    {
        return $this->mongoCursor->count($foundOnly);
    }

    /** @proxy */
    public function addOption($key, $value)
    {
        $this->mongoCursor->addOption($key, $value);
        return $this;
    }

    /** @proxy */
    public function batchSize($num)
    {
        $htis->mongoCursor->batchSize($num);
        return $this;
    }

    /** @proxy */
    public function limit($num)
    {
        $this->mongoCursor->limit($num);
        return $this;
    }

    /** @proxy */
    public function skip($num)
    {
        $this->mongoCursor->skip($num);
        return $this;
    }

    /** @proxy */
    public function slaveOkay($okay = true)
    {
        $this->mongoCursor->slaveOkay($okay);
        return $this;
    }

    /** @proxy */
    public function snapshot()
    {
        $this->mongoCursor->snapshot();
        return $this;
    }

    /** @proxy */
    public function sort($fields)
    {
        $this->mongoCursor->sort($fields);
        return $this;
    }

    /** @proxy */
    public function tailable($tail = true)
    {
        $this->mongoCursor->tailable($tail);
        return $this;
    }

    /** @proxy */
    public function timeout($ms)
    {
        $this->mongoCursor->timeout($ms);
        return $this;
    }

    /** @proxy */
    public function valid()
    {
        return $this->mongoCursor->valid();
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }

    /**
     * Get the first single result from the cursor.
     *
     * @return object $document  The single document.
     */
    public function getSingleResult()
    {
        $result = null;
        $this->valid() ?: $this->next();
        if ($this->valid()) {
            $result = $this->current();
        }
        $this->reset();
        return $result ? $result : null;
    }
}