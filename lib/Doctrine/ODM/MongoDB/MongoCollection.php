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
    Doctrine\Common\EventManager,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\ODM\MongoDB\Event\CollectionEventArgs,
    Doctrine\ODM\MongoDB\Event\CollectionUpdateEventArgs;

/**
 * Wrapper for the PHP MongoCollection class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoCollection
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The PHP MongoCollection being wrapped.
     *
     * @var \MongoCollection
     */
    private $mongoCollection;

    /**
     * The PHP MongoDB that the collection lives in.
     *
     * @var \MongoDB
     */
    private $db;

    /**
     * The ClassMetadata instance for this collection.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * A callable for logging statements.
     *
     * @var mixed
     */
    private $loggerCallable;

    /**
     * The event manager that is the central point of the event system.
     *
     * @var Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * Create a new MongoCollection instance that wraps a PHP MongoCollection instance
     * for a given ClassMetadata instance.
     *
     * @param MongoCollection $mongoCollection The MongoCollection instance.
     * @param ClassMetadata $class The ClassMetadata instance.
     * @param EventManager $evm The EventManager instance.
     * @param Configuration $c The Configuration instance
     */
    public function __construct(DocumentManager $dm, \MongoCollection $mongoCollection, MongoDB $db, ClassMetadata $class, EventManager $evm, Configuration $c)
    {
        $this->dm = $dm;
        $this->mongoCollection = $mongoCollection;
        $this->db = $db;
        $this->class = $class;
        $this->eventManager = $evm;
        $this->loggerCallable = $c->getLoggerCallable();
        $this->cmd = $c->getMongoCmd();
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
     * Returns teh ClassMetadata instance for this collection.
     *
     * @return Doctrine\ODM\MongoDB\MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->mongoCollection;
    }

    /** @override */
    public function batchInsert(array &$a, array $options = array())
    {
        if ($this->eventManager->hasListeners(CollectionEvents::preBatchInsert)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preBatchInsert, new CollectionEventArgs($this, $a));
        }

        if ($this->class->isFile()) {
            foreach ($a as $key => &$array) {
                $this->insertFile($array);
            }
        } else {
            $this->mongoCollection->batchInsert($a, $options);
        }

        if ($this->loggerCallable) {
            $this->log(array(
                'batchInsert' => true,
                'num' => count($a),
                'data' => $a
            ));
        }

        if ($this->eventManager->hasListeners(CollectionEvents::postBatchInsert)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postBatchInsert, new CollectionEventArgs($this, $result));
        }

        return $a;
    }

    /**
     * Save a file whether it exists or not already. Deletes previous file
     * contents before trying to store new file contents.
     *
     * @param array $a Array to store
     * @return array $a
     */
    public function insertFile(array &$a)
    {
        if ($this->eventManager->hasListeners(CollectionEvents::preInsertFile)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preInsertFile, new CollectionEventArgs($this, $a));
        }
        $fileName = $this->class->fieldMappings[$this->class->file]['fieldName'];

        // If file exists and is dirty then lets persist the file and store the file path or the bytes
        if (isset($a[$fileName]) && $a[$fileName]->isDirty()) {
            $file = $a[$fileName]; // instanceof MongoGridFSFile

            $document = $a;
            unset($document[$fileName]);

            $this->storeFile($file, $document);
        }

        if ($this->eventManager->hasListeners(CollectionEvents::postInsertFile)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postInsertFile, new CollectionEventArgs($this, $file));
        }
        return $a;
    }

    public function updateFile(array $criteria, array &$a, array $options = array())
    {
        if ($this->eventManager->hasListeners(CollectionEvents::preUpdateFile)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preUpdateFile, new CollectionEventArgs($this, $a));
        }

        $fileName = $this->class->fieldMappings[$this->class->file]['fieldName'];
        $file = isset($a[$this->cmd.'set'][$fileName]) ? $a[$this->cmd.'set'][$fileName] : null;
        unset($a[$this->cmd.'set'][$fileName]);

        // Has file to be persisted
        if (isset($file) && $file->isDirty()) {
            // It is impossible to update a file on the grid so we have to remove it and
            // persist a new file with the same data

            // First do a find and remove query to remove the file metadata and chunks so
            // we can restore the file below
            $document = $this->findAndRemove($criteria, $options);
            unset(
                $document['filename'],
                $document['length'],
                $document['chunkSize'],
                $document['uploadDate'],
                $document['md5'],
                $document['file']
            );

            // Store the file
            $this->storeFile($file, $document);
        }

        // Now send the original update bringing the file up to date
        if ($a) {
            if ($this->loggerCallable) {
                $this->log(array(
                    'updating' => true,
                    'file' => true,
                    'id' => $id,
                    'set' => $a
                ));
            }
            $this->mongoCollection->update($criteria, $a, $options);
        }

        if ($this->eventManager->hasListeners(CollectionEvents::postUpdateFile)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postUpdateFile, new CollectionEventArgs($this, $file));
        }

        return $a;
    }

    /** @override */
    public function update(array $criteria, array $newObj, array $options = array())
    {
        $criteria = $this->prepareQuery($criteria);

        if ($this->eventManager->hasListeners(CollectionEvents::preUpdate)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preUpdate, new CollectionUpdateEventArgs($this, $criteria, $newObj, $options));
        }

        if ($this->loggerCallable) {
            $this->log(array(
                'update' => true,
                'criteria' => $criteria,
                'newObj' => $newObj,
                'options' => $options
            ));
        }
        if ($this->class->isFile()) {
            $result = $this->updateFile($criteria, $newObj, $options);
        } else {
            $result = $this->mongoCollection->update($criteria, $newObj, $options);
        }

        if ($this->eventManager->hasListeners(CollectionEvents::postUpdate)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postUpdate, new CollectionEventArgs($this, $result));
        }

        return $result;
    }

    /** @override */
    public function find(array $query = array(), array $fields = array())
    {
        if ($this->class->hasDiscriminator() && ! isset($query[$this->class->discriminatorField['name']])) {
            $discriminatorValues = $this->getClassDiscriminatorValues($this->class);
            $query[$this->class->discriminatorField['name']] = array('$in' => $discriminatorValues);
        }

        $query = $this->prepareQuery($query);

        if ($this->eventManager->hasListeners(CollectionEvents::preFind)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preFind, new CollectionEventArgs($this, $query));
        }

        if ($this->loggerCallable) {
            $this->log(array(
                'find' => true,
                'query' => $query,
                'fields' => $fields
            ));
        }
        $result = $this->mongoCollection->find($query, $fields);

        if ($this->eventManager->hasListeners(CollectionEvents::postFind)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postFind, new CollectionEventArgs($this, $result));
        }

        return $result;
    }

    /** @override */
    public function findOne(array $query = array(), array $fields = array())
    {
        if ($this->class->hasDiscriminator() && ! isset($query[$this->class->discriminatorField['name']])) {
            $discriminatorValues = $this->getClassDiscriminatorValues($this->class);
            $query[$this->class->discriminatorField['name']] = array('$in' => $discriminatorValues);
        }

        $query = $this->prepareQuery($query);

        if ($this->eventManager->hasListeners(CollectionEvents::preFindOne)) {
            $this->eventManager->dispatchEvent(CollectionEvents::preFindOne, new CollectionEventArgs($this, $query));
        }

        if ($this->loggerCallable) {
            $this->log(array(
                'findOne' => true,
                'query' => $query,
                'fields' => $fields
            ));
        }

        if ($this->mongoCollection instanceof \MongoGridFS) {
            if (($result = $this->mongoCollection->findOne($query)) !== null) {
                $data = $result->file;
                $data[$this->class->file] = $result;
                $result = $data;
            }
        } else {
            $result = $this->mongoCollection->findOne($query, $fields);
        }

        if ($this->eventManager->hasListeners(CollectionEvents::postFindOne)) {
            $this->eventManager->dispatchEvent(CollectionEvents::postFindOne, new CollectionEventArgs($this, $result));
        }

        return $result;
    }

    public function findAndRemove(array $query, array $options = array())
    {
        $command = array();
        $command['findandmodify'] = $this->getName();
        $command['query'] = $query;
        $command['remove'] = true;
        $result = $this->db->command($command);
        if (isset($result['value'])) {
            $document = $result['value'];
            if ($this->class->isFile()) {
                // Remove the file data from the chunks collection
                $this->mongoCollection->chunks->remove(array('files_id' => $document['_id']), $options);
            }
            return $document;
        }
        return null;
    }

    public function findAndModify(array $query, array $newObj, array $options = array())
    {
        $command = array();
        $command['findandmodify'] = $this->mongoCollection->getName();
        $command['query'] = $query;
        $command['update'] = $newObj;
        if (isset($options['upsert'])) {
            $command['upsert'] = true;
        }
        if (isset($options['new'])) {
            $command['new'] = true;
        }
        $result = $this->db->command($command);
        return $result['value'];
    }

    private function storeFile(MongoGridFSFile $file, array &$document)
    {
        if ($file->hasUnpersistedFile()) {
            $filename = $file->getFilename();
            if ($this->loggerCallable) {
                $this->log(array(
                    'storing' => true,
                    'file' => $file,
                    'document' => $document
                ));
            }
            $id = $this->mongoCollection->storeFile($filename, $document);
        } else {
            $bytes = $file->getBytes();
            if ($this->loggerCallable) {
                $this->log(array(
                    'storing' => true,
                    'bytes' => true,
                    'document' => $document
                ));
            }
            $id = $this->mongoCollection->storeBytes($bytes, $document);
        }
        $file->setMongoGridFSFile(new \MongoGridFSFile($this->mongoCollection, $document));
        return $file;
    }

    /** @proxy */
    public function count(array $query = array(), $limit = 0, $skip = 0)
    {
        return $this->mongoCollection->count($query, $limit, $skip);
    }

    /** @proxy */
    public function createDBRef(array $a)
    {
        return $this->mongoCollection->createDBRef($a);
    }

    /** @proxy */
    public function deleteIndex($keys)
    {
        return $this->mongoCollection->deleteIndex($keys);
    }

    /** @proxy */
    public function deleteIndexes()
    {
        return $this->mongoCollection->deleteIndexes();
    }

    /** @proxy */
    public function drop()
    {
        return $this->mongoCollection->drop();
    }

    /** @proxy */
    public function ensureIndex(array $keys, array $options)
    {
        return $this->mongoCollection->ensureIndex($keys, $options);
    }

    /** @proxy */
    public function __get($name)
    {
        return $this->mongoCollection->__get($name);
    }

    /** @proxy */
    public function getDBRef(array $ref)
    {
        return $this->mongoCollection->getDBRef($ref);
    }

    /** @proxy */
    public function getIndexInfo()
    {
        return $this->mongoCollection->getIndexInfo();
    }

    /** @proxy */
    public function getName()
    {
        return $this->mongoCollection->getName();
    }

    /** @proxy */
    public function group($keys, array $initial, $reduce, array $options = array())
    {
        return $this->mongoCollection->group($keys, $initial, $reduce, $options);
    }

    /** @proxy */
    public function insert(array $a, array $options = array())
    {
        return $this->mongoCollection->insert($a, $options);
    }

    /** @proxy */
    public function remove(array $criteria, array $options = array())
    {
        return $this->mongoCollection->remove($criteria, $options);
    }

    /** @proxy */
    public function save(array $a, array $options = array())
    {
        return $this->mongoCollection->save($a, $options);
    }

    /** @proxy */
    public function validate($scanData = false)
    {
        return $this->mongoCollection->validate($scanData);
    }

    /** @proxy */
    public function __toString()
    {
        return $this->mongoCollection->__toString();
    }

    private function getClassDiscriminatorValues(ClassMetadata $metadata)
    {
        $discriminatorValues = array($metadata->discriminatorValue);
        foreach ($metadata->subClasses as $className) {
            if ($key = array_search($className, $metadata->discriminatorMap)) {
                $discriminatorValues[] = $key;
            }
        }
        return $discriminatorValues;
    }

    /**
     * Prepare where values converting document object field names to the document collection
     * field name.
     *
     * @param string $fieldName
     * @param string $value
     * @return string $value
     */
    private function prepareWhereValue(&$fieldName, $value)
    {
        if (strpos($fieldName, '.') !== false) {
            $e = explode('.', $fieldName);

            $mapping = $this->class->getFieldMapping($e[0]);

            if ($this->class->hasField($e[0])) {
                $name = $this->class->fieldMappings[$e[0]]['name'];
                if ($name !== $e[0]) {
                    $e[0] = $name;
                }
            }

            if (isset($mapping['targetDocument'])) {
                $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
                if ($targetClass->hasField($e[1]) && $targetClass->identifier === $e[1]) {
                    $fieldName = $e[0] . '.$id';
                    $value = $targetClass->getDatabaseIdentifierValue($value);
                } elseif ($e[1] === '$id') {
                    $value = $targetClass->getDatabaseIdentifierValue($value);
                }
            }
        } elseif ($this->class->hasField($fieldName) && ! $this->class->isIdentifier($fieldName)) {
            $name = $this->class->fieldMappings[$fieldName]['name'];
            if ($name !== $fieldName) {
                $fieldName = $name;
            }
        } else {
            if ($fieldName === $this->class->identifier || $fieldName === '_id') {
                $fieldName = '_id';
                if (is_array($value)) {
                    if (isset($value[$this->cmd.'in'])) {
                        foreach ($value[$this->cmd.'in'] as $k => $v) {
                            $value[$this->cmd.'in'][$k] = $this->class->getDatabaseIdentifierValue($v);
                        }
                    } else {
                        foreach ($value as $k => $v) {
                            $value[$k] = $this->class->getDatabaseIdentifierValue($v);
                        }
                    }
                } else {
                    $value = $this->class->getDatabaseIdentifierValue($value);
                }
            }
        }
        return $value;
    }

    private function prepareQuery(array $query)
    {
        $newQuery = array();
        foreach ($query as $key => $value) {
            $value = $this->prepareWhereValue($key, $value);
            $newQuery[$key] = $value;
        }
        return $newQuery;
    }
}