<?php

namespace Doctrine\ODM\MongoDB\Tools;

use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * ResolveTargetDocumentListener
 *
 * Mechanism to overwrite document interfaces or classes specified as association targets.
 */
class ResolveTargetDocumentListener
{
    /**
     * @var array
     */
    private $resolveTargetDocuments = array();

    /**
     * Add a target-document class name to resolve to a new class name.
     *
     * @param string $originalDocument
     * @param string $newDocument
     * @param array $mapping
     * @return void
     */
    public function addResolveTargetDocument($originalDocument, $newDocument, array $mapping)
    {
        $mapping['targetDocument'] = ltrim($newDocument, "\\");
        $this->resolveTargetDocuments[ltrim($originalDocument, "\\")] = $mapping;
    }

    /**
     * Process event and resolve new target document names.
     *
     * @param LoadClassMetadataEventArgs $args
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $cm = $args->getClassMetadata();
        foreach ($cm->associationMappings as $mapping) {
            if (isset($this->resolveTargetDocuments[$mapping['targetDocument']])) {
                $this->remapAssociation($cm, $mapping);
            }
        }
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array $mapping
     */
    private function remapAssociation(ClassMetadata $classMetadata, array $mapping)
    {
        $newMapping = $this->resolveTargetDocuments[$mapping['targetDocument']];
        $newMapping = array_replace_recursive($mapping, $newMapping);
        $newMapping['fieldName'] = $mapping['fieldName'];

        // clear reference case of duplicate exception
        unset($classMetadata->fieldMappings[$mapping['fieldName']]);
        unset($classMetadata->associationMappings[$mapping['fieldName']]);

        switch ($mapping['association']) {
            case ClassMetadata::REFERENCE_ONE:
                $classMetadata->mapOneReference($newMapping);
                break;
            case ClassMetadata::REFERENCE_MANY:
                $classMetadata->mapManyReference($newMapping);
                break;
            case ClassMetadata::EMBED_ONE:
                $classMetadata->mapOneEmbedded($newMapping);
                break;
            case ClassMetadata::EMBED_MANY:
                $classMetadata->mapManyEmbedded($newMapping);
                break;
        }
    }
}
