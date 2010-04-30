<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\CommitOrderCalculator;

class UnitOfWork
{
    const STATE_MANAGED = 1;
    const STATE_NEW = 2;
    const STATE_DETACHED = 3;
    const STATE_REMOVED = 4;

    private $_em;
    private $_hydrator;
    private $_originalDocumentData = array();
    private $_documentStates = array();
    private $_documentInsertions = array();
    private $_documentUpdates = array();
    private $_documentDeletions = array();
    private $_identityMap = array();

    public function __construct(DocumentManager $em)
    {
        $this->_dm = $em;
        $this->_hydrator = $em->getHydrator();
        $this->_commitOrderCalculator = new CommitOrderCalculator();
    }

    public function getOrCreateDocument($className, array $data = array(), array $hints = array())
    {
        $class = $this->_dm->getClassMetadata($className);
        
        $id = isset($data['_id']) ? (string) $data['_id'] : null;
        if ($id && isset($this->_identityMap[$className][$id])) {
            $document = $this->_identityMap[$className][$id];
            $overrideLocalValues = isset($hints[Query::HINT_REFRESH]) ? true : false;
        } else {
            $document = $class->newInstance();
            $oid = spl_object_hash($document);
            $this->_documentStates[$oid] = self::STATE_MANAGED;
            $this->_originalDocumentData[$oid] = $data;
            $this->_identityMap[$className][$id] = $document;
            $overrideLocalValues = true;
        }
        if ($overrideLocalValues === true) {
            $this->_hydrator->hydrate($class, $document, $data);
        }
        return $document;
    }

    public function getDocumentState($document, $assume = null)
    {
        $oid = spl_object_hash($document);
        if ( ! isset($this->_documentStates[$oid])) {
            if ($assume === null) {
                if ($this->_dm->getClassMetadata(get_class($document))->getIdentifierValue($document)) {
                    $this->_documentStates[$oid] = self::STATE_DETACHED;
                } else {
                    $this->_documentStates[$oid] = self::STATE_NEW;
                }
            } else {
                $this->_documentStates[$oid] = $assume;
            }
        }
        return $this->_documentStates[$oid];
    }

    public function detach($document)
    {
        $visited = array();
        $this->_doDetach($document, $visited);
    }

    public function removeFromIdentityMap($document)
    {
        $oid = spl_object_hash($document);
        $class = $this->_dm->getClassMetadata(get_class($document));
        $id = $class->getIdentifierValue($document);
        
        if ( ! $id) {
            throw new \InvalidArgumentException('The given document has no id.');
        }
        $className = $class->name;
        if (isset($this->_identityMap[$className][$id])) {
            unset($this->_identityMap[$className][$id]);
            $this->_documentStates[$oid] = self::STATE_DETACHED;
            return true;
        }

        return false;
    }

    private function _doDetach($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $document;

        switch ($this->getDocumentState($document, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->removeFromIdentityMap($document);
                unset(
                    $this->_documentInsertions[$oid],
                    $this->_documentUpdates[$oid],
                    $this->_documentDeletions[$oid],
                    $this->_documentIdentifiers[$oid],
                    $this->_documentStates[$oid],
                    $this->_originalDocumentData[$oid]
                );
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }
        
        $this->_cascadeDetach($document, $visited);
    }

    private function _cascadeDetach($document, array &$visited)
    {
        $class = $this->_dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['reference'])) {
                continue;
            }

            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ( ! $relatedDocuments || (is_array($relatedDocuments) && isset($relatedDocuments['$ref']))) {
                continue;
            }

            if (is_array($relatedDocuments)) {
                foreach ($relatedDocuments as $document) {
                    if (is_array($document) && isset($document['$ref'])) {
                        continue;
                    }
                    $this->_doDetach($document, $visited);
                }
            } else {
                $this->_doDetach($relatedDocuments, $visited);
            }
        }
    }


    public function refresh($document)
    {
        $className = get_class($document);
        $class = $this->_dm->getClassMetadata($className);
        $result = $this->_dm->createQuery($className)
            ->where($class->identifier, $class->getIdentifierValue($document))
            ->refresh()
            ->getSingleResult();
        if ($result === false) {
            throw new \InvalidArgumentException('Could not refresh document because it does not exist anymore.');
        }
    }

    public function persist($document)
    {
        $visited = array();
        $this->_doPersist($document, $visited);
    }

    public function _doPersist($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $document;

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                $this->_documentStates[$oid] = self::STATE_MANAGED;
                $this->_documentInsertions[$oid] = $document;
                break;
            case self::STATE_REMOVED:
                unset($this->_documentDeletions[$oid]);
                break;
        }
        
        $this->_cascadePersist($document, $visited);
    }

    public function _cascadePersist($document, array &$visited)
    {
        $class = $this->_dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['reference'])) {
                continue;
            }

            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ( ! $relatedDocuments || (is_array($relatedDocuments) && isset($relatedDocuments['$ref']))) {
                continue;
            }

            if (is_array($relatedDocuments)) {
                foreach ($relatedDocuments as $document) {
                    if (is_array($document) && isset($document['$ref'])) {
                        continue;
                    }
                    $this->_doPersist($document, $visited);
                }
            } else {
                $this->_doPersist($relatedDocuments, $visited);
            }
        }
    }

    public function remove($document)
    {
        $visited = array();
        $this->_doRemove($document, $visited);
    }

    private function _doRemove($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $document;

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_MANAGED:
                $this->_documentDeletions[$oid] = $document;
                break;
        }

        $this->_cascadeRemove($document, $visited);
    }

    private function _cascadeRemove($document, array &$visited)
    {
        $class = $this->_dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['reference'])) {
                continue;
            }
            if ( ! isset($mapping['cascadeDelete'])) {
                continue;
            }

            $relatedDocuments = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ( ! $relatedDocuments || (is_array($relatedDocuments) && isset($relatedDocuments['$ref']))) {
                continue;
            }

            if (is_array($relatedDocuments)) {
                foreach ($relatedDocuments as $document) {
                    if (isset($document['$ref'])) {
                        continue;
                    }
                    $this->_doRemove($document, $visited);
                }
            } else {
                $this->_doRemove($relatedDocuments, $visited);
            }
        }
    }

    public function commit()
    {
        $this->computeChangeSets();

        if ( ! ($this->_documentInsertions ||
                $this->_documentDeletions ||
                $this->_documentUpdates)) {
            return; // Nothing to do.
        }

        $this->_executeInsertions();
        $this->_executeUpdates();
        $this->_executeDeletions();

        $this->_documentInsertions =
        $this->_documentUpdates =
        $this->_documentDeletions = array();
    }

    public function computeChangeSets()
    {
        foreach ($this->_identityMap as $className => $documents) {
            $class = $this->_dm->getClassMetadata($className);
            foreach ($documents as $id => $document) {
                $oid = spl_object_hash($document);
                $state = $this->getDocumentState($document);
                if (isset($this->_documentDeletions[$oid])) {
                    continue;
                }
                if ( ! isset($this->_originalDocumentData[$oid])) {
                    continue;
                }
                $originalData = $this->_originalDocumentData[$oid];
                $changed = false;
                foreach ($originalData as $key => $value) {
                    if ($key === '_id') {
                        continue;
                    }
                    if ($value !== $class->getFieldValue($document, $key)) {
                        $changed = true;
                    }
                }
                if ($state === self::STATE_MANAGED && $changed === true) {
                    $this->_documentUpdates[$oid] = $document;
                }
                $this->persist($document);
            }
        }
    }

    private function _buildFieldValuesForSave($document)
    {
        $metadata = $this->_dm->getClassMetadata(get_class($document));
        $values = array();
        foreach ($metadata->fieldMappings as $field => $mapping) {
            if (isset($mapping['id'])) {
                continue;
            }

            $reflProp = $metadata->reflFields[$field];
            $value = $reflProp->getValue($document);
            if ( ! $value) {
                continue;
            }

            if (isset($mapping['embedded'])) {
                if ($mapping['type'] === 'many') {
                    $values[$field] = array();
                    foreach ($value as $v) {
                        $values[$field][] = $this->_buildFieldValuesForSave($v);
                    }
                } else {
                    $values[$field] = $this->_buildFieldValuesForSave($value);
                }
            } else if (isset($mapping['reference'])) {
                if ($mapping['type'] === 'one') {
                    if (is_object($value)) {
                        $value = $this->_buildFieldValuesForSave($value);
                    }
                    $ref = $this->_dm->getDocumentCollection($mapping['targetDocument'])->createDBRef($value);
                    $values[$field] = $ref;
                } else {
                    $collection = $this->_dm->getDocumentCollection($mapping['targetDocument']);
                    foreach ($value as $v) {
                        $ref = $collection->createDBRef($this->_buildFieldValuesForSave($v));
                        $values[$field][] = $ref;
                    }
                }
            } else {
                $values[$field] = $value;
            }
        }
        if ($metadata->identifier) {
            if ($id = $metadata->getIdentifierObject($document)) {
                $values['_id'] = $id;
            }
        }
        return $values;
    }

    private function _executeInsertions()
    {
        if ( ! $this->_documentInsertions) {
            return;
        }

        $classes = array();
        $insertions = array();
        foreach ($this->_documentInsertions as $oid => $document) {
            $className = get_class($document);
            $insertions[$className][$oid] = $document;
            $class = $this->_dm->getClassMetadata($className);
            $this->_commitOrderCalculator->addClass($class);
            $classes[] = $class;
        }

        foreach ($classes as $class) {
            foreach ($class->fieldMappings as $mapping) {
                 if ( ! isset($mapping['reference'])) {
                     continue;
                 }
                 $targetClass = $this->_dm->getClassMetadata($mapping['targetDocument']);
                 $this->_commitOrderCalculator->addClass($targetClass);
                 $this->_commitOrderCalculator->addDependency($targetClass, $class);
             }
        }

        $order = $this->_commitOrderCalculator->getCommitOrder();

        foreach ($order as $class) {
            if ( ! isset($insertions[$class->name])) {
                continue;
            }
            $documents = $insertions[$class->name];
            $collection = $this->_dm->getDocumentCollection($class->name);

            $inserts = array();
            foreach ($documents as $oid => $document) {
                $values = $this->_buildFieldValuesForSave($document);
                $inserts[$oid] = $values;
                $this->_originalDocumentData[$oid] = $values;
            }
            $collection->batchInsert($inserts);
            foreach ($inserts as $oid => $values) {
                $document = $insertions[$class->name][$oid];
                $id = (string) $values['_id'];
                $class->setIdentifierValue($document, $id);
                $this->_identityMap[$class->name][$id] = $document;
            }
        }

        $this->_commitOrderCalculator->clear();
    }

    private function _executeUpdates()
    {
        foreach ($this->_documentUpdates as $oid => $document) {
            $className = get_class($document);
            $metadata = $this->_dm->getClassMetadata($className);
            $collection = $this->_dm->getDocumentCollection($className);
            $values = $this->_buildFieldValuesForSave($document);
            $collection->save($values);
        }
    }

    private function _executeDeletions()
    {
        foreach ($this->_documentDeletions as $oid => $document) {
            $className = get_class($document);
            $metadata = $this->_dm->getClassMetadata($className);
            $collection = $this->_dm->getDocumentCollection($className);
            $collection->remove(
                array('_id' => $metadata->getIdentifierObject($document)),
                array('justOne' => true)
            );
        }
    }

    public function clear()
    {
        $this->_identityMap =
        $this->_originalDocumentData =
        $this->_documentStates =
        $this->_documentInsertions =
        $this->_documentUpdates =
        $this->_documentDeletions = array();
        $this->_commitOrderCalculator->clear();
    }
}