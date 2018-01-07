<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MappingClassMetadata;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @since       1.0
 */
class YamlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.yml';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        /* @var $class ClassMetadataInfo */
        $element = $this->getElement($className);
        if ( ! $element) {
            return;
        }
        $element['type'] = $element['type'] ?? 'document';

        if (isset($element['db'])) {
            $class->setDatabase($element['db']);
        }
        if (isset($element['collection'])) {
            $class->setCollection($element['collection']);
        }
        if (isset($element['readPreference'])) {
            if (! isset($element['readPreference']['mode'])) {
                throw new \InvalidArgumentException('"mode" is a required key for the readPreference setting.');
            }
            $class->setReadPreference(
                $element['readPreference']['mode'],
                $element['readPreference']['tagSets'] ?? null
            );
        }
        if (isset($element['writeConcern'])) {
            $class->setWriteConcern($element['writeConcern']);
        }
        if ($element['type'] == 'document') {
            if (isset($element['repositoryClass'])) {
                $class->setCustomRepositoryClass($element['repositoryClass']);
            }
        } elseif ($element['type'] === 'mappedSuperclass') {
            $class->setCustomRepositoryClass(
                $element['repositoryClass'] ?? null
            );
            $class->isMappedSuperclass = true;
        } elseif ($element['type'] === 'embeddedDocument') {
            $class->isEmbeddedDocument = true;
        } elseif ($element['type'] === 'queryResultDocument') {
            $class->isQueryResultDocument = true;
        }
        if (isset($element['indexes'])) {
            foreach($element['indexes'] as $index) {
                $class->addIndex($index['keys'], $index['options'] ?? array());
            }
        }
        if (isset($element['shardKey'])) {
            $this->setShardKey($class, $element['shardKey']);
        }
        if (isset($element['inheritanceType'])) {
            $class->setInheritanceType(constant(MappingClassMetadata::class . '::INHERITANCE_TYPE_' . strtoupper($element['inheritanceType'])));
        }
        if (isset($element['discriminatorField'])) {
            $class->setDiscriminatorField($this->parseDiscriminatorField($element['discriminatorField']));
        }
        if (isset($element['discriminatorMap'])) {
            $class->setDiscriminatorMap($element['discriminatorMap']);
        }
        if (isset($element['defaultDiscriminatorValue'])) {
            $class->setDefaultDiscriminatorValue($element['defaultDiscriminatorValue']);
        }
        if (isset($element['changeTrackingPolicy'])) {
            $class->setChangeTrackingPolicy(constant(MappingClassMetadata::class . '::CHANGETRACKING_' . strtoupper($element['changeTrackingPolicy'])));
        }
        if (! empty($element['readOnly'])) {
            $class->markReadOnly();
        }
        if (isset($element['fields'])) {
            foreach ($element['fields'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $type = $mapping;
                    $mapping = array();
                    $mapping['type'] = $type;
                }
                if ( ! isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                if (isset($mapping['type']) && ! empty($mapping['embedded'])) {
                    $this->addMappingFromEmbed($class, $fieldName, $mapping, $mapping['type']);
                } elseif (isset($mapping['type']) && ! empty($mapping['reference'])) {
                    $this->addMappingFromReference($class, $fieldName, $mapping, $mapping['type']);
                } else {
                    $this->addFieldMapping($class, $mapping);
                }
            }
        }
        if (isset($element['embedOne'])) {
            foreach ($element['embedOne'] as $fieldName => $embed) {
                $this->addMappingFromEmbed($class, $fieldName, $embed, 'one');
            }
        }
        if (isset($element['embedMany'])) {
            foreach ($element['embedMany'] as $fieldName => $embed) {
                $this->addMappingFromEmbed($class, $fieldName, $embed, 'many');
            }
        }
        if (isset($element['referenceOne'])) {
            foreach ($element['referenceOne'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'one');
            }
        }
        if (isset($element['referenceMany'])) {
            foreach ($element['referenceMany'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'many');
            }
        }
        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $type => $methods) {
                foreach ($methods as $method) {
                    $class->addLifecycleCallback($method, constant('Doctrine\ODM\MongoDB\Events::' . $type));
                }
            }
        }
        if (isset($element['alsoLoadMethods'])) {
            foreach ($element['alsoLoadMethods'] as $methodName => $fieldName) {
                $class->registerAlsoLoadMethod($methodName, $fieldName);
            }
        }
    }

    private function addFieldMapping(ClassMetadataInfo $class, $mapping)
    {
        if (isset($mapping['name'])) {
            $name = $mapping['name'];
        } elseif (isset($mapping['fieldName'])) {
            $name = $mapping['fieldName'];
        } else {
            throw new \InvalidArgumentException('Cannot infer a MongoDB name from the mapping');
        }

        $class->mapField($mapping);

        if ( ! (isset($mapping['index']) || isset($mapping['unique']) || isset($mapping['sparse']))) {
            return;
        }

        // Multiple index specifications in one field mapping is ambiguous
        if ((isset($mapping['index']) && is_array($mapping['index'])) +
            (isset($mapping['unique']) && is_array($mapping['unique'])) +
            (isset($mapping['sparse']) && is_array($mapping['sparse'])) > 1) {
            throw new \InvalidArgumentException('Multiple index specifications found among index, unique, and/or sparse fields');
        }

        // Index this field if either "index", "unique", or "sparse" are set
        $keys = array($name => 'asc');

        /* The "order" option is only used in the index specification and should
         * not be passed along as an index option.
         */
        if (isset($mapping['index']['order'])) {
            $keys[$name] = $mapping['index']['order'];
            unset($mapping['index']['order']);
        } elseif (isset($mapping['unique']['order'])) {
            $keys[$name] = $mapping['unique']['order'];
            unset($mapping['unique']['order']);
        } elseif (isset($mapping['sparse']['order'])) {
            $keys[$name] = $mapping['sparse']['order'];
            unset($mapping['sparse']['order']);
        }

        /* Initialize $options from any array value among index, unique, and
         * sparse. Any boolean values for unique or sparse should be merged into
         * the options afterwards to ensure consistent parsing.
         */
        $options = array();
        $unique = null;
        $sparse = null;

        if (isset($mapping['index']) && is_array($mapping['index'])) {
            $options = $mapping['index'];
        }

        if (isset($mapping['unique'])) {
            if (is_array($mapping['unique'])) {
                $options = $mapping['unique'] + array('unique' => true);
            } else {
                $unique = (boolean) $mapping['unique'];
            }
        }

        if (isset($mapping['sparse'])) {
            if (is_array($mapping['sparse'])) {
                $options = $mapping['sparse'] + array('sparse' => true);
            } else {
                $sparse = (boolean) $mapping['sparse'];
            }
        }

        if (isset($unique)) {
            $options['unique'] = $unique;
        }

        if (isset($sparse)) {
            $options['sparse'] = $sparse;
        }

        $class->addIndex($keys, $options);
    }

    private function addMappingFromEmbed(ClassMetadataInfo $class, $fieldName, $embed, $type)
    {
        $defaultStrategy = $type == 'one' ? ClassMetadataInfo::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping = array(
            'type'            => $type,
            'embedded'        => true,
            'targetDocument'  => $embed['targetDocument'] ?? null,
            'collectionClass' => $embed['collectionClass'] ?? null,
            'fieldName'       => $fieldName,
            'strategy'        => (string) ($embed['strategy'] ?? $defaultStrategy),
        );
        if (isset($embed['name'])) {
            $mapping['name'] = $embed['name'];
        }
        if (isset($embed['discriminatorField'])) {
            $mapping['discriminatorField'] = $this->parseDiscriminatorField($embed['discriminatorField']);
        }
        if (isset($embed['discriminatorMap'])) {
            $mapping['discriminatorMap'] = $embed['discriminatorMap'];
        }
        if (isset($embed['defaultDiscriminatorValue'])) {
            $mapping['defaultDiscriminatorValue'] = $embed['defaultDiscriminatorValue'];
        }
        $this->addFieldMapping($class, $mapping);
    }

    private function addMappingFromReference(ClassMetadataInfo $class, $fieldName, $reference, $type)
    {
        $defaultStrategy = $type == 'one' ? ClassMetadataInfo::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping = array(
            'cascade'          => $reference['cascade'] ?? [],
            'orphanRemoval'    => $reference['orphanRemoval'] ?? false,
            'type'             => $type,
            'reference'        => true,
            'storeAs'          => (string) ($reference['storeAs'] ?? ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF),
            'targetDocument'   => $reference['targetDocument'] ?? null,
            'collectionClass'  => $reference['collectionClass'] ?? null,
            'fieldName'        => $fieldName,
            'strategy'         => (string) ($reference['strategy'] ?? $defaultStrategy),
            'inversedBy'       => isset($reference['inversedBy']) ? (string) $reference['inversedBy'] : null,
            'mappedBy'         => isset($reference['mappedBy']) ? (string) $reference['mappedBy'] : null,
            'repositoryMethod' => isset($reference['repositoryMethod']) ? (string) $reference['repositoryMethod'] : null,
            'limit'            => isset($reference['limit']) ? (integer) $reference['limit'] : null,
            'skip'             => isset($reference['skip']) ? (integer) $reference['skip'] : null,
            'prime'            => $reference['prime'] ?? [],
        );
        if (isset($reference['name'])) {
            $mapping['name'] = $reference['name'];
        }
        if (isset($reference['discriminatorField'])) {
            $mapping['discriminatorField'] = $this->parseDiscriminatorField($reference['discriminatorField']);
        }
        if (isset($reference['discriminatorMap'])) {
            $mapping['discriminatorMap'] = $reference['discriminatorMap'];
        }
        if (isset($reference['defaultDiscriminatorValue'])) {
            $mapping['defaultDiscriminatorValue'] = $reference['defaultDiscriminatorValue'];
        }
        if (isset($reference['sort'])) {
            $mapping['sort'] = $reference['sort'];
        }
        if (isset($reference['criteria'])) {
            $mapping['criteria'] = $reference['criteria'];
        }
        $this->addFieldMapping($class, $mapping);
    }

    /**
     * Parses the class or field-level "discriminatorField" option.
     *
     * If the value is an array, check the "name" option before falling back to
     * the deprecated "fieldName" option (for BC). Otherwise, the value must be
     * a string.
     *
     * @param array|string $discriminatorField
     * @return string
     * @throws \InvalidArgumentException if the value is neither a string nor an
     *                                   array with a "name" or "fieldName" key.
     */
    private function parseDiscriminatorField($discriminatorField)
    {
        if (is_string($discriminatorField)) {
            return $discriminatorField;
        }

        if ( ! is_array($discriminatorField)) {
            throw new \InvalidArgumentException('Expected array or string for discriminatorField; found: ' . gettype($discriminatorField));
        }

        if (isset($discriminatorField['name'])) {
            return (string) $discriminatorField['name'];
        }

        if (isset($discriminatorField['fieldName'])) {
            return (string) $discriminatorField['fieldName'];
        }

        throw new \InvalidArgumentException('Expected "name" or "fieldName" key in discriminatorField array; found neither.');
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        try {
            return Yaml::parse(file_get_contents($file));
        }
        catch (ParseException $e) {
            $e->setParsedFile($file);
            throw $e;
        }
    }

    private function setShardKey(ClassMetadataInfo $class, array $shardKey)
    {
        $keys = $shardKey['keys'];
        $options = array();

        if (isset($shardKey['options'])) {
            $allowed = array('unique', 'numInitialChunks');
            foreach ($shardKey['options'] as $name => $value) {
                if ( ! in_array($name, $allowed, true)) {
                    continue;
                }
                $options[$name] = $value;
            }
        }

        $class->setShardKey($keys, $options);
    }
}
