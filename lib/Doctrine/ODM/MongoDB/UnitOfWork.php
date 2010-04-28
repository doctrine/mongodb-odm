<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\EntityManager,
    Doctrine\ODM\MongoDB\CommitOrderCalculator;

class UnitOfWork
{
    const STATE_MANAGED = 1;
    const STATE_NEW = 2;
    const STATE_DETACHED = 3;
    const STATE_REMOVED = 4;

    private $_em;
    private $_hydrator;
    private $_originalEntityData = array();
    private $_entityStates = array();
    private $_entityInsertions = array();
    private $_entityUpdates = array();
    private $_entityDeletions = array();
    private $_identityMap = array();

    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_hydrator = $em->getHydrator();
        $this->_commitOrderCalculator = new CommitOrderCalculator();
    }

    public function getOrCreateEntity($className, array $data = array())
    {
        $class = $this->_em->getClassMetadata($className);
        
        $id = isset($data['_id']) ? (string) $data['_id'] : null;
        if ($id && isset($this->_identityMap[$id])) {
            $entity = $this->_identityMap[$id];
        } else {
            $entity = $class->newInstance();
            $oid = spl_object_hash($entity);
            $this->_entityStates[$oid] = self::STATE_MANAGED;
            $this->_originalEntityData[$oid] = $data;
            $this->_identityMap[$id] = $entity;
        }
        $this->_hydrator->hydrate($class, $entity, $data);
        return $entity;
    }

    public function getEntityState($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityStates[$oid])) {
            if ($this->_em->getClassMetadata(get_class($entity))->getIdentifierValue($entity)) {
                $this->_entityStates[$oid] = self::STATE_DETACHED;
            } else {
                $this->_entityStates[$oid] = self::STATE_NEW;
            }
        }
        return $this->_entityStates[$oid];
    }

    public function detach($entity)
    {
    }

    public function refresh($entity)
    {
        $className = get_class($entity);
        $result = $this->_em->findByID($className, $this->_em->getClassMetadata($className)->getIdentifierValue($entity));
        if ($result === false) {
            throw new \InvalidArgumentException('Could not refresh entity because it does not exist anymore.');
        }
    }

    public function persist($entity)
    {
        $visited = array();
        $this->_doPersist($entity, $visited);
    }

    public function _doPersist($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $entity;

        $state = $this->getEntityState($entity);
        switch ($state) {
            case self::STATE_NEW:
                $this->_entityStates[$oid] = self::STATE_MANAGED;
                $this->_entityInsertions[$oid] = $entity;
                break;
            case self::STATE_REMOVED:
                unset($this->_entityDeletions[$oid]);
                break;
        }
        
        $this->_cascadePersist($entity, $visited);
    }

    public function _cascadePersist($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['reference'])) {
                continue;
            }

            $relatedEntities = $class->reflFields[$mapping['fieldName']]->getValue($entity);
            if ( ! $relatedEntities || (is_array($relatedEntities) && isset($relatedEntities['$ref']))) {
                continue;
            }

            if (is_array($relatedEntities)) {
                foreach ($relatedEntities as $entity) {
                    if (is_array($entity) && isset($entity['$ref'])) {
                        continue;
                    }
                    $this->_doPersist($entity, $visited);
                }
            } else {
                $this->_doPersist($relatedEntities, $visited);
            }
        }
    }

    public function remove($entity)
    {
        $visited = array();
        $this->_doRemove($entity, $visited);
    }

    private function _doRemove($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $entity;

        $state = $this->getEntityState($entity);
        switch ($state) {
            case self::STATE_MANAGED:
                $this->_entityDeletions[$oid] = $entity;
                break;
        }

        $this->_cascadeRemove($entity, $visited);
    }

    private function _cascadeRemove($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->fieldMappings as $mapping) {
            if ( ! isset($mapping['reference'])) {
                continue;
            }
            if ( ! isset($mapping['cascadeDelete'])) {
                continue;
            }

            $relatedEntities = $class->reflFields[$mapping['fieldName']]->getValue($entity);
            if ( ! $relatedEntities || (is_array($relatedEntities) && isset($relatedEntities['$ref']))) {
                continue;
            }

            if (is_array($relatedEntities)) {
                foreach ($relatedEntities as $entity) {
                    if (isset($entity['$ref'])) {
                        continue;
                    }
                    $this->_doRemove($entity, $visited);
                }
            } else {
                $this->_doRemove($relatedEntities, $visited);
            }
        }
    }

    public function commit()
    {
        $this->computeChangeSets();

        if ( ! ($this->_entityInsertions ||
                $this->_entityDeletions ||
                $this->_entityUpdates)) {
            return; // Nothing to do.
        }

        $this->_executeInsertions();
        $this->_executeUpdates();
        $this->_executeDeletions();

        $this->_entityInsertions =
        $this->_entityUpdates =
        $this->_entityDeletions = array();
    }

    public function computeChangeSets()
    {
        foreach ($this->_identityMap as $id => $entity) {
            $oid = spl_object_hash($entity);
            $class = $this->_em->getClassMetadata(get_class($entity));
            $state = $this->getEntityState($entity);
            if (isset($this->_entityDeletions[$oid])) {
                continue;
            }
            if ( ! isset($this->_originalEntityData[$oid])) {
                continue;
            }
            $originalData = $this->_originalEntityData[$oid];
            $changed = false;
            foreach ($originalData as $key => $value) {
                if ($key === '_id') {
                    continue;
                }
                if ($value !== $class->getFieldValue($entity, $key)) {
                    $changed = true;
                }
            }
            if ($state === self::STATE_MANAGED && $changed === true) {
                $this->_entityUpdates[$oid] = $entity;
            }
            $this->persist($entity);
        }
    }

    private function _buildFieldValuesForSave($entity)
    {
        $metadata = $this->_em->getClassMetadata(get_class($entity));
        $values = array();
        foreach ($metadata->fieldMappings as $field => $mapping) {
            if (isset($mapping['id'])) {
                continue;
            }

            $reflProp = $metadata->reflFields[$field];
            $value = $reflProp->getValue($entity);
            if ( ! $value) {
                continue;
            }

            if (isset($mapping['embeddedDocument'])) {
                if ($mapping['type'] === 'many') {
                    $values[$field] = array();
                    foreach ($value as $key => $document) {
                        $values[$field][$key] = $this->_buildFieldValuesForSave($document);
                    }
                } else {
                    $values[$field] = $this->_buildFieldValuesForSave($value);
                }
            } else if (isset($mapping['reference'])) {
                if ($mapping['type'] === 'one') {
                    if (is_object($value)) {
                        $value = $this->_buildFieldValuesForSave($value);
                    }
                    $ref = $this->_em->getEntityCollection($mapping['targetEntity'])->createDBRef($value);
                    $values[$field] = $ref;
                } else {
                    foreach ($value as $v) {
                        $ref = $this->_em->getEntityCollection($mapping['targetEntity'])->createDBRef($this->_buildFieldValuesForSave($v));
                        $values[$field][] = $ref;
                    }
                }
            } else {
                $values[$field] = $value;
            }
        }
        if ($metadata->identifier) {
            if ($id = $metadata->getIdentifierObject($entity)) {
                $values['_id'] = $id;
            }
        }
        return $values;
    }

    private function _executeInsertions()
    {
        if ( ! $this->_entityInsertions) {
            return;
        }

        $classes = array();
        $insertions = array();
        foreach ($this->_entityInsertions as $oid => $entity) {
            $className = get_class($entity);
            $insertions[$className][$oid] = $entity;
            $class = $this->_em->getClassMetadata($className);
            $this->_commitOrderCalculator->addClass($class);
            $classes[] = $class;
        }

        foreach ($classes as $class) {
            foreach ($class->fieldMappings as $mapping) {
                 if ( ! isset($mapping['reference'])) {
                     continue;
                 }
                 $targetClass = $this->_em->getClassMetadata($mapping['targetEntity']);
                 $this->_commitOrderCalculator->addClass($targetClass);
                 $this->_commitOrderCalculator->addDependency($targetClass, $class);
             }
        }

        $order = $this->_commitOrderCalculator->getCommitOrder();

        foreach ($order as $class) {
            if ( ! isset($insertions[$class->name])) {
                continue;
            }
            $entities = $insertions[$class->name];
            $collection = $this->_em->getEntityCollection($class->name);
            $metadata = $this->_em->getClassMetadata($class->name);

            $inserts = array();
            foreach ($entities as $oid => $entity) {
                $values = $this->_buildFieldValuesForSave($entity);
                $inserts[$oid] = $values;
                $this->_originalEntityData[$oid] = $values;
            }
            $collection->batchInsert($inserts);
            foreach ($inserts as $oid => $values) {
                $entity = $insertions[$class->name][$oid];
                $id = (string) $values['_id'];
                $metadata->setIdentifierValue($entity, $id);
                $this->_identityMap[$id] = $entity;
            }
        }

        $this->_commitOrderCalculator->clear();
    }

    private function _executeUpdates()
    {
        foreach ($this->_entityUpdates as $oid => $entity) {
            $className = get_class($entity);
            $metadata = $this->_em->getClassMetadata($className);
            $collection = $this->_em->getEntityCollection($className);
            $values = $this->_buildFieldValuesForSave($entity);
            $collection->save($values);
        }
    }

    private function _executeDeletions()
    {
        foreach ($this->_entityDeletions as $oid => $entity) {
            $className = get_class($entity);
            $metadata = $this->_em->getClassMetadata($className);
            $collection = $this->_em->getEntityCollection($className);
            $collection->remove(
                array('_id' => $metadata->getIdentifierObject($entity)),
                array('justOne' => true)
            );
        }
    }

    public function clear()
    {
        $this->_identityMap =
        $this->_originalEntityData =
        $this->_entityStates =
        $this->_entityInsertions =
        $this->_entityUpdates =
        $this->_entityDeletions = array();
        $this->_commitOrderCalculator->clear();
    }
}