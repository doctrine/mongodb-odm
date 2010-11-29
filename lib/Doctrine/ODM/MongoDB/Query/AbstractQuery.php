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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\MongoIterator,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Abstract executable query class for the different types of queries to implement.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractQuery implements QueryInterface, MongoIterator
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    protected $dm;

    /**
     * The ClassMetadata instance for the class being queried
     *
     * @var ClassMetadata
     */
    protected $class;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    protected $cmd;

    /**
     * @var MongoIterator
     */
    protected $iterator;

    public function __construct(DocumentManager $dm, ClassMetadata $class, $cmd)
    {
        $this->dm = $dm;
        $this->class = $class;
        $this->cmd = $cmd;
    }

    /**
     * Gets an array of information about this query for debugging.
     *
     * @param string $name
     * @return array $debug
     */
    public function debug($name = null)
    {
        $debug = get_object_vars($this);

        unset($debug['dm']);
        if ($name !== null) {
            return $debug[$name];
        }
        foreach ($debug as $key => $value) {
            if ( ! $value) {
                unset($debug[$key]);
            }
        }
        return $debug;
    }

    public function getIterator(array $options = array())
    {
        if ($this->iterator === null) {
            $iterator = $this->execute($options);
            if ($iterator !== null && !$iterator instanceof MongoIterator) {
                throw new \BadMethodCallException('Query execution did not return an iterator. This query may not support returning iterators. ');
            }
            $this->iterator = $iterator;
        }
        return $this->iterator;
    }

    /**
     * Count the number of results for this query.
     *
     * @param bool $all
     * @return integer $count
     */
    public function count($all = false)
    {
        return $this->getIterator()->count($all);
    }

    /**
     * Execute the query and get a single result
     *
     * @return object $document  The single document.
     */
    public function getSingleResult(array $options = array())
    {
        return $this->getIterator($options)->getSingleResult();
    }

    /**
     * Iterator over the query using the MongoCursor.
     *
     * @return MongoCursor $cursor
     */
    public function iterate()
    {
        return $this->getIterator();
    }

    /** @inheritDoc */
    public function first()
    {
        return $this->getIterator()->first();
    }

    /** @inheritDoc */
    public function last()
    {
        return $this->getIterator()->last();
    }

    /** @inheritDoc */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /** @inheritDoc */
    public function next()
    {
        return $this->getIterator()->next();
    }

    /** @inheritDoc */
    public function current()
    {
        return $this->getIterator()->current();
    }

    /** @inheritDoc */
    public function rewind()
    {
        return $this->getIterator()->rewind();
    }

    /** @inheritDoc */
    public function valid()
    {
        return $this->getIterator()->valid();
    }

    /** @inheritDoc */
    public function toArray()
    {
        return $this->getIterator()->toArray();
    }
}