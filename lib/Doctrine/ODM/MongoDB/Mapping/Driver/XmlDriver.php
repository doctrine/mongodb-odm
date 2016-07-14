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

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @since       1.0
 */
class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

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
        /* @var $xmlRoot \SimpleXMLElement */
        $xmlRoot = $this->getElement($className);
        if ( ! $xmlRoot) {
            return;
        }

        if ($xmlRoot->getName() == 'document') {
            if (isset($xmlRoot['repository-class'])) {
                $class->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
            }
        } elseif ($xmlRoot->getName() == 'mapped-superclass') {
            $class->setCustomRepositoryClass(
                isset($xmlRoot['repository-class']) ? (string) $xmlRoot['repository-class'] : null
            );
            $class->isMappedSuperclass = true;
        } elseif ($xmlRoot->getName() == 'embedded-document') {
            $class->isEmbeddedDocument = true;
        }
        if (isset($xmlRoot['db'])) {
            $class->setDatabase((string) $xmlRoot['db']);
        }
        if (isset($xmlRoot['collection'])) {
            if (isset($xmlRoot['capped-collection'])) {
                $config = array('name' => (string) $xmlRoot['collection']);
                $config['capped'] = (bool) $xmlRoot['capped-collection'];
                if (isset($xmlRoot['capped-collection-max'])) {
                    $config['max'] = (int) $xmlRoot['capped-collection-max'];
                }
                if (isset($xmlRoot['capped-collection-size'])) {
                    $config['size'] = (int) $xmlRoot['capped-collection-size'];
                }
                $class->setCollection($config);
            } else {
                $class->setCollection((string) $xmlRoot['collection']);
            }
        }
        if (isset($xmlRoot['writeConcern'])) {
            $class->setWriteConcern((string) $xmlRoot['writeConcern']);
        }
        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = (string) $xmlRoot['inheritance-type'];
            $class->setInheritanceType(constant(MappingClassMetadata::class . '::INHERITANCE_TYPE_' . $inheritanceType));
        }
        if (isset($xmlRoot['change-tracking-policy'])) {
            $class->setChangeTrackingPolicy(constant(MappingClassMetadata::class . '::CHANGETRACKING_' . strtoupper((string) $xmlRoot['change-tracking-policy'])));
        }
        if (isset($xmlRoot->{'discriminator-field'})) {
            $discrField = $xmlRoot->{'discriminator-field'};
            /* XSD only allows for "name", which is consistent with association
             * configurations, but fall back to "fieldName" for BC.
             */
            $class->setDiscriminatorField(
                isset($discrField['name']) ? (string) $discrField['name'] : (string) $discrField['fieldName']
            );
        }
        if (isset($xmlRoot->{'discriminator-map'})) {
            $map = array();
            foreach ($xmlRoot->{'discriminator-map'}->{'discriminator-mapping'} AS $discrMapElement) {
                $map[(string) $discrMapElement['value']] = (string) $discrMapElement['class'];
            }
            $class->setDiscriminatorMap($map);
        }
        if (isset($xmlRoot->{'default-discriminator-value'})) {
            $class->setDefaultDiscriminatorValue((string) $xmlRoot->{'default-discriminator-value'}['value']);
        }
        if (isset($xmlRoot->{'indexes'})) {
            foreach ($xmlRoot->{'indexes'}->{'index'} as $index) {
                $this->addIndex($class, $index);
            }
        }
        if (isset($xmlRoot->{'shard-key'})) {
            $this->setShardKey($class, $xmlRoot->{'shard-key'}[0]);
        }
        if (isset($xmlRoot['require-indexes'])) {
            $class->setRequireIndexes('true' === (string) $xmlRoot['require-indexes']);
        }
        if (isset($xmlRoot['slave-okay'])) {
            $class->setSlaveOkay('true' === (string) $xmlRoot['slave-okay']);
        }
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping = array();
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string) $value;
                    $booleanAttributes = array('id', 'reference', 'embed', 'unique', 'sparse', 'file', 'distance');
                    if (in_array($key, $booleanAttributes)) {
                        $mapping[$key] = ('true' === $mapping[$key]);
                    }
                }
                if (isset($mapping['id']) && $mapping['id'] === true && isset($mapping['strategy'])) {
                    $mapping['options'] = array();
                    if (isset($field->{'id-generator-option'})) {
                        foreach ($field->{'id-generator-option'} as $generatorOptions) {
                            $attributesGenerator = iterator_to_array($generatorOptions->attributes());
                            if (isset($attributesGenerator['name']) && isset($attributesGenerator['value'])) {
                                $mapping['options'][(string) $attributesGenerator['name']] = (string) $attributesGenerator['value'];
                            }
                        }
                    }
                }

                if (isset($attributes['not-saved'])) {
                    $mapping['notSaved'] = ('true' === (string) $attributes['not-saved']);
                }

                if (isset($attributes['also-load'])) {
                    $mapping['alsoLoadFields'] = explode(',', $attributes['also-load']);
                } elseif (isset($attributes['version'])) {
                    $mapping['version'] = ('true' === (string) $attributes['version']);
                } elseif (isset($attributes['lock'])) {
                    $mapping['lock'] = ('true' === (string) $attributes['lock']);
                }

                $this->addFieldMapping($class, $mapping);
            }
        }
        if (isset($xmlRoot->{'embed-one'})) {
            foreach ($xmlRoot->{'embed-one'} as $embed) {
                $this->addEmbedMapping($class, $embed, 'one');
            }
        }
        if (isset($xmlRoot->{'embed-many'})) {
            foreach ($xmlRoot->{'embed-many'} as $embed) {
                $this->addEmbedMapping($class, $embed, 'many');
            }
        }
        if (isset($xmlRoot->{'reference-many'})) {
            foreach ($xmlRoot->{'reference-many'} as $reference) {
                $this->addReferenceMapping($class, $reference, 'many');
            }
        }
        if (isset($xmlRoot->{'reference-one'})) {
            foreach ($xmlRoot->{'reference-one'} as $reference) {
                $this->addReferenceMapping($class, $reference, 'one');
            }
        }
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $class->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ODM\MongoDB\Events::' . (string) $lifecycleCallback['type']));
            }
        }
        if (isset($xmlRoot->{'also-load-methods'})) {
            foreach ($xmlRoot->{'also-load-methods'}->{'also-load-method'} as $alsoLoadMethod) {
                $class->registerAlsoLoadMethod((string) $alsoLoadMethod['method'], (string) $alsoLoadMethod['field']);
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

        // Index this field if either "index", "unique", or "sparse" are set
        if ( ! (isset($mapping['index']) || isset($mapping['unique']) || isset($mapping['sparse']))) {
            return;
        }

        $keys = array($name => isset($mapping['order']) ? $mapping['order'] : 'asc');
        $options = array();

        if (isset($mapping['background'])) {
            $options['background'] = (boolean) $mapping['background'];
        }
        if (isset($mapping['drop-dups'])) {
            $options['dropDups'] = (boolean) $mapping['drop-dups'];
        }
        if (isset($mapping['index-name'])) {
            $options['name'] = (string) $mapping['index-name'];
        }
        if (isset($mapping['safe'])) {
            $options['safe'] = (boolean) $mapping['safe'];
        }
        if (isset($mapping['sparse'])) {
            $options['sparse'] = (boolean) $mapping['sparse'];
        }
        if (isset($mapping['unique'])) {
            $options['unique'] = (boolean) $mapping['unique'];
        }

        $class->addIndex($keys, $options);
    }

    private function addEmbedMapping(ClassMetadataInfo $class, $embed, $type)
    {
        $attributes = $embed->attributes();
        $defaultStrategy = $type == 'one' ? ClassMetadataInfo::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping = array(
            'type'            => $type,
            'embedded'        => true,
            'targetDocument'  => isset($attributes['target-document']) ? (string) $attributes['target-document'] : null,
            'collectionClass' => isset($attributes['collection-class']) ? (string) $attributes['collection-class'] : null,
            'name'            => (string) $attributes['field'],
            'strategy'        => isset($attributes['strategy']) ? (string) $attributes['strategy'] : $defaultStrategy,
        );
        if (isset($attributes['fieldName'])) {
            $mapping['fieldName'] = (string) $attributes['fieldName'];
        }
        if (isset($embed->{'discriminator-field'})) {
            $attr = $embed->{'discriminator-field'};
            $mapping['discriminatorField'] = (string) $attr['name'];
        }
        if (isset($embed->{'discriminator-map'})) {
            foreach ($embed->{'discriminator-map'}->{'discriminator-mapping'} as $discriminatorMapping) {
                $attr = $discriminatorMapping->attributes();
                $mapping['discriminatorMap'][(string) $attr['value']] = (string) $attr['class'];
            }
        }
        if (isset($embed->{'default-discriminator-value'})) {
            $mapping['defaultDiscriminatorValue'] = (string) $embed->{'default-discriminator-value'}['value'];
        }
        if (isset($attributes['not-saved'])) {
            $mapping['notSaved'] = ('true' === (string) $attributes['not-saved']);
        }
        if (isset($attributes['also-load'])) {
            $mapping['alsoLoadFields'] = explode(',', $attributes['also-load']);
        }
        $this->addFieldMapping($class, $mapping);
    }

    private function addReferenceMapping(ClassMetadataInfo $class, $reference, $type)
    {
        $cascade = array_keys((array) $reference->cascade);
        if (1 === count($cascade)) {
            $cascade = current($cascade) ?: next($cascade);
        }
        $attributes = $reference->attributes();
        $defaultStrategy = $type == 'one' ? ClassMetadataInfo::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping = array(
            'cascade'          => $cascade,
            'orphanRemoval'    => isset($attributes['orphan-removal']) ? ('true' === (string) $attributes['orphan-removal']) : false,
            'type'             => $type,
            'reference'        => true,
            'simple'           => isset($attributes['simple']) ? ('true' === (string) $attributes['simple']) : false, // deprecated
            'storeAs'          => isset($attributes['store-as']) ? (string) $attributes['store-as'] : ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB,
            'targetDocument'   => isset($attributes['target-document']) ? (string) $attributes['target-document'] : null,
            'collectionClass'  => isset($attributes['collection-class']) ? (string) $attributes['collection-class'] : null,
            'name'             => (string) $attributes['field'],
            'strategy'         => isset($attributes['strategy']) ? (string) $attributes['strategy'] : $defaultStrategy,
            'inversedBy'       => isset($attributes['inversed-by']) ? (string) $attributes['inversed-by'] : null,
            'mappedBy'         => isset($attributes['mapped-by']) ? (string) $attributes['mapped-by'] : null,
            'repositoryMethod' => isset($attributes['repository-method']) ? (string) $attributes['repository-method'] : null,
            'limit'            => isset($attributes['limit']) ? (integer) $attributes['limit'] : null,
            'skip'             => isset($attributes['skip']) ? (integer) $attributes['skip'] : null,
        );

        if (isset($attributes['fieldName'])) {
            $mapping['fieldName'] = (string) $attributes['fieldName'];
        }
        if (isset($reference->{'discriminator-field'})) {
            $attr = $reference->{'discriminator-field'};
            $mapping['discriminatorField'] = (string) $attr['name'];
        }
        if (isset($reference->{'discriminator-map'})) {
            foreach ($reference->{'discriminator-map'}->{'discriminator-mapping'} as $discriminatorMapping) {
                $attr = $discriminatorMapping->attributes();
                $mapping['discriminatorMap'][(string) $attr['value']] = (string) $attr['class'];
            }
        }
        if (isset($reference->{'default-discriminator-value'})) {
            $mapping['defaultDiscriminatorValue'] = (string) $reference->{'default-discriminator-value'}['value'];
        }
        if (isset($reference->{'sort'})) {
            foreach ($reference->{'sort'}->{'sort'} as $sort) {
                $attr = $sort->attributes();
                $mapping['sort'][(string) $attr['field']] = isset($attr['order']) ? (string) $attr['order'] : 'asc';
            }
        }
        if (isset($reference->{'criteria'})) {
            foreach ($reference->{'criteria'}->{'criteria'} as $criteria) {
                $attr = $criteria->attributes();
                $mapping['criteria'][(string) $attr['field']] = (string) $attr['value'];
            }
        }
        if (isset($attributes['not-saved'])) {
            $mapping['notSaved'] = ('true' === (string) $attributes['not-saved']);
        }
        if (isset($attributes['also-load'])) {
            $mapping['alsoLoadFields'] = explode(',', $attributes['also-load']);
        }
        $this->addFieldMapping($class, $mapping);
    }

    private function addIndex(ClassMetadataInfo $class, \SimpleXmlElement $xmlIndex)
    {
        $attributes = $xmlIndex->attributes();

        $keys = array();

        foreach ($xmlIndex->{'key'} as $key) {
            $keys[(string) $key['name']] = isset($key['order']) ? (string) $key['order'] : 'asc';
        }

        $options = array();

        if (isset($attributes['background'])) {
            $options['background'] = ('true' === (string) $attributes['background']);
        }
        if (isset($attributes['drop-dups'])) {
            $options['dropDups'] = ('true' === (string) $attributes['drop-dups']);
        }
        if (isset($attributes['name'])) {
            $options['name'] = (string) $attributes['name'];
        }
        if (isset($attributes['safe'])) {
            $options['safe'] = ('true' === (string) $attributes['safe']);
        }
        if (isset($attributes['sparse'])) {
            $options['sparse'] = ('true' === (string) $attributes['sparse']);
        }
        if (isset($attributes['unique'])) {
            $options['unique'] = ('true' === (string) $attributes['unique']);
        }

        if (isset($xmlIndex->{'option'})) {
            foreach ($xmlIndex->{'option'} as $option) {
                $value = (string) $option['value'];
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = preg_match('/^[-]?\d+$/', $value) ? (integer) $value : (float) $value;
                }
                $options[(string) $option['name']] = $value;
            }
        }

        if (isset($xmlIndex->{'partial-filter-expression'})) {
            $partialFilterExpressionMapping = $xmlIndex->{'partial-filter-expression'};

            if (isset($partialFilterExpressionMapping->and)) {
                foreach ($partialFilterExpressionMapping->and as $and) {
                    if (! isset($and->field)) {
                        continue;
                    }

                    $partialFilterExpression = $this->getPartialFilterExpression($and->field);
                    if (! $partialFilterExpression) {
                        continue;
                    }

                    $options['partialFilterExpression']['$and'][] = $partialFilterExpression;
                }
            } elseif (isset($partialFilterExpressionMapping->field)) {
                $partialFilterExpression = $this->getPartialFilterExpression($partialFilterExpressionMapping->field);

                if ($partialFilterExpression) {
                    $options['partialFilterExpression'] = $partialFilterExpression;
                }
            }
        }

        $class->addIndex($keys, $options);
    }

    private function getPartialFilterExpression(\SimpleXMLElement $fields)
    {
        $partialFilterExpression = [];
        foreach ($fields as $field) {
            $operator = (string) $field['operator'] ?: null;

            if (! isset($field['value'])) {
                if (! isset($field->field)) {
                    continue;
                }

                $nestedExpression = $this->getPartialFilterExpression($field->field);
                if (! $nestedExpression) {
                    continue;
                }

                $value = $nestedExpression;
            } else {
                $value = trim((string) $field['value']);
            }

            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            } elseif (is_numeric($value)) {
                $value = preg_match('/^[-]?\d+$/', $value) ? (integer) $value : (float) $value;
            }

            $partialFilterExpression[(string) $field['name']] = $operator ? ['$' . $operator => $value] : $value;
        }

        return $partialFilterExpression;
    }

    private function setShardKey(ClassMetadataInfo $class, \SimpleXmlElement $xmlShardkey)
    {
        $attributes = $xmlShardkey->attributes();

        $keys = array();
        $options = array();
        foreach ($xmlShardkey->{'key'} as $key) {
            $keys[(string) $key['name']] = isset($key['order']) ? (string)$key['order'] : 'asc';
        }

        if (isset($attributes['unique'])) {
            $options['unique'] = ('true' === (string) $attributes['unique']);
        }

        if (isset($attributes['numInitialChunks'])) {
            $options['numInitialChunks'] = (int) $attributes['numInitialChunks'];
        }

        if (isset($xmlShardkey->{'option'})) {
            foreach ($xmlShardkey->{'option'} as $option) {
                $value = (string) $option['value'];
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = preg_match('/^[-]?\d+$/', $value) ? (integer) $value : (float) $value;
                }
                $options[(string) $option['name']] = $value;
            }
        }

        $class->setShardKey($keys, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        foreach (array('document', 'embedded-document', 'mapped-superclass') as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $documentName = (string) $documentElement['name'];
                    $result[$documentName] = $documentElement;
                }
            }
        }

        return $result;
    }
}
