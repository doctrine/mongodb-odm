<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type;

class DataPreparer
{
    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

    /**
     * Initializes a new DataPreparer instance.
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\ODM\MongoDB\UnitOfWork $uow
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, $cmd)
    {
        $this->dm = $dm;
        $this->uow = $uow;
        $this->cmd = $cmd;
    }

    /**
     * Prepares insert data for document
     *
     * @param mixed $document
     * @return array
     */
    public function prepareInsertData($document)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);
        $insertData = array();
        foreach ($class->fieldMappings as $mapping) {
            if ($mapping['type'] === 'many') {
                continue;
            }
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;
            if ($class->isIdentifier($mapping['fieldName'])) {
                if ($new === null) {
                    $new = new \MongoId();
                }
                $insertData['_id'] = $this->prepareValue($mapping, $new);
                continue;
            }
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }
            $value = $this->prepareValue($mapping, $new);
            if ($value === null && $mapping['nullable'] === false) {
                continue;
            }

            $insertData[$mapping['name']] = $value;
            if (isset($mapping['reference'])) {
                $scheduleForUpdate = false;
                if ($mapping['type'] === 'one') {
                    if ( ! isset($insertData[$mapping['name']][$this->cmd . 'id'])) {
                        $scheduleForUpdate = true;
                    }
                }
                if ($scheduleForUpdate) {
                    unset($insertData[$mapping['name']]);
                    $this->uow->scheduleExtraUpdate($document, array(
                        $mapping['fieldName'] => array(null, $new)
                    ));
                }
            }
        }
        // add discriminator if the class has one
        if ($class->hasDiscriminator()) {
            $insertData[$class->discriminatorField['name']] = $class->discriminatorValue;
        }
        return $insertData;
    }

    /**
     * Prepares update array for document, using atomic operators
     *
     * @param mixed $document
     * @return array
     */
    public function prepareUpdateData($document)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);
        $result = array();
        foreach ($changeset as $fieldName => $change) {
            $mapping = $class->fieldMappings[$fieldName];
            if (isset($mapping['reference']) && $mapping['type'] === 'many') {
                continue;
            }
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
            list($old, $new) = $change;

            if (isset($mapping['embedded']) && $mapping['type'] === 'one') {
                // If we have a new embedded document then lets set it
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $result[$this->cmd . 'set'][$mapping['name']] = $this->prepareEmbeddedDocValue($mapping, $new);
                // If we don't have a new value then lets unset the embedded document
                } else if ( ! $new) {
                    $result[$this->cmd . 'unset'][$mapping['name']] = true;
                // Update existing embedded document
                } else {
                    $update = $this->prepareUpdateData($new);
                    foreach ($update as $cmd => $values) {
                        foreach ($values as $key => $value) {
                            $result[$cmd][$mapping['name'] . '.' . $key] = $value;
                        }
                    }
                }
            } else if (isset($mapping['embedded']) && $mapping['type'] === 'many') {
                foreach ($new as $key => $embeddedDoc) {
                    if (!$this->uow->isScheduledForInsert($embeddedDoc)) {
                        $update = $this->prepareUpdateData($embeddedDoc);
                        foreach ($update as $cmd => $values) {
                            foreach ($values as $name => $value) {
                                $result[$cmd][$mapping['name'] . '.' . $key . '.' . $name] = $value;
                            }
                        }
                    }
                }
            } else if ($mapping['type'] === 'increment') {
                $new = $this->prepareValue($mapping, $new);
                $old = $this->prepareValue($mapping, $old);
                if ($new >= $old) {
                    $result[$this->cmd . 'inc'][$mapping['name']] = $new - $old;
                } else {
                    $result[$this->cmd . 'inc'][$mapping['name']] = ($old - $new) * -1;
                }
            } else {
                $new = $this->prepareValue($mapping, $new);
                if (isset($new) || $mapping['nullable'] === true) {
                    $result[$this->cmd . 'set'][$mapping['name']] = $new;
                } else {
                    $result[$this->cmd . 'unset'][$mapping['name']] = true;
                }
            }
        }
        return $result;
    }

    /**
     *
     * @param array $mapping
     * @param mixed $value
     */
    public function prepareValue(array $mapping, $value)
    {
        if ($value === null) {
            return null;
        }
        if ($mapping['type'] === 'many') {
            $prepared = array();
            $oneMapping = $mapping;
            $oneMapping['type'] = 'one';
            foreach ($value as $rawValue) {
                $prepared[] = $this->prepareValue($oneMapping, $rawValue);
            }
            if (empty($prepared)) {
                $prepared = null;
            }
        } elseif (isset($mapping['reference']) || isset($mapping['embedded'])) {
            if (isset($mapping['embedded'])) {
                $prepared = $this->prepareEmbeddedDocValue($mapping, $value);
            } elseif (isset($mapping['reference'])) {
                $prepared = $this->prepareReferencedDocValue($mapping, $value);
            }
        } else {
            $prepared = Type::getType($mapping['type'])->convertToDatabaseValue($value);
        }
        return $prepared;
    }

    /**
     * Returns the reference representation to be stored in mongodb or null if not applicable.
     *
     * @param array $referenceMapping
     * @param Document $document
     * @return array|null
     */
    public function prepareReferencedDocValue(array $referenceMapping, $document)
    {
        $id = null;
        if (is_array($document)) {
            $className = $referenceMapping['targetDocument'];
        } else {
            $className = get_class($document);
            $id = $this->uow->getDocumentIdentifier($document);
        }
        $class = $this->dm->getClassMetadata($className);
        if (null !== $id) {
            $id = $class->getDatabaseIdentifierValue($id);
        }
        $ref = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id' => $id,
            $this->cmd . 'db' => $class->getDB()
        );
        if ( ! isset($referenceMapping['targetDocument'])) {
            $discriminatorField = isset($referenceMapping['discriminatorField']) ? $referenceMapping['discriminatorField'] : '_doctrine_class_name';
            $discriminatorValue = isset($referenceMapping['discriminatorMap']) ? array_search($class->getName(), $referenceMapping['discriminatorMap']) : $class->getName();
            $ref[$discriminatorField] = $discriminatorValue;
        }
        return $ref;
    }

    /**
     * Prepares array of values to be stored in mongo to represent embedded object.
     *
     * @param array $embeddedMapping
     * @param Document $embeddedDocument
     * @return array
     */
    public function prepareEmbeddedDocValue(array $embeddedMapping, $embeddedDocument)
    {
        $className = get_class($embeddedDocument);
        $class = $this->dm->getClassMetadata($className);
        $embeddedDocumentValue = array();
        foreach ($class->fieldMappings as $mapping) {
            // Skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }

            $rawValue = $class->getFieldValue($embeddedDocument, $mapping['fieldName']);

            // Don't store null values unless nullable is specified
            if ($rawValue === null && $mapping['nullable'] === false) {
                continue;
            }
            if (isset($mapping['embedded']) || isset($mapping['reference'])) {
                if (isset($mapping['embedded'])) {
                    if ($mapping['type'] == 'many') {
                        /*
                        $value = array();
                        foreach ($rawValue as $embeddedDoc) {
                            $value[] = $this->prepareEmbeddedDocValue($mapping, $embeddedDoc);
                        }
                        if (empty($value)) {
                            $value = null;
                        }
                        */
                        $value = null;
                    } elseif ($mapping['type'] == 'one') {
                        $value = $this->prepareEmbeddedDocValue($mapping, $rawValue);
                    }
                } elseif (isset($mapping['reference'])) {
                    if ($mapping['type'] == 'many') {
                        $value = array();
                        foreach ($rawValue as $referencedDoc) {
                            $value[] = $this->prepareReferencedDocValue($mapping, $referencedDoc);
                        }
                        if (empty($value)) {
                            $value = null;
                        }
                    } else {
                        $value = $this->prepareReferencedDocValue($mapping, $rawValue);
                    }
                }
            } else {
                $value = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);
            }
            if ($value === null && $mapping['nullable'] === false) {
                continue;
            }
            $embeddedDocumentValue[$mapping['name']] = $value;
        }
        if ( ! isset($embeddedMapping['targetDocument'])) {
            $discriminatorField = isset($embeddedMapping['discriminatorField']) ? $embeddedMapping['discriminatorField'] : '_doctrine_class_name';
            $discriminatorValue = isset($embeddedMapping['discriminatorMap']) ? array_search($class->getName(), $embeddedMapping['discriminatorMap']) : $class->getName();
            $embeddedDocumentValue[$discriminatorField] = $discriminatorValue;
        }
        if (empty($embeddedDocumentValue)) {
            return (object) $embeddedDocumentValue;
        }
        return $embeddedDocumentValue;
    }
}