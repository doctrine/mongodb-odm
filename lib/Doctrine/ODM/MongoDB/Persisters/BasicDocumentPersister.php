<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\MongoCursor,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\Collection;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicDocumentPersister
{
    protected $_dm;
    protected $_uow;
    protected $_class;
    protected $_collection;
    protected $_documentName;
    protected $_documentIdentifiers = array();
    protected $_queuedInserts = array();
    public function __construct(DocumentManager $dm, ClassMetadata $class)
    {
        $this->_dm = $dm;
        $this->_uow = $dm->getUnitOfWork();
        $this->_class = $class;
        $this->_documentName = $class->getName();
        $this->_collection = $dm->getDocumentCollection($class->name);
    }
    public function addInsert($document)
    {
        $this->_queuedInserts[spl_object_hash($document)] = $document;
    }
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $inserts = array();

        foreach ($this->_queuedInserts as $oid => $document) {
            $data = $this->prepareUpdateData($document);
            if ( ! isset ($data['$set'])) {
                continue;
            }
            $inserts[$oid] = $data['$set'];
            if (isset ($data['$pushAll'])) {
                foreach ($data['$pushAll'] as $fieldName => $value) {
                    $inserts[$oid][$fieldName] = $value;
                }
            }
        }
        if (empty ($inserts)) {
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
            $this->_collection->update(array('_id' => new \MongoId($id)), $update);
        }
    }

    public function delete($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);
        $this->_collection->remove(array('_id' => new \MongoId($id)));
    }

    public function prepareUpdateData($document)
    {
        $oid = spl_object_hash($document);
        $changeset = $this->_uow->getDocumentChangeSet($document);
        $result = array();
        foreach ($this->_class->fieldMappings as $mapping) {
            $old = isset ($changeset[$mapping['fieldName']][0]) ? $changeset[$mapping['fieldName']][0] : null;
            $new = isset ($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;
            $changeset[$mapping['fieldName']] = array();
            if (isset($mapping['reference'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    if ( ! isset ($old)) {
                        $result['$pushAll'][$mapping['fieldName']] = array();
                    }
                    $new = $this->_prepareDocReferenceArray($new, $targetClass);
                    $old = $this->_prepareDocReferenceArray($old, $targetClass);
                    $this->_tryMarkChangedArray($mapping['fieldName'], $new, $old, $result);
                } else {
                    if (isset ($new)) {
                        $doc = $new;
                        $ref = $this->_prepareDocReference($targetClass, $doc);
                        unset ($doc);
                        if (isset($ref)) {
                            $new = $ref;
                        }
                    }
                    if (isset ($old) && is_object($old)) {
                        $old = $this->_prepareDocReference($targetClass, $old);
                    }
                    $this->_tryMarkChanged($mapping['fieldName'], $new, $old, $result);
                }
            } elseif (isset($mapping['embedded'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many') {
                    if ( ! isset ($old)) {
                        $result['$pushAll'][$mapping['fieldName']] = array();
                    }
                    $new = $this->_prepareDocEmbeddedArray($new, $targetClass);
                    $old = $this->_prepareDocEmbeddedArray($old, $targetClass);
                    $this->_tryMarkChangedArray($mapping['fieldName'], $new, $old, $result);
                } else {
                    if (isset ($new)) {
                        $new = $this->_prepareDocEmbeded($targetClass, $new);
                    }
                    if (isset ($old) && is_object($old)) {
                        $old = $this->_prepareDocEmbeded($targetClass, $old);
                    }
                    $this->_tryMarkChanged($mapping['fieldName'], $new, $old, $result);
                }
           } else {
                if (isset ($new)) {
                    $new = Type::getType($mapping['type'])->convertToDatabaseValue($new);
                }
                if (isset ($old) && ! (is_object($new) && $old instanceof $new)) {
                    $old = Type::getType($mapping['type'])->convertToDatabaseValue($old);
                }
                if ($new != $old) {
                    if (isset ($new)) {
                        $result['$set'][$mapping['fieldName']] = $new;
                    } else {
                        $result['$unset'][$mapping['fieldName']] = true;
                    }
                }
            }
        }
        
        return $result;
    }

    protected function _tryMarkChanged($fieldName, $new, $old, array &$result)
    {
        if ($new != $old) {
            if (isset ($new)) {
                $result['$set'][$fieldName] = $new;
            } else {
                $result['$unset'][$fieldName] = true;
            }
        }
    }

    protected function _tryMarkChangedArray($fieldName, array $new, array $old, array &$result)
    {
        foreach ($old as $val) {
            if ( ! in_array($val, $new)) {
                $result['$pullAll'][$fieldName][] = $val;
            }
        }
        foreach ($new as $val) {
            if ( ! in_array($val, $old)) {
                $result['$pushAll'][$fieldName][] = $val;
            }
        }
    }

    protected function _prepareDocReferenceArray($val, $targetClass)
    {
        if (isset ($val)) {
            $coll = $val;
            $val = array();
            foreach ($coll as $doc) {
                if (is_object($doc)) {
                    $doc = $this->_prepareDocReference($targetClass, $doc);
                }
                $val[] = $doc;
            }
            unset ($coll);
        } else {
            $val = array();
        }
        return $val;
    }

    protected function _prepareDocEmbeddedArray($val, $targetClass)
    {
        if (isset ($val)) {
            $coll = $val;
            $val = array();
            foreach ($coll as $doc) {
                if (is_object($doc)) {
                    $doc = $this->_prepareDocEmbeded($targetClass, $doc);
                }
                $val[] = $doc;
            }
            unset ($docs);
        } else {
            $val = array();
        }
        return $val;
    }

    /**
     * Gets the ClassMetadata instance of the entity class this persister is used for.
     *
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }
    public function refresh($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);
        $this->_dm->loadByID($this->_class->name, $id);
    }

    /**
     * Loads an entity by a list of field criteria.
     *
     * @param array $query The criteria by which to load the entity.
     * @param object $document The entity to load the data into. If not specified,
     *        a new entity is created.
     * @param $assoc The association that connects the entity to load to another entity, if any.
     * @param array $hints Hints for entity creation.
     * @return object The loaded and managed entity instance or NULL if the entity can not be found.
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

    public function loadById($id)
    {
        $result = $this->_collection->findOne(array('_id' => new \MongoId($id)));
        if ($result !== null) {
            return $this->_uow->getOrCreateDocument($this->_documentName, $result);
        }
        return null;
    }

    /**
     * Loads a list of entities by a list of field criteria.
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
     * returns the reference representation to be stored in mongodb
     * or null if not applicable
     * @param ClassMetadata $class
     * @param Document $doc
     * @return array|null
     */
    private function _prepareDocReference($class, $doc)
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
     * prepares array of values to be stored in mongo
     * to represent embedded object
     * @param ClassMetadata $class
     * @param Document $doc
     * @return array
     */
    private function _prepareDocEmbeded($class, $doc)
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