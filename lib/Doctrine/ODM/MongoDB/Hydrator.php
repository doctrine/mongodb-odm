<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class Hydrator
{
    private $_em;
    private $_hints = array();

    public function __construct(DocumentManager $em)
    {
        $this->_dm = $em;
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
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($data[$mapping['fieldName']]) && isset($mapping['embedded'])) {
                $embeddedMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $embeddedDocument = $embeddedMetadata->newInstance();
                if ($mapping['type'] === 'many') {
                    $documents = array();
                    foreach ($data[$mapping['fieldName']] as $key => $doc) {
                        $documents[$key] = $this->hydrate($embeddedMetadata, clone $embeddedDocument, $doc);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                } else {
                    $metadata->setFieldValue($document, $mapping['fieldName'], $this->hydrate($embeddedMetadata, clone $embeddedDocument, $data[$mapping['fieldName']]));
                }
            } else if (isset($data[$mapping['fieldName']])) {
                $metadata->setFieldValue($document, $mapping['fieldName'], $data[$mapping['fieldName']]);
            }
            if (isset($mapping['reference']) && isset($this->_hints['load_association_' . $mapping['fieldName']])) {
                $this->_dm->loadDocumentAssociation($document, $mapping['fieldName']);
            }
        }
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, (string) $data['_id']);
        }
        return $document;
    }
}