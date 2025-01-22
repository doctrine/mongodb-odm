<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractIndex;
use Doctrine\ODM\MongoDB\Mapping\Annotations\SearchIndex;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ShardKey;
use Doctrine\ODM\MongoDB\Mapping\Annotations\TimeSeries;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use MongoDB\BSON\Document;
use MongoDB\Driver\Exception\UnexpectedValueException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function array_merge;
use function array_replace;
use function assert;
use function class_exists;
use function constant;
use function count;
use function is_array;
use function trigger_deprecation;

/**
 * The AtttributeDriver reads the mapping metadata from attributes.
 */
class AttributeDriver implements MappingDriver
{
    use ColocatedMappingDriver;

    /**
     * @internal this property will be private in 3.0
     *
     * @var Reader|AttributeReader
     */
    protected $reader;

    /** @param string|string[]|null $paths */
    public function __construct($paths = null, ?Reader $reader = null)
    {
        if ($reader !== null) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.7',
                'Passing a $reader parameter to %s is deprecated',
                __METHOD__,
            );
        }

        $this->reader = $reader ?? new AttributeReader();

        $this->addPaths((array) $paths);
    }

    public function isTransient($className): bool
    {
        $classAttributes = $this->getClassAttributes(new ReflectionClass($className));

        foreach ($classAttributes as $attribute) {
            if ($attribute instanceof ODM\AbstractDocument) {
                return false;
            }
        }

        return true;
    }

    public function loadMetadataForClass($className, PersistenceClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $reflClass = $metadata->getReflectionClass();

        $classAttributes = $this->getClassAttributes($reflClass);

        $documentAttribute = null;
        foreach ($classAttributes as $attribute) {
            $classAttributes[$attribute::class] = $attribute;

            if ($attribute instanceof ODM\AbstractDocument) {
                if ($documentAttribute !== null) {
                    throw MappingException::classCanOnlyBeMappedByOneAbstractDocument($className, $documentAttribute, $attribute);
                }

                $documentAttribute = $attribute;
            }

            // non-document class attributes
            if ($attribute instanceof ODM\AbstractIndex) {
                $this->addIndex($metadata, $attribute);
            }

            if ($attribute instanceof ODM\SearchIndex) {
                $this->addSearchIndex($metadata, $attribute);
            }

            if ($attribute instanceof ODM\Indexes) {
                trigger_deprecation(
                    'doctrine/mongodb-odm',
                    '2.2',
                    'The "@Indexes" attribute used in class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.',
                    $className,
                );
                $value = $attribute->value;
                foreach (is_array($value) ? $value : [$value] as $index) {
                    $this->addIndex($metadata, $index);
                }
            } elseif ($attribute instanceof ODM\InheritanceType) {
                $metadata->setInheritanceType(constant(ClassMetadata::class . '::INHERITANCE_TYPE_' . $attribute->value));
            } elseif ($attribute instanceof ODM\DiscriminatorField) {
                $metadata->setDiscriminatorField($attribute->value);
            } elseif ($attribute instanceof ODM\DiscriminatorMap) {
                $value = $attribute->value;
                assert(is_array($value));
                $metadata->setDiscriminatorMap($value);
            } elseif ($attribute instanceof ODM\DiscriminatorValue) {
                $metadata->setDiscriminatorValue($attribute->value);
            } elseif ($attribute instanceof ODM\ChangeTrackingPolicy) {
                $metadata->setChangeTrackingPolicy(constant(ClassMetadata::class . '::CHANGETRACKING_' . $attribute->value));
            } elseif ($attribute instanceof ODM\DefaultDiscriminatorValue) {
                $metadata->setDefaultDiscriminatorValue($attribute->value);
            } elseif ($attribute instanceof ODM\ReadPreference) {
                $metadata->setReadPreference($attribute->value, $attribute->tags ?? []);
            } elseif ($attribute instanceof ODM\Validation) {
                if (isset($attribute->validator)) {
                    try {
                        $validatorBson = Document::fromJSON($attribute->validator);
                    } catch (UnexpectedValueException $e) {
                        throw MappingException::schemaValidationError($e->getCode(), $e->getMessage(), $className, 'validator');
                    }

                    $validator = $validatorBson->toPHP();
                    $metadata->setValidator($validator);
                }

                if (isset($attribute->action)) {
                    $metadata->setValidationAction($attribute->action);
                }

                if (isset($attribute->level)) {
                    $metadata->setValidationLevel($attribute->level);
                }
            }
        }

        if ($documentAttribute === null) {
            throw MappingException::classIsNotAValidDocument($className);
        }

        if ($documentAttribute instanceof ODM\MappedSuperclass) {
            $metadata->isMappedSuperclass = true;
        } elseif ($documentAttribute instanceof ODM\EmbeddedDocument) {
            $metadata->isEmbeddedDocument = true;
        } elseif ($documentAttribute instanceof ODM\QueryResultDocument) {
            $metadata->isQueryResultDocument = true;
        } elseif ($documentAttribute instanceof ODM\View) {
            if (! $documentAttribute->rootClass) {
                throw MappingException::viewWithoutRootClass($className);
            }

            if (! class_exists($documentAttribute->rootClass)) {
                throw MappingException::viewRootClassNotFound($className, $documentAttribute->rootClass);
            }

            $metadata->markViewOf($documentAttribute->rootClass);
        } elseif ($documentAttribute instanceof ODM\File) {
            $metadata->isFile = true;

            if ($documentAttribute->chunkSizeBytes !== null) {
                $metadata->setChunkSizeBytes($documentAttribute->chunkSizeBytes);
            }
        }

        if (isset($documentAttribute->db)) {
            $metadata->setDatabase($documentAttribute->db);
        }

        if (isset($documentAttribute->collection)) {
            $metadata->setCollection($documentAttribute->collection);
        }

        if (isset($documentAttribute->view)) {
            $metadata->setCollection($documentAttribute->view);
        }

        // Store bucketName as collection name for GridFS files
        if (isset($documentAttribute->bucketName)) {
            $metadata->setBucketName($documentAttribute->bucketName);
        }

        if (isset($documentAttribute->repositoryClass)) {
            $metadata->setCustomRepositoryClass($documentAttribute->repositoryClass);
        }

        if (isset($documentAttribute->writeConcern)) {
            $metadata->setWriteConcern($documentAttribute->writeConcern);
        }

        if (isset($documentAttribute->indexes) && count($documentAttribute->indexes)) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.2',
                'The "indexes" parameter in the "%s" attribute for class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.',
                $documentAttribute::class,
                $className,
            );

            foreach ($documentAttribute->indexes as $index) {
                $this->addIndex($metadata, $index);
            }
        }

        if (! empty($documentAttribute->readOnly)) {
            $metadata->markReadOnly();
        }

        foreach ($reflClass->getProperties() as $property) {
            if (
                ($metadata->isMappedSuperclass && ! $property->isPrivate())
                ||
                ($metadata->isInheritedField($property->name) && $property->getDeclaringClass()->name !== $metadata->name)
            ) {
                continue;
            }

            $indexes        = [];
            $mapping        = ['fieldName' => $property->getName()];
            $fieldAttribute = null;

            foreach ($this->getPropertyAttributes($property) as $propertyAttribute) {
                if ($propertyAttribute instanceof ODM\AbstractField) {
                    $fieldAttribute = $propertyAttribute;
                }

                if ($propertyAttribute instanceof ODM\AbstractIndex) {
                    $indexes[] = $propertyAttribute;
                }

                if ($propertyAttribute instanceof ODM\Indexes) {
                    trigger_deprecation(
                        'doctrine/mongodb-odm',
                        '2.2',
                        'The "@Indexes" attribute used in property "%s" of class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.',
                        $property->getName(),
                        $className,
                    );

                    $value = $propertyAttribute->value;
                    foreach (is_array($value) ? $value : [$value] as $index) {
                        $indexes[] = $index;
                    }
                } elseif ($propertyAttribute instanceof ODM\AlsoLoad) {
                    $mapping['alsoLoadFields'] = (array) $propertyAttribute->value;
                } elseif ($propertyAttribute instanceof ODM\Version) {
                    $mapping['version'] = true;
                } elseif ($propertyAttribute instanceof ODM\Lock) {
                    $mapping['lock'] = true;
                }
            }

            if ($fieldAttribute) {
                $mapping = array_replace($mapping, (array) $fieldAttribute);
                $metadata->mapField($mapping);
            }

            if (! $indexes) {
                continue;
            }

            foreach ($indexes as $index) {
                $name = $mapping['name'] ?? $mapping['fieldName'];
                $keys = [$name => $index->order ?: 'asc'];
                $this->addIndex($metadata, $index, $keys);
            }
        }

        // Set shard key after all fields to ensure we mapped all its keys
        if (isset($classAttributes[ShardKey::class])) {
            assert($classAttributes[ShardKey::class] instanceof ShardKey);
            $this->setShardKey($metadata, $classAttributes[ShardKey::class]);
        }

        // Mark as time series only after mapping all fields
        if (isset($classAttributes[TimeSeries::class])) {
            assert($classAttributes[TimeSeries::class] instanceof TimeSeries);
            $metadata->markAsTimeSeries($classAttributes[TimeSeries::class]);
        }

        foreach ($reflClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            /* Filter for the declaring class only. Callbacks from parent
             * classes will already be registered.
             */
            if ($method->getDeclaringClass()->name !== $reflClass->name) {
                continue;
            }

            foreach ($this->getMethodAttributes($method) as $methodAttribute) {
                if ($methodAttribute instanceof ODM\AlsoLoad) {
                    $metadata->registerAlsoLoadMethod($method->getName(), $methodAttribute->value);
                }

                if (! isset($classAttributes[ODM\HasLifecycleCallbacks::class])) {
                    continue;
                }

                if ($methodAttribute instanceof ODM\PrePersist) {
                    $metadata->addLifecycleCallback($method->getName(), Events::prePersist);
                } elseif ($methodAttribute instanceof ODM\PostPersist) {
                    $metadata->addLifecycleCallback($method->getName(), Events::postPersist);
                } elseif ($methodAttribute instanceof ODM\PreUpdate) {
                    $metadata->addLifecycleCallback($method->getName(), Events::preUpdate);
                } elseif ($methodAttribute instanceof ODM\PostUpdate) {
                    $metadata->addLifecycleCallback($method->getName(), Events::postUpdate);
                } elseif ($methodAttribute instanceof ODM\PreRemove) {
                    $metadata->addLifecycleCallback($method->getName(), Events::preRemove);
                } elseif ($methodAttribute instanceof ODM\PostRemove) {
                    $metadata->addLifecycleCallback($method->getName(), Events::postRemove);
                } elseif ($methodAttribute instanceof ODM\PreLoad) {
                    $metadata->addLifecycleCallback($method->getName(), Events::preLoad);
                } elseif ($methodAttribute instanceof ODM\PostLoad) {
                    $metadata->addLifecycleCallback($method->getName(), Events::postLoad);
                } elseif ($methodAttribute instanceof ODM\PreFlush) {
                    $metadata->addLifecycleCallback($method->getName(), Events::preFlush);
                }
            }
        }
    }

    /**
     * @param ClassMetadata<object>     $class
     * @param array<string, int|string> $keys
     */
    private function addIndex(ClassMetadata $class, AbstractIndex $index, array $keys = []): void
    {
        $keys    = array_merge($keys, $index->keys);
        $options = [];
        $allowed = ['name', 'background', 'unique', 'sparse', 'expireAfterSeconds'];
        foreach ($allowed as $name) {
            if (! isset($index->$name)) {
                continue;
            }

            $options[$name] = $index->$name;
        }

        if (! empty($index->partialFilterExpression)) {
            $options['partialFilterExpression'] = $index->partialFilterExpression;
        }

        $options = array_merge($options, $index->options);
        $class->addIndex($keys, $options);
    }

    /** @param ClassMetadata<object> $class */
    private function addSearchIndex(ClassMetadata $class, SearchIndex $index): void
    {
        $definition = [];

        foreach (['dynamic', 'fields'] as $key) {
            if (isset($index->$key)) {
                $definition['mappings'][$key] = $index->$key;
            }
        }

        foreach (['analyzer', 'searchAnalyzer', 'analyzers', 'storedSource', 'synonyms'] as $key) {
            if (isset($index->$key)) {
                $definition[$key] = $index->$key;
            }
        }

        $class->addSearchIndex($definition, $index->name ?? null);
    }

    /**
     * @param ClassMetadata<object> $class
     *
     * @throws MappingException
     */
    private function setShardKey(ClassMetadata $class, ODM\ShardKey $shardKey): void
    {
        $options = [];
        $allowed = ['unique', 'numInitialChunks'];
        foreach ($allowed as $name) {
            if (! isset($shardKey->$name)) {
                continue;
            }

            $options[$name] = $shardKey->$name;
        }

        $class->setShardKey($shardKey->keys, $options);
    }

    /** @return Reader|AttributeReader */
    public function getReader()
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.4',
            '%s is deprecated with no replacement',
            __METHOD__,
        );

        return $this->reader;
    }

    /**
     * Factory method for the Attribute Driver
     *
     * @param string[]|string $paths
     *
     * @return AttributeDriver
     */
    public static function create($paths = [], ?Reader $reader = null)
    {
        return new self($paths, $reader);
    }

    /** @return object[] */
    private function getClassAttributes(ReflectionClass $class): array
    {
        if ($this->reader instanceof AttributeReader) {
            return $this->reader->getClassAttributes($class);
        }

        return $this->reader->getClassAnnotations($class);
    }

    /** @return object[] */
    private function getMethodAttributes(ReflectionMethod $method): array
    {
        if ($this->reader instanceof AttributeReader) {
            return $this->reader->getMethodAttributes($method);
        }

        return $this->reader->getMethodAnnotations($method);
    }

    /** @return object[] */
    private function getPropertyAttributes(ReflectionProperty $property): array
    {
        if ($this->reader instanceof AttributeReader) {
            return $this->reader->getPropertyAttributes($property);
        }

        return $this->reader->getPropertyAnnotations($property);
    }
}
