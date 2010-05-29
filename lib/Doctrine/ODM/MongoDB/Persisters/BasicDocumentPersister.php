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

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\MongoCursor,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\Collection;

/**
 * The BasicDocumentPersister is responsible for actual persisting the calculated
 * changesets performed by the UnitOfWork.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 4930 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicDocumentPersister
{
    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $_dm;

    /**
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $_uow;

    /**
     * The ClassMetadata instance for the document type being persisted.
     *
     * @var Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $_class;

    /**
     * The MongoCollection instance for this document.
     *
     * @var Doctrine\ODM\MongoDB\MongoCollection
     */
    private $_collection;

    /**
     * The string document name being persisted.
     *
     * @var string
     */
    private $_documentName;

    /**
     * Array of quered inserts for the persister to insert.
     *
     * @var array
     */
    private $_queuedInserts = array();

    /**
     * Initializes a new BasicDocumentPersister instance.
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $class
     */
    public function __construct(DocumentManager $dm, ClassMetadata $class)
    {
        $this->_dm = $dm;
        $this->_uow = $dm->getUnitOfWork();
        $this->_class = $class;
        $this->_documentName = $class->getName();
        $this->_collection = $dm->getDocumentCollection($class->name);
    }

    /**
     * Adds a document to the queued insertions.
     * The document remains queued until {@link executeInserts} is invoked.
     *
     * @param object $document The document to queue for insertion.
     */
    public function addInsert($document)
    {
        $this->_queuedInserts[spl_object_hash($document)] = $document;
    }

    /**
     * Executes all queued document insertions and returns any generated post-insert
     * identifiers that were created as a result of the insertions.
     *
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @return array An array of any generated post-insert IDs. This will be an empty array
     *               if the document class does not use the IDENTITY generation strategy.
     */
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $inserts = array();

        foreach ($this->_queuedInserts as $oid => $document) {
            $data = $this->prepareInsertData($document);
            if ( ! $data) {
                continue;
            }
            $inserts[$oid] = $data;
        }
        if (empty($inserts)) {
            return;
        }
        $this->_collection->batchInsert($inserts);

        foreach ($inserts as $oid => $data) {
            $document = $this->_queuedInserts[$oid];
            $postInsertIds[(string) $data['_id']] = $document;
            if ($this->_class->isFile()) {
                $this->_dm->getHydrator()->hydrate($this->_class, $document, $data);
            }
        }

        return $postInsertIds;
    }

    public function update($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);

        $update = $this->prepareUpdateData($document);
        if ( ! empty($update)) {
            /**
             * temporary fix for @link http://jira.mongodb.org/browse/SERVER-1050
             * atomic modifiers $pushAll and $pullAll, $push, $pop and $pull
             * are not allowed on the same field in one update
             */
            $id = new \MongoId($id);
            if (isset ($update['$pushAll']) && isset ($update['$pullAll'])) {
                $fields = array_intersect(
                    array_keys($update['$pushAll']),
                    array_keys($update['$pullAll'])
                );
                if ( ! empty ($fields)) {
                    $tempUpdate = array();
                    foreach ($fields as $field) {
                        $tempUpdate[$field] = $update['$pullAll'][$field];
                        unset ($update['$pullAll'][$field]);
                    }
                    if (empty ($update['$pullAll'])) {
                        unset ($update['$pullAll']);
                    }
                    $this->_collection->update(array('_id' => $id), array(
                        '$pullAll' => $tempUpdate
                    ));
                }
            }
            $this->_collection->update(array('_id' => $id), $update);
        }
    }

    public function delete($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);
        $this->_collection->remove(array('_id' => new \MongoId($id)));
    }

    public function prepareInsertData($document)
    {
        $oid = spl_object_hash($document);
        $changeset = $this->_uow->getDocumentChangeSet($document);
        $result = array();
        foreach ($this->_class->fieldMappings as $mapping) {
            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;
            if ($new === null) {
                continue;
            }
            $changeset[$mapping['fieldName']] = array();
            if (isset($mapping['reference'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    $result[$mapping['fieldName']] = $this->_prepareDocReferenceArray($new, $targetClass);
                } else {
                    $result[$mapping['fieldName']] = $this->_prepareDocReference($targetClass, $new);
                }
            } elseif (isset($mapping['embedded'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    $result[$mapping['fieldName']] = $this->_prepareDocEmbeddedArray($new, $targetClass);
                } else {
                    $result[$mapping['fieldName']] = $this->_prepareDocEmbeded($targetClass, $new);
                }
            } else {
                $result[$mapping['fieldName']] = Type::getType($mapping['type'])->convertToDatabaseValue($new);
            }
        }

        return $result;
    }

    public function prepareUpdateData($document)
    {
        $oid = spl_object_hash($document);
        $changeset = $this->_uow->getDocumentChangeSet($document);
        $result = array();
        foreach ($this->_class->fieldMappings as $mapping) {
            $old = isset($changeset[$mapping['fieldName']][0]) ? $changeset[$mapping['fieldName']][0] : null;
            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;
            $changeset[$mapping['fieldName']] = array();
            if (isset($mapping['reference'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    if ( ! isset($old)) {
                        $result['$pushAll'][$mapping['fieldName']] = array();
                    }
                    $new = $this->_prepareDocReferenceArray($new, $targetClass);
                    $old = $this->_prepareDocReferenceArray($old, $targetClass);
                    $this->_addArrayUpdateAtomicOperator($mapping, $new, $old, $result);
                } else {
                    if (isset($new)) {
                        $doc = $new;
                        $ref = $this->_prepareDocReference($targetClass, $doc);
                        unset($doc);
                        if (isset($ref)) {
                            $new = $ref;
                        }
                    }
                    if (isset($old) && is_object($old)) {
                        $old = $this->_prepareDocReference($targetClass, $old);
                    }
                    $this->_addFieldUpdateAtomicOperator($mapping, $new, $old, $result);
                }
            } elseif (isset($mapping['embedded'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    if ( ! isset($old)) {
                        $result['$pushAll'][$mapping['fieldName']] = array();
                    }
                    $new = $this->_prepareDocEmbeddedArray($new, $targetClass);
                    $old = $this->_prepareDocEmbeddedArray($old, $targetClass);
                    $this->_addArrayUpdateAtomicOperator($mapping, $new, $old, $result);
                } else {
                    if (isset($new)) {
                        $new = $this->_prepareDocEmbeded($targetClass, $new);
                    }
                    if (isset($old) && is_object($old)) {
                        $old = $this->_prepareDocEmbeded($targetClass, $old);
                    }
                    $this->_addFieldUpdateAtomicOperator($mapping, $new, $old, $result);
                }
//            @todo the below doesn't work, need to find out why
            } elseif ($mapping['type'] === 'collection') {
                $this->_addArrayUpdateAtomicOperator($mapping, (array) $new, (array) $old, $result);
            } else {
                if (isset($new)) {
                    $new = Type::getType($mapping['type'])->convertToDatabaseValue($new);
                }
                if (isset($old) && ! (is_object($new) && $old instanceof $new)) {
                    $old = Type::getType($mapping['type'])->convertToDatabaseValue($old);
                }
                $this->_addFieldUpdateAtomicOperator($mapping, $new, $old, $result);
            }
        }
        
        return $result;
    }

    /**
     * Gets the ClassMetadata instance of the document class this persister is used for.
     *
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Refreshes a managed document.
     *
     * @param object $document The document to refresh.
     */
    public function refresh($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);
        $this->_dm->loadByID($this->_class->name, $id);
    }

    /**
     * Loads an document by a list of field criteria.
     *
     * @param array $query The criteria by which to load the document.
     * @param object $document The document to load the data into. If not specified,
     *        a new document is created.
     * @param $assoc The association that connects the document to load to another document, if any.
     * @param array $hints Hints for document creation.
     * @return object The loaded and managed document instance or NULL if the document can not be found.
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load(array $query = array(), array $select = array())
    {
        $result = $this->_collection->findOne($query, $select);
        if ($result !== null) {
            return $this->_uow->getOrCreateDocument($this->_documentName, $result);
        }
        return null;
    }

    /**
     * Lood document by its identifier.
     *
     * @param string $id
     * @return object|null
     */
    public function loadById($id)
    {
        $result = $this->_collection->findOne(array('_id' => new \MongoId($id)));
        if ($result !== null) {
            return $this->_uow->getOrCreateDocument($this->_documentName, $result);
        }
        return null;
    }

    /**
     * Loads a list of documents by a list of field criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $query = array(), array $select = array())
    {
        $cursor = $this->_collection->find($query, $select);
        return new MongoCursor($this->_dm, $this->_dm->getHydrator(), $this->_class, $cursor);
    }

    /**
     * Add the atomic operator to update or remove a field from a document
     * based on whether or not the value has changed.
     *
     * @param string $fieldName
     * @param string $new
     * @param string $old
     * @param string $result
     */
    private function _addFieldUpdateAtomicOperator(array $mapping, $new, $old, array &$result)
    {
        if ($this->_equals($old, $new)) {
            return;
        }

        if ($mapping['type'] === 'increment') {
            $result['$inc'][$mapping['fieldName']] = $new - $old;
        } else {
            if (isset($new)) {
                $result['$set'][$mapping['fieldName']] = $new;
            } else {
                $result['$unset'][$mapping['fieldName']] = true;
            }
        }
    }

    /**
     * Add the atomic operator to add new values to an array and to remove values
     * from an array.
     *
     * @param string $fieldName
     * @param array $new
     * @param array $old
     * @param string $result
     */
    private function _addArrayUpdateAtomicOperator(array $mapping, array $new, array $old, array &$result)
    {
        foreach ($old as $val) {
            if ( ! in_array($val, $new)) {
                $result['$pullAll'][$mapping['fieldName']][] = $val;
            }
        }
        foreach ($new as $val) {
            if ( ! in_array($val, $old)) {
                $result['$pushAll'][$mapping['fieldName']][] = $val;
            }
        }
    }

    /**
     * Performs value comparison
     * @param mixed $old
     * @param mixed $new
     */
    private function _equals($old, $new)
    {
        $old = is_scalar($old) ? $old : $this->_getScalar($old);
        $new = is_scalar($new) ? $new : $this->_getScalar($new);
        return $new === $old;
    }

    /**
     * Converts value for comparison
     * @param mixed $val
     * @todo refactor it to use \Type, to get rid of hard-coded conversion
     */
    public function _getScalar($val)
    {
        if ($val instanceof \MongoDate) {
            return $val->sec;
        } else if ($val instanceof \DateTime) {
            return $val->getTimestamp();
        } else if ($val instanceof \MongoBinData) {
            return $val->bin;
        } else if (($val instanceof \MongoId) || ($val instanceof \MongoTimestamp)) {
            return (string) $val;
        } else if ($val instanceof \MongoMaxKey) {
            return 1;
        } else if ($val instanceof \MongoMinKey) {
            return 0;
        } else {
            return $val;
        }
    }

    /**
     * Prepare array of document references for persistence.
     *
     * @param PersistentCollection $val
     * @param ClassMetadata $targetClass
     */
    private function _prepareDocReferenceArray($val, ClassMetadata $targetClass)
    {
        if (isset($val)) {
            $coll = $val;
            $val = array();
            foreach ($coll as $doc) {
                if (is_object($doc)) {
                    $doc = $this->_prepareDocReference($targetClass, $doc);
                }
                $val[] = $doc;
            }
            unset($coll);
        } else {
            $val = array();
        }
        return $val;
    }

    /**
     * Prepare array of embedded documents for persistence.
     *
     * @param PersistentCollection $val 
     * @param ClassMetadata $targetClass
     */
    private function _prepareDocEmbeddedArray($val, ClassMetadata $targetClass)
    {
        if (isset($val)) {
            $coll = $val;
            $val = array();
            foreach ($coll as $doc) {
                if (is_object($doc)) {
                    $doc = $this->_prepareDocEmbeded($targetClass, $doc);
                }
                $val[] = $doc;
            }
            unset($coll);
        } else {
            $val = array();
        }
        return $val;
    }

    /**
     * Returns the reference representation to be stored in mongodb or null if not applicable.
     *
     * @param ClassMetadata $class
     * @param Document $doc
     * @return array|null
     */
    private function _prepareDocReference(ClassMetadata$class, $doc)
    {
        $id = $this->_uow->getDocumentIdentifier($doc);
        $ref = array(
            '$ref' => $class->getCollection(),
            '$id' => $id,
            '$db' => $class->getDB()
        );
        return $ref;
    }

    /**
     * Prepares array of values to be stored in mongo to represent embedded object.
     *
     * @param ClassMetadata $class
     * @param Document $doc
     * @return array
     */
    private function _prepareDocEmbeded(ClassMetadata $class, $doc)
    {
        $changeset = array();
        foreach ($class->fieldMappings as $mapping) {
            $rawValue = $class->getFieldValue($doc, $mapping['fieldName']);
            if ( ! isset($rawValue)) {
                continue;
            }
            if (isset($mapping['embedded']) || isset($mapping['reference'])) {
                $classMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if (isset($mapping['embedded'])) {
                    if ($mapping['type'] == 'many') {
                        $value = array();
                        foreach ($rawValue as $doc) {
                            $value[] = $this->_prepareDocEmbeded($classMetadata, $doc);
                        }
                    } elseif ($mapping['type'] == 'one') {
                        $value = $this->_prepareDocEmbeded($classMetadata, $rawValue);
                    }
                } elseif (isset($mapping['reference'])) {
                    if ($mapping['type'] == 'many') {
                         $value = array();
                        foreach ($rawValue as $doc) {
                            $value[] = $this->_prepareDocReference($classMetadata, $doc);
                        }
                    } else {
                        $value = $this->_prepareDocReference($classMetadata, $rawValue);
                    }
                }
            } else {
                $value = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);
            }
            $changeset[$mapping['fieldName']] = $value;
        }
        return $changeset;
    }
}