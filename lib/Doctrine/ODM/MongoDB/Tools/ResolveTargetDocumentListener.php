<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

use function array_key_exists;
use function array_replace_recursive;
use function assert;
use function ltrim;

/**
 * ResolveTargetDocumentListener
 *
 * Mechanism to overwrite document interfaces or classes specified as association targets.
 *
 * @phpstan-import-type AssociationFieldMapping from ClassMetadata
 */
class ResolveTargetDocumentListener implements EventSubscriber
{
    /** @var array<class-string, array{targetDocument: class-string}> */
    private array $resolveTargetDocuments = [];

    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
            Events::onClassMetadataNotFound,
        ];
    }

    /**
     * Add a target-document class name to resolve to a new class name.
     *
     * @param array{targetDocument?: class-string} $mapping
     */
    public function addResolveTargetDocument(string $originalDocument, string $newDocument, array $mapping): void
    {
        $mapping['targetDocument']                                                = $this->getRealClassName($newDocument);
        $this->resolveTargetDocuments[$this->getRealClassName($originalDocument)] = $mapping;
    }

    /** @return class-string */
    private function getRealClassName(string $className): string
    {
        return ltrim($className, '\\');
    }

    /**
     * @internal this is an event callback, and should not be called directly
     *
     * @return void
     */
    public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $args)
    {
        if (! array_key_exists($args->getClassName(), $this->resolveTargetDocuments)) {
            return;
        }

        $args->setFoundMetadata(
            $args
                ->getDocumentManager()
                ->getClassMetadata($this->resolveTargetDocuments[$args->getClassName()]['targetDocument']),
        );
    }

    /**
     * Process event and resolve new target document names.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $cm = $args->getClassMetadata();
        assert($cm instanceof ClassMetadata);
        foreach ($cm->associationMappings as $mapping) {
            if (! isset($this->resolveTargetDocuments[$mapping['targetDocument']])) {
                continue;
            }

            $this->remapAssociation($cm, $mapping);
        }
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     * @phpstan-param AssociationFieldMapping $mapping
     */
    private function remapAssociation(ClassMetadata $classMetadata, array $mapping): void
    {
        $newMapping              = $this->resolveTargetDocuments[$mapping['targetDocument']];
        $newMapping              = array_replace_recursive($mapping, $newMapping);
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
