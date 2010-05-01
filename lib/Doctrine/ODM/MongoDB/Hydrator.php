<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection;

class Hydrator
{
    private $_dm;
    private $_hints = array();

    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
    }

    public function hint($hint)
    {
        $this->_hints[$hint] = $hint;
    }

    public function getHints()
    {
        return $this->_hints;
    }

    public function hydrate(ClassMetadata $metadata, $document, $data)
    {
        $values = array();
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($data[$mapping['name']]) && isset($mapping['embedded'])) {
                $embeddedMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $embeddedDocument = $embeddedMetadata->newInstance();
                if ($mapping['type'] === 'many') {
                    $documents = new ArrayCollection();
                    foreach ($data[$mapping['name']] as $docArray) {
                        $doc = clone $embeddedDocument;
                        $this->hydrate($embeddedMetadata, $doc, $docArray);
                        $documents->add($doc);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                    $value = $documents;
                } else {
                    $value = clone $embeddedDocument;
                    $this->hydrate($embeddedMetadata, $value, $data[$mapping['name']]);
                    $metadata->setFieldValue($document, $mapping['fieldName'], $value);
                }
            } else if (isset($data[$mapping['name']])) {
                $value = $data[$mapping['name']];
                $metadata->setFieldValue($document, $mapping['fieldName'], $value);
            }
            if (isset($mapping['reference']) && isset($this->_hints['load_association_' . $mapping['fieldName']])) {
                $this->_dm->loadDocumentAssociation($document, $mapping['fieldName']);
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