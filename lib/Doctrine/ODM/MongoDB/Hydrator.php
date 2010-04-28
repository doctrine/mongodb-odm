<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class Hydrator
{
    private $_em;
    private $_hints = array();

    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
    }

    public function hint($hint)
    {
        $this->_hints[$hint] = $hint;
    }

    public function getHints()
    {
        return $this->_hints;
    }

    public function hydrate(ClassMetadata $metadata, $entity, $data)
    {
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($data[$mapping['fieldName']]) && isset($mapping['embedded'])) {
                $embeddedMetadata = $this->_em->getClassMetadata($mapping['targetEntity']);
                $embeddedEntity = $embeddedMetadata->newInstance();
                if ($mapping['type'] === 'many') {
                    $documents = array();
                    foreach ($data[$mapping['fieldName']] as $key => $document) {
                        $documents[$key] = $this->hydrate($embeddedMetadata, clone $embeddedEntity, $document);
                    }
                    $metadata->setFieldValue($entity, $mapping['fieldName'], $documents);
                } else {
                    $metadata->setFieldValue($entity, $mapping['fieldName'], $this->hydrate($embeddedMetadata, clone $embeddedEntity, $data[$mapping['fieldName']]));
                }
            } else if (isset($data[$mapping['fieldName']])) {
                $metadata->setFieldValue($entity, $mapping['fieldName'], $data[$mapping['fieldName']]);
            }
            if (isset($mapping['reference']) && isset($this->_hints['load_association_' . $mapping['fieldName']])) {
                $this->_em->loadEntityAssociation($entity, $mapping['fieldName']);
            }
        }
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($entity, (string) $data['_id']);
        }
        return $entity;
    }
}