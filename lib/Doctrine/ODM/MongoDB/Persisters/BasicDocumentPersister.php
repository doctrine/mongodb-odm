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
            $data = $this->_prepareInsertData($document);
            $inserts[$oid] = $data;
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
        $update = $this->_prepareUpdateData($document);
        $id = $update['_id'];
        unset($update['_id']);

        $this->_collection->update(array('_id' => $id), array('$set' => $update));
    }
    public function delete($document)
    {
        $id = $this->_uow->getDocumentIdentifier($document);
        $this->_collection->remove(array('_id' => new \MongoId($id)));
    }

    private function _prepareInsertData($document)
    {
        return $this->_prepareUpdateData($document);
    }
    private function _prepareUpdateData($document)
    {
        $oid = spl_object_hash($document);
        $changeset = $this->_uow->getDocumentChangeSet($document);
        foreach ($changeset as $fieldName => $values) {
            $changeset[$fieldName] = $values[1];
        }
        $docId = $this->_uow->getDocumentIdentifier($document);
        if ($docId) {
            $changeset['_id'] = new \MongoId($docId);
        }
        foreach ($this->_class->fieldMappings as $mapping) {
            if (isset($mapping['reference'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if ($mapping['type'] === 'many' && isset($changeset[$mapping['fieldName']])) {
                    $coll = $changeset[$mapping['fieldName']];
                    $changeset[$mapping['fieldName']] = array();
                    foreach ($coll as $key => $doc) {
                        $ref = $this->_prepareDocReference($targetClass, $doc);
                        if (isset($ref)) {
                            $changeset[$mapping['fieldName']][] = $ref;
                        }
                    }
                } elseif (isset($changeset[$mapping['fieldName']])) {
                    $doc = $changeset[$mapping['fieldName']];
                    $ref = $this->_prepareDocReference($targetClass, $doc);
                    if (isset($ref)) {
                        $changeset[$mapping['fieldName']] = $ref;
                    }
                }
            } elseif (isset($mapping['embedded'])) {
                $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                if (isset($changeset[$mapping['fieldName']])) {
                    $doc = $changeset[$mapping['fieldName']];
                    $changeset[$mapping['fieldName']] = $this->_prepareDocEmbeded($targetClass, $doc);
                }
            } elseif (isset($changeset[$mapping['fieldName']])) {
                $changeset[$mapping['fieldName']] = Type::getType($mapping['type'])->convertToDatabaseValue($changeset[$mapping['fieldName']]);
            }
        }
        return $changeset;
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
    public function refresh(array $id, $document)
    {
        
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
        if (is_array($doc) || $doc instanceof Collection) {
            foreach ($doc as $val) {
                $changeset[] = $this->_prepareDocEmbeded($class, $val);
            }
        } else {
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
        }
        return $changeset;
    }

}
