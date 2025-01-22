<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Annotations\TimeSeries;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use DOMDocument;
use InvalidArgumentException;
use LibXMLError;
use MongoDB\BSON\Document;
use MongoDB\Driver\Exception\UnexpectedValueException;
use SimpleXMLElement;

use function array_is_list;
use function array_keys;
use function array_map;
use function assert;
use function class_exists;
use function constant;
use function count;
use function current;
use function explode;
use function implode;
use function in_array;
use function interface_exists;
use function is_numeric;
use function iterator_to_array;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function next;
use function preg_match;
use function simplexml_load_file;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @phpstan-import-type FieldMappingConfig from ClassMetadata
 * @template-extends FileDriver<SimpleXMLElement>
 */
class XmlDriver extends FileDriver
{
    public const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    private const DEFAULT_GRIDFS_MAPPINGS = [
        'length' => [
            'name' => 'length',
            'type' => 'int',
            'notSaved' => true,
        ],
        'chunk-size' => [
            'name' => 'chunkSize',
            'type' => 'int',
            'notSaved' => true,
        ],
        'filename' => [
            'name' => 'filename',
            'type' => 'string',
            'notSaved' => true,
        ],
        'upload-date' => [
            'name' => 'uploadDate',
            'type' => 'date',
            'notSaved' => true,
        ],
    ];

    /** @param string|null $fileExtension */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
    public function loadMetadataForClass($className, \Doctrine\Persistence\Mapping\ClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $xmlRoot = $this->getElement($className);
        assert($xmlRoot instanceof SimpleXMLElement);

        if ($xmlRoot->getName() === 'document') {
            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
            }
        } elseif ($xmlRoot->getName() === 'mapped-superclass') {
            $metadata->setCustomRepositoryClass(
                isset($xmlRoot['repository-class']) ? (string) $xmlRoot['repository-class'] : null,
            );
            $metadata->isMappedSuperclass = true;
        } elseif ($xmlRoot->getName() === 'embedded-document') {
            $metadata->isEmbeddedDocument = true;
        } elseif ($xmlRoot->getName() === 'query-result-document') {
            $metadata->isQueryResultDocument = true;
        } elseif ($xmlRoot->getName() === 'view') {
            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
            }

            if (! isset($xmlRoot['root-class'])) {
                throw MappingException::viewWithoutRootClass($className);
            }

            $rootClass = (string) $xmlRoot['root-class'];
            if (! class_exists($rootClass)) {
                throw MappingException::viewRootClassNotFound($className, $rootClass);
            }

            $metadata->markViewOf($rootClass);
        } elseif ($xmlRoot->getName() === 'gridfs-file') {
            $metadata->isFile = true;

            if (isset($xmlRoot['chunk-size-bytes'])) {
                $metadata->setChunkSizeBytes((int) $xmlRoot['chunk-size-bytes']);
            }

            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
            }
        }

        if (isset($xmlRoot['db'])) {
            $metadata->setDatabase((string) $xmlRoot['db']);
        }

        if (isset($xmlRoot['collection'])) {
            if (isset($xmlRoot['capped-collection'])) {
                $config           = ['name' => (string) $xmlRoot['collection']];
                $config['capped'] = (bool) $xmlRoot['capped-collection'];
                if (isset($xmlRoot['capped-collection-max'])) {
                    $config['max'] = (int) $xmlRoot['capped-collection-max'];
                }

                if (isset($xmlRoot['capped-collection-size'])) {
                    $config['size'] = (int) $xmlRoot['capped-collection-size'];
                }

                $metadata->setCollection($config);
            } else {
                $metadata->setCollection((string) $xmlRoot['collection']);
            }
        }

        if (isset($xmlRoot['bucket-name'])) {
            $metadata->setBucketName((string) $xmlRoot['bucket-name']);
        }

        if (isset($xmlRoot['view'])) {
            $metadata->setCollection((string) $xmlRoot['view']);
        }

        if (isset($xmlRoot['write-concern'])) {
            $metadata->setWriteConcern((string) $xmlRoot['write-concern']);
        }

        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = (string) $xmlRoot['inheritance-type'];
            $metadata->setInheritanceType(constant(ClassMetadata::class . '::INHERITANCE_TYPE_' . $inheritanceType));
        }

        if (isset($xmlRoot['change-tracking-policy'])) {
            $metadata->setChangeTrackingPolicy(constant(ClassMetadata::class . '::CHANGETRACKING_' . strtoupper((string) $xmlRoot['change-tracking-policy'])));
        }

        if (isset($xmlRoot->{'discriminator-field'})) {
            $discrField = $xmlRoot->{'discriminator-field'};
            $metadata->setDiscriminatorField((string) $discrField['name']);
        }

        if (isset($xmlRoot->{'discriminator-map'})) {
            $map = [];
            foreach ($xmlRoot->{'discriminator-map'}->{'discriminator-mapping'} as $discrMapElement) {
                $map[(string) $discrMapElement['value']] = (string) $discrMapElement['class'];
            }

            $metadata->setDiscriminatorMap($map);
        }

        if (isset($xmlRoot->{'default-discriminator-value'})) {
            $metadata->setDefaultDiscriminatorValue((string) $xmlRoot->{'default-discriminator-value'}['value']);
        }

        if (isset($xmlRoot->indexes)) {
            foreach ($xmlRoot->indexes->index as $index) {
                $this->addIndex($metadata, $index);
            }
        }

        if (isset($xmlRoot->{'search-indexes'})) {
            foreach ($xmlRoot->{'search-indexes'}->{'search-index'} as $searchIndex) {
                $this->addSearchIndex($metadata, $searchIndex);
            }
        }

        if (isset($xmlRoot->{'shard-key'})) {
            $this->setShardKey($metadata, $xmlRoot->{'shard-key'}[0]);
        }

        if (isset($xmlRoot->{'schema-validation'})) {
            $xmlSchemaValidation = $xmlRoot->{'schema-validation'};

            if (isset($xmlSchemaValidation['action'])) {
                $metadata->setValidationAction((string) $xmlSchemaValidation['action']);
            }

            if (isset($xmlSchemaValidation['level'])) {
                $metadata->setValidationLevel((string) $xmlSchemaValidation['level']);
            }

            $validatorJson = (string) $xmlSchemaValidation;
            try {
                $validatorBson = Document::fromJSON($validatorJson);
            } catch (UnexpectedValueException $e) {
                throw MappingException::schemaValidationError($e->getCode(), $e->getMessage(), $className, 'schema-validation');
            }

            $validator = $validatorBson->toPHP();
            $metadata->setValidator($validator);
        }

        if (isset($xmlRoot['read-only']) && (string) $xmlRoot['read-only'] === 'true') {
            $metadata->markReadOnly();
        }

        if (isset($xmlRoot->{'read-preference'})) {
            $metadata->setReadPreference(...$this->transformReadPreference($xmlRoot->{'read-preference'}));
        }

        if (isset($xmlRoot->id)) {
            $field   = $xmlRoot->id;
            $mapping = [
                'id' => true,
                'fieldName' => 'id',
            ];

            $attributes = $field->attributes();
            assert($attributes instanceof SimpleXMLElement);
            foreach ($attributes as $key => $value) {
                $mapping[$key] = (string) $value;
            }

            if (isset($attributes['field-name'])) {
                $mapping['fieldName'] = (string) $attributes['field-name'];
            }

            if (isset($mapping['strategy'])) {
                $mapping['options'] = [];
                if (isset($field->{'generator-option'})) {
                    foreach ($field->{'generator-option'} as $generatorOptions) {
                        $attributesGenerator = iterator_to_array($generatorOptions->attributes());
                        if (! isset($attributesGenerator['name']) || ! isset($attributesGenerator['value'])) {
                            continue;
                        }

                        $mapping['options'][(string) $attributesGenerator['name']] = (string) $attributesGenerator['value'];
                    }
                }
            }

            $this->addFieldMapping($metadata, $mapping);
        }

        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping    = [];
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key]     = (string) $value;
                    $booleanAttributes = ['reference', 'embed', 'unique', 'sparse', 'nullable'];
                    if (! in_array($key, $booleanAttributes)) {
                        continue;
                    }

                    $mapping[$key] = ($mapping[$key] === 'true');
                }

                if (isset($attributes['not-saved'])) {
                    $mapping['notSaved'] = ((string) $attributes['not-saved'] === 'true');
                }

                if (isset($attributes['enum-type'])) {
                    $mapping['enumType'] = (string) $attributes['enum-type'];
                }

                if (isset($attributes['field-name'])) {
                    $mapping['fieldName'] = (string) $attributes['field-name'];
                }

                if (isset($attributes['also-load'])) {
                    $mapping['alsoLoadFields'] = explode(',', (string) $attributes['also-load']);
                } elseif (isset($attributes['version'])) {
                    $mapping['version'] = ((string) $attributes['version'] === 'true');
                } elseif (isset($attributes['lock'])) {
                    $mapping['lock'] = ((string) $attributes['lock'] === 'true');
                }

                $this->addFieldMapping($metadata, $mapping);
            }
        }

        $this->addGridFSMappings($metadata, $xmlRoot);

        if (isset($xmlRoot->{'embed-one'})) {
            foreach ($xmlRoot->{'embed-one'} as $embed) {
                $this->addEmbedMapping($metadata, $embed, ClassMetadata::ONE);
            }
        }

        if (isset($xmlRoot->{'embed-many'})) {
            foreach ($xmlRoot->{'embed-many'} as $embed) {
                $this->addEmbedMapping($metadata, $embed, ClassMetadata::MANY);
            }
        }

        if (isset($xmlRoot->{'reference-many'})) {
            foreach ($xmlRoot->{'reference-many'} as $reference) {
                $this->addReferenceMapping($metadata, $reference, ClassMetadata::MANY);
            }
        }

        if (isset($xmlRoot->{'reference-one'})) {
            foreach ($xmlRoot->{'reference-one'} as $reference) {
                $this->addReferenceMapping($metadata, $reference, ClassMetadata::ONE);
            }
        }

        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $metadata->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ODM\MongoDB\Events::' . (string) $lifecycleCallback['type']));
            }
        }

        if (isset($xmlRoot->{'also-load-methods'})) {
            foreach ($xmlRoot->{'also-load-methods'}->{'also-load-method'} as $alsoLoadMethod) {
                $metadata->registerAlsoLoadMethod((string) $alsoLoadMethod['method'], (string) $alsoLoadMethod['field']);
            }
        }

        if (isset($xmlRoot->{'time-series'})) {
            $attributes = $xmlRoot->{'time-series'}->attributes();

            $metaField             = isset($attributes['meta-field']) ? (string) $attributes['meta-field'] : null;
            $granularity           = isset($attributes['granularity']) ? Granularity::from((string) $attributes['granularity']) : null;
            $expireAfterSeconds    = isset($attributes['expire-after-seconds']) ? (int) $attributes['expire-after-seconds'] : null;
            $bucketMaxSpanSeconds  = isset($attributes['bucket-max-span-seconds']) ? (int) $attributes['bucket-max-span-seconds'] : null;
            $bucketRoundingSeconds = isset($attributes['bucket-rounding-seconds']) ? (int) $attributes['bucket-rounding-seconds'] : null;

            $metadata->markAsTimeSeries(new TimeSeries(
                timeField: (string) $attributes['time-field'],
                metaField: $metaField,
                granularity: $granularity,
                expireAfterSeconds: $expireAfterSeconds,
                bucketMaxSpanSeconds: $bucketMaxSpanSeconds,
                bucketRoundingSeconds: $bucketRoundingSeconds,
            ));
        }
    }

    // phpcs:enable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

    /**
     * @param ClassMetadata<object> $class
     * @phpstan-param FieldMappingConfig $mapping
     */
    private function addFieldMapping(ClassMetadata $class, array $mapping): void
    {
        if (isset($mapping['name'])) {
            $name = $mapping['name'];
        } elseif (isset($mapping['fieldName'])) {
            $name = $mapping['fieldName'];
        } else {
            throw new InvalidArgumentException('Cannot infer a MongoDB name from the mapping');
        }

        $class->mapField($mapping);

        // Index this field if either "index", "unique", or "sparse" are set
        if (! (isset($mapping['index']) || isset($mapping['unique']) || isset($mapping['sparse']))) {
            return;
        }

        $keys    = [$name => $mapping['order'] ?? 'asc'];
        $options = [];

        if (isset($mapping['background'])) {
            $options['background'] = (bool) $mapping['background'];
        }

        if (isset($mapping['index-name'])) {
            $options['name'] = (string) $mapping['index-name'];
        }

        if (isset($mapping['sparse'])) {
            $options['sparse'] = (bool) $mapping['sparse'];
        }

        if (isset($mapping['unique'])) {
            $options['unique'] = (bool) $mapping['unique'];
        }

        $class->addIndex($keys, $options);
    }

    /** @param ClassMetadata<object> $class */
    private function addEmbedMapping(ClassMetadata $class, SimpleXMLElement $embed, string $type): void
    {
        $attributes      = $embed->attributes();
        $defaultStrategy = $type === ClassMetadata::ONE ? ClassMetadata::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping         = [
            'type'            => $type,
            'embedded'        => true,
            'targetDocument'  => isset($attributes['target-document']) ? (string) $attributes['target-document'] : null,
            'collectionClass' => isset($attributes['collection-class']) ? (string) $attributes['collection-class'] : null,
            'name'            => (string) $attributes['field'],
            'strategy'        => (string) ($attributes['strategy'] ?? $defaultStrategy),
            'nullable'        => isset($attributes['nullable']) ? ((string) $attributes['nullable'] === 'true') : false,
            'storeEmptyArray'  => isset($attributes['store-empty-array']) ? ((string) $attributes['store-empty-array'] === 'true') : false,
        ];
        if (isset($attributes['field-name'])) {
            $mapping['fieldName'] = (string) $attributes['field-name'];
        }

        if (isset($embed->{'discriminator-field'})) {
            $attr                          = $embed->{'discriminator-field'};
            $mapping['discriminatorField'] = (string) $attr['name'];
        }

        if (isset($embed->{'discriminator-map'})) {
            foreach ($embed->{'discriminator-map'}->{'discriminator-mapping'} as $discriminatorMapping) {
                $attr                                                 = $discriminatorMapping->attributes();
                $mapping['discriminatorMap'][(string) $attr['value']] = (string) $attr['class'];
            }
        }

        if (isset($embed->{'default-discriminator-value'})) {
            $mapping['defaultDiscriminatorValue'] = (string) $embed->{'default-discriminator-value'}['value'];
        }

        if (isset($attributes['not-saved'])) {
            $mapping['notSaved'] = ((string) $attributes['not-saved'] === 'true');
        }

        if (isset($attributes['also-load'])) {
            $mapping['alsoLoadFields'] = explode(',', (string) $attributes['also-load']);
        }

        $this->addFieldMapping($class, $mapping);
    }

    /** @param ClassMetadata<object> $class */
    private function addReferenceMapping(ClassMetadata $class, ?SimpleXMLElement $reference, string $type): void
    {
        $cascade = array_keys((array) $reference->cascade);
        if (count($cascade) === 1) {
            $cascade = current($cascade) ?: next($cascade);
        }

        $attributes      = $reference->attributes();
        $defaultStrategy = $type === ClassMetadata::ONE ? ClassMetadata::STORAGE_STRATEGY_SET : CollectionHelper::DEFAULT_STRATEGY;
        $mapping         = [
            'cascade'          => $cascade,
            'orphanRemoval'    => isset($attributes['orphan-removal']) ? ((string) $attributes['orphan-removal'] === 'true') : false,
            'type'             => $type,
            'reference'        => true,
            'storeAs'          => (string) ($attributes['store-as'] ?? ClassMetadata::REFERENCE_STORE_AS_DB_REF),
            'targetDocument'   => isset($attributes['target-document']) ? (string) $attributes['target-document'] : null,
            'collectionClass'  => isset($attributes['collection-class']) ? (string) $attributes['collection-class'] : null,
            'name'             => (string) $attributes['field'],
            'strategy'         => (string) ($attributes['strategy'] ?? $defaultStrategy),
            'inversedBy'       => isset($attributes['inversed-by']) ? (string) $attributes['inversed-by'] : null,
            'mappedBy'         => isset($attributes['mapped-by']) ? (string) $attributes['mapped-by'] : null,
            'repositoryMethod' => isset($attributes['repository-method']) ? (string) $attributes['repository-method'] : null,
            'limit'            => isset($attributes['limit']) ? (int) $attributes['limit'] : null,
            'skip'             => isset($attributes['skip']) ? (int) $attributes['skip'] : null,
            'prime'            => [],
            'nullable'         => isset($attributes['nullable']) ? ((string) $attributes['nullable'] === 'true') : false,
            'storeEmptyArray'  => isset($attributes['store-empty-array']) ? ((string) $attributes['store-empty-array'] === 'true') : false,
        ];

        if (isset($attributes['field-name'])) {
            $mapping['fieldName'] = (string) $attributes['field-name'];
        }

        if (isset($reference->{'discriminator-field'})) {
            $attr                          = $reference->{'discriminator-field'};
            $mapping['discriminatorField'] = (string) $attr['name'];
        }

        if (isset($reference->{'discriminator-map'})) {
            foreach ($reference->{'discriminator-map'}->{'discriminator-mapping'} as $discriminatorMapping) {
                $attr                                                 = $discriminatorMapping->attributes();
                $mapping['discriminatorMap'][(string) $attr['value']] = (string) $attr['class'];
            }
        }

        if (isset($reference->{'default-discriminator-value'})) {
            $mapping['defaultDiscriminatorValue'] = (string) $reference->{'default-discriminator-value'}['value'];
        }

        if (isset($reference->sort)) {
            foreach ($reference->sort->sort as $sort) {
                $attr                                     = $sort->attributes();
                $mapping['sort'][(string) $attr['field']] = (string) ($attr['order'] ?? 'asc');
            }
        }

        if (isset($reference->criteria)) {
            foreach ($reference->criteria->criteria as $criteria) {
                $attr                                         = $criteria->attributes();
                $mapping['criteria'][(string) $attr['field']] = (string) $attr['value'];
            }
        }

        if (isset($attributes['not-saved'])) {
            $mapping['notSaved'] = ((string) $attributes['not-saved'] === 'true');
        }

        if (isset($attributes['also-load'])) {
            $mapping['alsoLoadFields'] = explode(',', (string) $attributes['also-load']);
        }

        if (isset($reference->prime)) {
            foreach ($reference->prime->field as $field) {
                $attr               = $field->attributes();
                $mapping['prime'][] = (string) $attr['name'];
            }
        }

        $this->addFieldMapping($class, $mapping);
    }

    /** @param ClassMetadata<object> $class */
    private function addIndex(ClassMetadata $class, SimpleXMLElement $xmlIndex): void
    {
        $attributes = $xmlIndex->attributes();

        $keys = [];

        foreach ($xmlIndex->key as $key) {
            $keys[(string) $key['name']] = (string) ($key['order'] ?? 'asc');
        }

        $options = [];

        if (isset($attributes['background'])) {
            $options['background'] = ((string) $attributes['background'] === 'true');
        }

        if (isset($attributes['name'])) {
            $options['name'] = (string) $attributes['name'];
        }

        if (isset($attributes['sparse'])) {
            $options['sparse'] = ((string) $attributes['sparse'] === 'true');
        }

        if (isset($attributes['unique'])) {
            $options['unique'] = ((string) $attributes['unique'] === 'true');
        }

        if (isset($xmlIndex->option)) {
            foreach ($xmlIndex->option as $option) {
                $options[(string) $option['name']] = $this->convertXMLElementValue((string) $option['value']);
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

    /** @param ClassMetadata<object> $class */
    private function addSearchIndex(ClassMetadata $class, SimpleXMLElement $searchIndex): void
    {
        $definition = [];

        if (isset($searchIndex['dynamic'])) {
            $definition['mappings']['dynamic'] = $this->convertXMLElementValue((string) $searchIndex['dynamic']);
        }

        foreach ($searchIndex->field as $field) {
            $name            = (string) $field['name'];
            $fieldDefinition = $this->getSearchIndexFieldDefinition($field);

            // If the field is indexed with multiple data types, collect the definitions in a list.
            // See: https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#index-field-as-multiple-data-types
            if (isset($definition['mappings']['fields'][$name])) {
                if (! array_is_list($definition['mappings']['fields'][$name])) {
                    $definition['mappings']['fields'][$name] = [$definition['mappings']['fields'][$name]];
                }

                $definition['mappings']['fields'][$name][] = $fieldDefinition;
            } else {
                $definition['mappings']['fields'][$name] = $fieldDefinition;
            }
        }

        foreach (['analyzer', 'searchAnalyzer', 'storedSource'] as $key) {
            if (isset($searchIndex[$key])) {
                $definition[$key] = $this->convertXMLElementValue((string) $searchIndex[$key]);
            }
        }

        foreach ($searchIndex->{'stored-source'} as $storedSource) {
            $type   = (string) $storedSource['type'];
            $fields = [];

            foreach ($storedSource->field as $field) {
                $fields[] = (string) $field['name'];
            }

            if (isset($definition['storedSource'])) {
                throw new InvalidArgumentException('Search index definition already has a "storedSource" option');
            }

            if ($type !== 'include' && $type !== 'exclude') {
                throw new InvalidArgumentException(sprintf('Type "%s" is unsupported for <stored-source>', $type));
            }

            $definition['storedSource'] = [$type => $fields];
        }

        foreach ($searchIndex->synonym as $synonym) {
            $definition['synonyms'][] = [
                'analyzer' => (string) $synonym['analyzer'],
                'name' => (string) $synonym['name'],
                'source' => ['collection' => (string) $synonym['sourceCollection']],
            ];
        }

        $name = isset($searchIndex['name']) ? (string) $searchIndex['name'] : null;

        $class->addSearchIndex($definition, $name);
    }

    private function getSearchIndexFieldDefinition(SimpleXMLElement $field): array
    {
        $fieldDefinition = [];

        foreach ($field->field as $nestedField) {
            $name                  = (string) $nestedField['name'];
            $nestedFieldDefinition = $this->getSearchIndexFieldDefinition($field);

            // If the field is indexed with multiple data types, collect the definitions in a list.
            // See: https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#index-field-as-multiple-data-types
            if (isset($fieldDefinition[$name])) {
                if (! array_is_list($fieldDefinition['fields'][$name])) {
                    $fieldDefinition['fields'][$name] = [$fieldDefinition['fields'][$name]];
                }

                $fieldDefinition['fields'][$name][] = $fieldDefinition;
            } else {
                $fieldDefinition['fields'][$name] = $fieldDefinition;
            }
        }

        foreach ($field->multi as $multi) {
            $name                            = (string) $multi['name'];
            $fieldDefinition['multi'][$name] = $this->getSearchIndexFieldDefinition($multi);
        }

        $allowedOptions = [
            'type',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/autocomplete-type/
            'maxGrams',
            'minGrams',
            'tokenization',
            'foldDiacritics',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/document-type/
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/embedded-documents-type/
            'dynamic',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/geo-type/
            'indexShapes',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/knn-vector/
            'dimensions',
            'similarity',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/number-type/
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/number-facet-type/
            'representation',
            'indexIntegers',
            'indexDoubles',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/string-type/
            'analyzer',
            'searchAnalyzer',
            'indexOptions',
            'store',
            'ignoreAbove',
            'norms',
            // https://www.mongodb.com/docs/atlas/atlas-search/field-types/token-type/
            'normalizer',
        ];

        foreach ($allowedOptions as $key) {
            if (isset($field[$key])) {
                $fieldDefinition[$key] = $this->convertXMLElementValue((string) $field[$key]);
            }
        }

        return $fieldDefinition;
    }

    /** @return array<string, array<string, mixed>|scalar|null> */
    private function getPartialFilterExpression(SimpleXMLElement $fields): array
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
                $value = $this->convertXMLElementValue((string) $field['value']);
            }

            $partialFilterExpression[(string) $field['name']] = $operator ? ['$' . $operator => $value] : $value;
        }

        return $partialFilterExpression;
    }

    /**
     * Converts XML strings to scalar values.
     *
     * Special strings "false", "true", and "null" are converted to their
     * respective values. Numeric strings are cast to int or float depending on
     * whether they contain decimal separators or not.
     *
     * @return scalar|null
     */
    private function convertXMLElementValue(string $value)
    {
        $value = trim($value);

        switch ($value) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[-]?\d+$/', $value) ? (int) $value : (float) $value;
    }

    /** @param ClassMetadata<object> $class */
    private function setShardKey(ClassMetadata $class, SimpleXMLElement $xmlShardkey): void
    {
        $attributes = $xmlShardkey->attributes();

        $keys    = [];
        $options = [];
        foreach ($xmlShardkey->key as $key) {
            $keys[(string) $key['name']] = (string) ($key['order'] ?? 'asc');
        }

        if (isset($attributes['unique'])) {
            $options['unique'] = ((string) $attributes['unique'] === 'true');
        }

        if (isset($attributes['numInitialChunks'])) {
            $options['numInitialChunks'] = (int) $attributes['numInitialChunks'];
        }

        if (isset($xmlShardkey->option)) {
            foreach ($xmlShardkey->option as $option) {
                $options[(string) $option['name']] = $this->convertXMLElementValue((string) $option['value']);
            }
        }

        $class->setShardKey($keys, $options);
    }

    /**
     * Parses <read-preference> to a format suitable for the underlying driver.
     *
     * list($readPreference, $tags) = $this->transformReadPreference($xml->{read-preference});
     *
     * @return array{string, array<int, array<string, string>>|null}
     */
    private function transformReadPreference(SimpleXMLElement $xmlReadPreference): array
    {
        $tags = null;
        if (isset($xmlReadPreference->{'tag-set'})) {
            $tags = [];
            foreach ($xmlReadPreference->{'tag-set'} as $tagSet) {
                $set = [];
                foreach ($tagSet->tag as $tag) {
                    $set[(string) $tag['name']] = (string) $tag['value'];
                }

                $tags[] = $set;
            }
        }

        return [(string) $xmlReadPreference['mode'], $tags];
    }

    protected function loadMappingFile($file): array
    {
        $result = [];

        $this->validateSchema($file);

        $xmlElement = simplexml_load_file($file);

        foreach (['document', 'embedded-document', 'mapped-superclass', 'query-result-document', 'view', 'gridfs-file'] as $type) {
            if (! isset($xmlElement->$type)) {
                continue;
            }

            foreach ($xmlElement->$type as $documentElement) {
                $documentName          = (string) $documentElement['name'];
                $result[$documentName] = $documentElement;
            }
        }

        return $result;
    }

    private function validateSchema(string $filename): void
    {
        $document = new DOMDocument();
        $document->load($filename);

        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            libxml_clear_errors();

            if (! $document->schemaValidate(__DIR__ . '/../../../../../../doctrine-mongo-mapping.xsd')) {
                throw MappingException::xmlMappingFileInvalid($filename, $this->formatErrors(libxml_get_errors()));
            }
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    /** @param LibXMLError[] $xmlErrors */
    private function formatErrors(array $xmlErrors): string
    {
        return implode("\n", array_map(static fn (LibXMLError $error): string => sprintf('Line %d:%d: %s', $error->line, $error->column, $error->message), $xmlErrors));
    }

    /** @param ClassMetadata<object> $class */
    private function addGridFSMappings(ClassMetadata $class, SimpleXMLElement $xmlRoot): void
    {
        if (! $class->isFile) {
            return;
        }

        foreach (self::DEFAULT_GRIDFS_MAPPINGS as $name => $mapping) {
            if (! isset($xmlRoot->{$name})) {
                continue;
            }

            if (isset($xmlRoot->{$name}->attributes()['field-name'])) {
                $mapping['fieldName'] = (string) $xmlRoot->{$name}->attributes()['field-name'];
            }

            $this->addFieldMapping($class, $mapping);
        }

        if (! isset($xmlRoot->metadata)) {
            return;
        }

        $xmlRoot->metadata->addAttribute('field', 'metadata');
        $this->addEmbedMapping($class, $xmlRoot->metadata, ClassMetadata::ONE);
    }
}

interface_exists(ClassMetadata::class);
