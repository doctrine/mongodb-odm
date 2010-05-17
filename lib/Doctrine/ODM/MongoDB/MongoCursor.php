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
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoCursor implements \Iterator
{
    /** The DocumentManager instance. */
    private $_dm;
    /** The UnitOfWork instance. */
    private $_uow;
    /** The ClassMetadata instance. */
    private $_class;
    /** The PHP MongoCursor being wrapped */
    private $_mongoCursor;

    /**
     * Create a new MongoCursor which wraps around a given PHP MongoCursor.
     *
     * @param DocumentManager $dm
     * @param Hydrator $hydrator
     * @param ClassMetadata $class
     * @param MongoCursor $mongoCursor
     */
    public function __construct(DocumentManager $dm, Hydrator $hydrator, ClassMetadata $class, \MongoCursor $mongoCursor)
    {
        $this->_dm = $dm;
        $this->_uow = $this->_dm->getUnitOfWork();
        $this->_hydrator = $hydrator;
        $this->_class = $class;
        $this->_mongoCursor = $mongoCursor;
    }

    /**
     * Returns the MongoCursor instance being wrapped.
     *
     * @return MongoCursor $mongoCursor The MongoCursor instance being wrapped.
     */
    public function getMongoCursor()
    {
        return $this->_mongoCursor;
    }

    /** @override */
    public function current()
    {
        if ($this->_mongoCursor instanceof \MongoGridFSCursor) {
            $file = $this->_mongoCursor->current();
            $current = $file->file;
            $current[$this->_class->file] = $file;
        } else {
            $current = $this->_mongoCursor->current();
        }
        $document = $this->_uow->getOrCreateDocument($this->_class->name, $current);
        return $document;
    }

    /** @proxy */
    public function next()
    {
        return $this->_mongoCursor->next();
    }

    /** @proxy */
    public function key()
    {
        return $this->_mongoCursor->key();
    }

    /** @proxy */
    public function valid()
    {
        return $this->_mongoCursor->valid();
    }

    /** @proxy */
    public function rewind()
    {
        return $this->_mongoCursor->rewind();
    }

    /**
     * Returns an array by converting the iterator to an array.
     *
     * @return array $results
     */
    public function getResults()
    {
        return iterator_to_array($this);
    }

    /** @proxy */
    public function __call($method, $arguments)
    {
        $return = call_user_func_array(array($this->_mongoCursor, $method), $arguments);
        if ($return === $this->_mongoCursor) {
            return $this;
        }
        return $return;
    }
}