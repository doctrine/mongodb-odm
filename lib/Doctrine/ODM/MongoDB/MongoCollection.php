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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Wrapper for the PHP MongoCollection class.
 *
 * @license 	http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    	www.doctrine-project.org
 * @since   	1.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoCollection
{
    /** The PHP MongoCollection being wrapped */
    private $_mongoCollection;

    /** The ClassMetadata instance for this collection */
    private $_class;

    /**
     * Create a new MongoCollection instance that wraps a PHP MongoCollection instance
     * for a given ClassMetadata instance.
     *
     * @param MongoCollection $mongoColleciton The MongoCollection instance.
     * @param ClassMetadata $class The ClassMetadata instance.
     */
    public function __construct(\MongoCollection $mongoCollection, ClassMetadata $class)
    {
        $this->_mongoCollection = $mongoCollection;
        $this->_class = $class;
    }

    /**
     * Returns teh ClassMetadata instance for this collection.
     *
     * @return Doctrine\ODM\MongoDB\MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->_mongoCollection;
    }

    /** @override */
    public function batchInsert(array &$a, array $options = array())
    {
        if ($this->_mongoCollection instanceof \MongoGridFS) {
            foreach ($a as $key => $array) {
                $this->saveFile($array);
                $a[$key] = $array;
            }
            return $a;
        }
        return $this->_mongoCollection->batchInsert($a, $options);
    }

    /**
     * Save a file whether it exists or not already. Deletes previous file 
     * contents before trying to store new file contents.
     *
     * @param array $a Array to store
     * @return array $a
     */
    public function saveFile(array &$a)
    {
        $fileName = $this->_class->fieldMappings[$this->_class->file]['fieldName'];
        $file = $a[$fileName];
        unset($a[$fileName]);
        if ($file instanceof \MongoGridFSFile) {
            $id = $a['_id'];
            unset($a['_id']);
            $set = array('$set' => $a);
            $this->_mongoCollection->update(array('_id' => $id), $set);
        } else {
            if (isset($a['_id'])) {
                $this->_mongoCollection->chunks->remove(array('files_id' => $a['_id']));
            }
            if (file_exists($file)) {
                $id = $this->_mongoCollection->storeFile($file, $a);
            } else if (is_string($file)) {
                $id = $this->_mongoCollection->storeBytes($file, $a);
            }
            $file = $this->_mongoCollection->findOne(array('_id' => $id));
        }
        $a = $file->file;
        $a[$this->_class->file] = $file;
        return $a;
    }

    /** @override */
    public function getDBRef(array $reference)
    {
        if ($this->_class->isFile()) {
            $ref = $this->_mongoCollection->getDBRef($reference);
            $file = $this->_mongoCollection->findOne(array('_id' => $ref['_id']));
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_mongoCollection->getDBRef($reference);
    }

    /** @override */
    public function save(array &$a, array $options = array())
    {
        if ($this->_class->isFile()) {
            return $this->saveFile($a);
        }
        return $this->_mongoCollection->save($a, $options);
    }

    /** @override */
    public function find(array $query = array(), array $fields = array())
    {
        return $this->_mongoCollection->find($query, $fields);
    }

    /** @override */
    public function findOne(array $query = array(), array $fields = array())
    {
        if ($this->_mongoCollection instanceof \MongoGridFS) {
            $file = $this->_mongoCollection->findOne($query);
            $data = $file->file;
            $data[$this->_class->file] = $file;
            return $data;
        }
        return $this->_mongoCollection->findOne($query, $fields);
    }

    /** @proxy */
    public function __call($method, $arguments)
    {
        if (method_exists($this->_mongoCollection, $method)) {
            return call_user_func_array(array($this->_mongoCollection, $method), $arguments);
        }
    }
}