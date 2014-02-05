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
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
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
        if (isset($element['inheritanceType'])) {
            $class->setInheritanceType(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($element['inheritanceType'])));
        }
        if (isset($element['discriminatorField'])) {
            $class->setDiscriminatorField($this->parseDiscriminatorField($element['discriminatorField']));
        }
        if (isset($element['discriminatorMap'])) {
            $class->setDiscriminatorMap($element['discriminatorMap']);
        }
        if (isset($element['changeTrackingPolicy'])) {
            $class->setChangeTrackingPolicy(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper($element['changeTrackingPolicy'])));
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
                if (isset($mapping['type']) && $mapping['type'] === 'collection') {
                    // Note: this strategy is not actually used
                    $mapping['strategy'] = isset($mapping['strategy']) ? $mapping['strategy'] : 'pushAll';
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

        // Index this field if either "index", "unique", or "sparse" are set
        $keys = array($name => 'asc');

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

        $options = array();

        if (isset($mapping['index'])) {
            $options = is_array($mapping['index']) ? $mapping['index'] : array();
        } elseif (isset($mapping['unique'])) {
            $options = is_array($mapping['unique']) ? $mapping['unique'] : array();
            $options['unique'] = true;
        } elseif (isset($mapping['sparse'])) {
            $options = is_array($mapping['sparse']) ? $mapping['sparse'] : array();
            $options['sparse'] = true;
        }

        $class->addIndex($keys, $options);
    }

    private function addMappingFromEmbed(ClassMetadataInfo $class, $fieldName, $embed, $type)
    {
        $mapping = array(
            'type'           => $type,
            'embedded'       => true,
            'targetDocument' => isset($embed['targetDocument']) ? $embed['targetDocument'] : null,
            'fieldName'      => $fieldName,
            'strategy'       => isset($embed['strategy']) ? (string) $embed['strategy'] : 'pushAll',
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
        $this->addFieldMapping($class, $mapping);
    }

    private function addMappingFromReference(ClassMetadataInfo $class, $fieldName, $reference, $type)
    {
        $mapping = array(
            'cascade'          => isset($reference['cascade']) ? $reference['cascade'] : null,
            'orphanRemoval'    => isset($reference['orphanRemoval']) ? $reference['orphanRemoval'] : false,
            'type'             => $type,
            'reference'        => true,
            'simple'           => isset($reference['simple']) ? (boolean) $reference['simple'] : false,
            'targetDocument'   => isset($reference['targetDocument']) ? $reference['targetDocument'] : null,
            'fieldName'        => $fieldName,
            'strategy'         => isset($reference['strategy']) ? (string) $reference['strategy'] : 'pushAll',
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
        return Yaml::parse($file);
    }
}
