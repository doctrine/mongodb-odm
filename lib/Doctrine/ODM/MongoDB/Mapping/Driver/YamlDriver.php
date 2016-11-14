<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
        $element['type'] = isset($element['type']) ? $element['type'] : 'document';

        if (isset($element['db'])) {
            $class->setDatabase($element['db']);
        }
        if (isset($element['collection'])) {
            $class->setCollection($element['collection']);
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
                isset($element['repositoryClass']) ? $element['repositoryClass'] : null
            );
            $class->isMappedSuperclass = true;
        } elseif ($element['type'] === 'embeddedDocument') {
            $class->isEmbeddedDocument = true;
        }
        if (isset($element['indexes'])) {
            foreach($element['indexes'] as $index) {
                $class->addIndex($index['keys'], isset($index['options']) ? $index['options'] : array());
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
        if (isset($element['requireIndexes'])) {
            $class->setRequireIndexes($element['requireIndexes']);
        }
        if (isset($element['slaveOkay'])) {
            $class->setSlaveOkay($element['slaveOkay']);
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
            'targetDocument'  => isset($embed['targetDocument']) ? $embed['targetDocument'] : null,
            'collectionClass' => isset($embed['collectionClass']) ? $embed['collectionClass'] : null,
            'fieldName'       => $fieldName,
            'strategy'        => isset($embed['strategy']) ? (string) $embed['strategy'] : $defaultStrategy,
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
            'cascade'          => isset($reference['cascade']) ? $reference['cascade'] : null,
            'orphanRemoval'    => isset($reference['orphanRemoval']) ? $reference['orphanRemoval'] : false,
            'type'             => $type,
            'reference'        => true,
            'simple'           => isset($reference['simple']) ? (boolean) $reference['simple'] : false, // deprecated
            'storeAs'          => isset($reference['storeAs']) ? (string) $reference['storeAs'] : ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB,
            'targetDocument'   => isset($reference['targetDocument']) ? $reference['targetDocument'] : null,
            'collectionClass'  => isset($reference['collectionClass']) ? $reference['collectionClass'] : null,
            'fieldName'        => $fieldName,
            'strategy'         => isset($reference['strategy']) ? (string) $reference['strategy'] : $defaultStrategy,
            'inversedBy'       => isset($reference['inversedBy']) ? (string) $reference['inversedBy'] : null,
            'mappedBy'         => isset($reference['mappedBy']) ? (string) $reference['mappedBy'] : null,
            'repositoryMethod' => isset($reference['repositoryMethod']) ? (string) $reference['repositoryMethod'] : null,
            'limit'            => isset($reference['limit']) ? (integer) $reference['limit'] : null,
            'skip'             => isset($reference['skip']) ? (integer) $reference['skip'] : null,
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
