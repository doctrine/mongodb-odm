<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\Common\Util\Inflector;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated class was deprecated in 1.3 and will be removed in 2.0.
 */
class ConvertMappingToXmlCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('odm:mapping:dump-xml')
            ->setDescription('Converts mappings to the XML format. Available in 1.3 only.')
            ->addArgument('dest-path', InputArgument::REQUIRED, 'The path to generate your XML mappings.')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A string pattern used to match documents that should be processed.')
            ->addOption('ext', null, InputOption::VALUE_OPTIONAL, 'Extension for created files.', 'dcm.xml')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DocumentManager $dm */
        $dm = $this->getHelper('documentManager')->getDocumentManager();

        $metadatas = MetadataFilter::filter(
            $dm->getMetadataFactory()->getAllMetadata(),
            $input->getOption('filter')
        );
        $destPath = rtrim($input->getArgument('dest-path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (! count($metadatas)) {
            $output->writeln('No Metadata Classes to process.');
            return;
        }

        /** @var ClassMetadataInfo $metadata */
        foreach ($metadatas as $metadata) {
            $output->writeln(sprintf('Processing document "<info>%s</info>"', $metadata->name));
            $this->inspectDeprecations($metadata, $output);
            $xml = $this->export($metadata);
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($xml->asXML());
            $dom->formatOutput = true;
            file_put_contents(
                sprintf("%s%s.%s", $destPath, str_replace('\\', '.', $metadata->name), $input->getOption('ext')),
                $dom->saveXML()
            );
        }

        $output->writeln(PHP_EOL . sprintf('XML mapping files generated to "<info>%s</info>"', $destPath));
    }

    private function inspectDeprecations(ClassMetadataInfo $metadata, OutputInterface $output)
    {
        if (! empty($metadata->requireIndexes)) {
            $output->writeln('<comment>!</comment> requireIndexes has been deprecated, omitting.');
        }
        if (! empty($metadata->slaveOkay)) {
            $output->writeln('<comment>!</comment> slaveOkay has been deprecated, omitting. Please specify read preference manually.');
        }
        foreach ($metadata->indexes as $index) {
            if (! empty($index['options']['partialFilterExpression'])) {
                $output->writeln('<comment>!</comment> Index\'s partialFilterExpression can not be converted automatically. Please do so manually.');
                break;
            }
        }
    }

    private function export(ClassMetadataInfo $metadata)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd" />');

        if ($metadata->isEmbeddedDocument) {
            $root = $xml->addChild('embedded-document');
        } elseif ($metadata->isMappedSuperclass) {
            $root = $xml->addChild('mapped-superclass');
        } elseif ($metadata->isQueryResultDocument) {
            $root = $xml->addChild('query-result-document');
        } else {
            $root = $xml->addChild('document');
        }
        $root->addAttribute('name', $metadata->name);

        $this->processDocumentLevelConfiguration($metadata, $root);

        if (! empty($metadata->lifecycleCallbacks)) {
            $callbacks = $root->addChild('lifecycle-callbacks');
            foreach ($metadata->lifecycleCallbacks as $event => $methods) {
                foreach ($methods as $method) {
                    $callback = $callbacks->addChild('lifecycle-callback');
                    $callback->addAttribute('method', $method);
                    $callback->addAttribute('type', $event);
                }
            }
        }
        if (! empty($metadata->alsoLoadMethods)) {
            $alsoLoadMethods = $root->addChild('also-load-methods');
            foreach ($metadata->alsoLoadMethods as $method => $fields) {
                $alsoLoad = $alsoLoadMethods->addChild('also-load-method');
                $alsoLoad->addAttribute('method', $method);
                $alsoLoad->addAttribute('field', $fields[0]);
            }
        }

        foreach ($metadata->fieldMappings as $mapping) {
            if (empty($mapping['association'])) {
                if (empty($mapping['id'])) {
                    $field = $root->addChild('field');
                } else {
                    $field = $root->addChild('id');
                }

                $this->processField($mapping, $field);
            } elseif ($mapping['association'] === ClassMetadataInfo::EMBED_ONE) {
                $field = $root->addChild('embed-one');
                $this->processEmbed($mapping, $field);
            } elseif ($mapping['association'] === ClassMetadataInfo::EMBED_MANY) {
                $field = $root->addChild('embed-many');
                $this->processEmbed($mapping, $field);
            } elseif ($mapping['association'] === ClassMetadataInfo::REFERENCE_ONE) {
                $field = $root->addChild('reference-one');
                $this->processReference($mapping, $field);
            } elseif ($mapping['association'] === ClassMetadataInfo::REFERENCE_MANY) {
                $field = $root->addChild('reference-many');
                $this->processReference($mapping, $field);
            }
        }

        return $xml;
    }

    private function processField(array $mapping, \SimpleXMLElement $field)
    {
        if (isset($mapping['fieldName']) && $mapping['fieldName'] === $mapping['name']) {
            unset($mapping['fieldName']);
        }
        $attributes = [
            'field-name', 'type',
        ];
        if (empty($mapping['id'])) {
            $attributes += ['name', 'file', 'distance', 'version', 'lock', 'nullable', 'not-saved'];
        }
        if (isset($mapping['strategy']) && $mapping['strategy'] !== 'set') {
            $attributes[] = 'strategy';
        }
        // indexes from fields will be moved with indexes in general, no need to do it here
        $this->copyAttributes($attributes, $mapping, $field);
        if (! empty($mapping['alsoLoadFields'])) {
            $field->addAttribute('also-load', join(',', $mapping['alsoLoadFields']));
        }
        if (! empty($mapping['id']) && ! empty($mapping['options'])) {
            foreach ($mapping['options'] as $name => $value) {
                $option = $field->addChild('id-generator-option');
                $option->addAttribute('name', $name);
                $option->addAttribute('value', $value);
            }
        }
    }

    private function processEmbed(array $mapping, \SimpleXMLElement $field)
    {
        $field->addAttribute('field', $mapping['name']);
        if (isset($mapping['fieldName']) && $mapping['fieldName'] === $mapping['name']) {
            unset($mapping['fieldName']);
        }
        $attributes = ['field-name', 'target-document', 'collection-class', 'not-saved'];
        if ($mapping['type'] === ClassMetadataInfo::MANY) {
            $attributes[] = 'strategy';
        }
        $this->copyAttributes($attributes, $mapping, $field);
        $this->processDiscriminatorSettings($mapping, $field);
        if (! empty($mapping['alsoLoadFields'])) {
            $field->addAttribute('also-load', join(',', $mapping['alsoLoadFields']));
        }
    }

    private function processReference(array $mapping, \SimpleXMLElement $field)
    {
        $field->addAttribute('field', $mapping['name']);
        if (isset($mapping['fieldName']) && $mapping['fieldName'] === $mapping['name']) {
            unset($mapping['fieldName']);
        }
        $attributes = [
            'field-name', 'target-document', 'collection-class', 'store-as', 'inversedBy',
            'mapped-by', 'repository-method', 'limit', 'skip', 'orphan-removal', 'not-saved',
        ];
        if ($mapping['type'] === ClassMetadataInfo::MANY) {
            $attributes[] = 'strategy';
        }
        $this->copyAttributes($attributes, $mapping, $field);
        $this->processDiscriminatorSettings($mapping, $field);
        if (! empty($mapping['alsoLoadFields'])) {
            $field->addAttribute('also-load', join(',', $mapping['alsoLoadFields']));
        }
        if (! empty($mapping['cascade'])) {
            $cascade = $field->addChild('cascade');
            foreach (count($mapping['cascade']) < 5 ? $mapping['cascade'] : ['all'] as $c) {
                $cascade->addChild($c);
            }
        }
        if (! empty($mapping['sort'])) {
            $sort = $field->addChild('sort');
            foreach ($mapping['sort'] as $f => $order) {
                $sortSort = $sort->addChild('sort');
                $sortSort->addAttribute('field', $field);
                $sortSort->addAttribute('order', $order);
            }
        }
        if (! empty($mapping['criteria'])) {
            $criteria = $field->addChild('criteria');
            foreach ($mapping['criteria'] as $f => $value) {
                $criteriaCriteria = $criteria->addChild('criteria');
                $criteriaCriteria->addAttribute('field', $field);
                $criteriaCriteria->addAttribute('value', $value);
            }
        }
        if (! empty($mapping['prime'])) {
            $prime = $field->addChild('prime');
            foreach ($mapping['prime'] as $primed) {
                $f = $prime->addChild('field');
                $f->addAttribute('name', $primed);
            }
        }
    }

    private function processDiscriminatorSettings(array $mapping, \SimpleXMLElement $field)
    {
        if (! empty($mapping['discriminatorField'])) {
            $discriminatorField = $field->addChild('discriminator-field');
            $discriminatorField->addAttribute('name', $mapping['discriminatorField']);
        }
        if (! empty($mapping['discriminatorMap'])) {
            $map = $field->addChild('discriminator-map');
            foreach ($mapping['discriminatorMap'] as $value => $class) {
                $dmapping = $map->addChild('discriminator-mapping');
                $dmapping->addAttribute('value', $value);
                $dmapping->addAttribute('class', $class);
            }
        }
        if (! empty($mapping['defaultDiscriminatorValue'])) {
            $defaultDiscriminator = $field->addChild('default-discriminator-value');
            $defaultDiscriminator->addAttribute('value', $mapping['defaultDiscriminatorValue']);
        }
    }

    private function copyAttributes(array $attributes, array $data, \SimpleXMLElement $xml)
    {
        foreach ($attributes as $attr) {
            $camelCase = Inflector::camelize($attr);
            if (! isset($data[$camelCase])) {
                continue;
            }
            if (is_bool($data[$camelCase])) {
                $xml->addAttribute($attr, $data[$camelCase] ? 'true' : 'false');
            } else {
                $xml->addAttribute($attr, $data[$camelCase]);
            }
        }
    }

    private function processDocumentLevelConfiguration(ClassMetadataInfo $metadata, \SimpleXMLElement $root)
    {
        // document level configuration
        if (! empty($metadata->customRepositoryClassName)) {
            $root->addAttribute('repository-class', $metadata->customRepositoryClassName);
        }
        if (! empty($metadata->db)) {
            $root->addAttribute('db', $metadata->db);
        }
        if (! empty($metadata->collection)) {
            $root->addAttribute('collection', $metadata->collection);
        }
        if (! empty($metadata->collectionCapped)) {
            $root->addAttribute('capped-collection', 'true');
            if (! empty($metadata->collectionMax)) {
                $root->addAttribute('capped-collection-max', $metadata->collectionMax);
            }
            if (! empty($metadata->collectionSize)) {
                $root->addAttribute('capped-collection-size', $metadata->collectionSize);
            }
        }
        if (! empty($metadata->writeConcern)) {
            $root->addAttribute('writeConcern', $metadata->writeConcern);
        }
        if ($metadata->inheritanceType !== ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $root->addAttribute(
                'inheritance-type',
                $metadata->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION
                    ? 'SINGLE_COLLECTION' : 'COLLECTION_PER_CLASS'
            );
        }
        if ($metadata->changeTrackingPolicy !== ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $root->addAttribute(
                'change-tracking-policy',
                $metadata->changeTrackingPolicy === ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT
                    ? 'DEFERRED_EXPLICIT' : 'NOTIFY'
            );
        }
        if (! empty($metadata->isReadOnly)) {
            $root->addAttribute('read-only', 'true');
        }
        if (! empty($metadata->discriminatorField)) {
            $discriminatorField = $root->addChild('discriminator-field');
            $discriminatorField->addAttribute('name', $metadata->discriminatorField);
        }
        if (! empty($metadata->discriminatorMap)) {
            $map = $root->addChild('discriminator-map');
            foreach ($metadata->discriminatorMap as $value => $class) {
                $mapping = $map->addChild('discriminator-mapping');
                $mapping->addAttribute('value', $value);
                $mapping->addAttribute('class', $class);
            }
        }
        if (! empty($metadata->defaultDiscriminatorValue)) {
            $defaultDiscriminator = $root->addChild('default-discriminator-value');
            $defaultDiscriminator->addAttribute('value', $metadata->defaultDiscriminatorValue);
        }
        if (! empty($metadata->shardKey)) {
            $shardKey = $root->addChild('shard-key');
            foreach ($metadata->shardKey['keys'] as $name => $order) {
                $key = $shardKey->addChild('key');
                $key->addAttribute('name', $name);
                $key->addAttribute('order', $order);
            }
            foreach ($metadata->shardKey['options'] as $name => $value) {
                $option = $shardKey->addChild('option');
                $option->addAttribute('name', $name);
                if (is_bool($value)) {
                    $option->addAttribute('value', $value ? 'true' : 'false');
                } else {
                    $option->addAttribute('value', $value);
                }
            }
        }
        if (! empty($metadata->readPreference)) {
            $readPreference = $root->addChild('read-preference');
            $readPreference->addAttribute('mode', $metadata->readPreference);
            if (! empty($metadata->readPreferenceTags)) {
                foreach ($metadata->readPreferenceTags as $tagSetConf) {
                    $tagSet = $readPreference->addChild('tag-set');
                    foreach ($tagSetConf as $name => $value) {
                        $tag = $tagSet->addChild('tag');
                        $tag->addAttribute('name', $name);
                        $tag->addAttribute('value', $value);
                    }
                }
            }
        }
        if (! empty($metadata->indexes)) {
            $indexes = $root->addChild('indexes');
            foreach ($metadata->indexes as $index) {
                $indexTag = $indexes->addChild('index');
                foreach ($index['keys'] as $name => $order) {
                    $key = $indexTag->addChild('key');
                    $key->addAttribute('name', $name);
                    $key->addAttribute('order', $order);
                }
                foreach ($index['options'] as $name => $value) {
                    if ($name === 'partialFilterExpression') {
                        continue; // not touching this, needs to be migrated manually
                    }
                    $option = $indexTag->addChild('option');
                    $option->addAttribute('name', $name);
                    if (is_bool($value)) {
                        $option->addAttribute('value', $value ? 'true' : 'false');
                    } else {
                        $option->addAttribute('value', $value);
                    }
                }
            }
        }
    }
}
