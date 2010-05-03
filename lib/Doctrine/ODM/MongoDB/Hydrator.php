<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

class Hydrator
{
    private $_dm;

    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
    }

    public function hydrate(ClassMetadata $metadata, $document, $data)
    {
        $values = array();
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($data[$mapping['fieldName']]) && isset($mapping['embedded'])) {
                $embeddedMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $embeddedDocument = $embeddedMetadata->newInstance();
                if ($mapping['type'] === 'many') {
                    $documents = new ArrayCollection();
                    foreach ($data[$mapping['fieldName']] as $docArray) {
                        $doc = clone $embeddedDocument;
                        $this->hydrate($embeddedMetadata, $doc, $docArray);
                        $documents->add($doc);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                    $value = $documents;
                } else {
                    $value = clone $embeddedDocument;
                    $this->hydrate($embeddedMetadata, $value, $data[$mapping['fieldName']]);
                    $metadata->setFieldValue($document, $mapping['fieldName'], $value);
                }
            } else if (isset($data[$mapping['fieldName']])) {
                $value = $data[$mapping['fieldName']];
                $metadata->setFieldValue($document, $mapping['fieldName'], $value);
            }
            if (isset($mapping['reference'])) {
                $targetMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $targetDocument = $targetMetadata->newInstance();
                $value = isset($data[$mapping['fieldName']]) ? $data[$mapping['fieldName']] : null;
                if ($mapping['type'] === 'one' && isset($value['$id'])) {
                    $id = (string) $value['$id'];
                    $proxy = $this->_dm->getReference($mapping['targetDocument'], $id);
                    $metadata->setFieldValue($document, $mapping['fieldName'], $proxy);
                } else if ($mapping['type'] === 'many' && (is_array($value) || $value instanceof Collection)) {
                    $documents = new PersistentCollection($this->_dm, $targetMetadata, new ArrayCollection());
                    $documents->setInitialized(false);
                    foreach ($value as $v) {
                        $id = (string) $v['$id'];
                        $proxy = $this->_dm->getReference($mapping['targetDocument'], $id);
                        $documents->add($proxy);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                }
            }
            if (isset($value)) {
                $values[$mapping['fieldName']] = $value;
            }
        }
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, (string) $data['_id']);
        }
        return $values;
    }
}