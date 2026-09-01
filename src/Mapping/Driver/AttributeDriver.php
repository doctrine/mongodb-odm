<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ClassLocator;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use MongoDB\BSON\Document;
use MongoDB\Driver\Exception\UnexpectedValueException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function array_any;
use function array_find;
use function array_merge;
use function array_replace;
use function assert;
use function class_exists;
use function constant;

/**
 * The AttributeDriver reads the mapping metadata from attributes.
 */
class AttributeDriver implements MappingDriver
{
    use ColocatedMappingDriver;

    private AttributeReader $reader;

    /** @param string|string[]|ClassLocator|null $paths */
    public function __construct(string|array|ClassLocator|null $paths = null)
    {
        $this->reader = new AttributeReader();

        if ($paths instanceof ClassLocator) {
            $this->classLocator = $paths;
        } else {
            $this->addPaths((array) $paths);
        }
    }

    public function isTransient(string $className): bool
    {
        $classAttributes = $this->getClassAttributes(new ReflectionClass($className));

        foreach ($classAttributes as $attribute) {
            if ($attribute instanceof ODM\AbstractDocument) {
                return false;
            }
        }

        return true;
    }

    public function loadMetadataForClass(string $className, PersistenceClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $reflClass = $metadata->getReflectionClass();

        $classAttributes = $this->getClassAttributes($reflClass);

        $documentAttribute = null;
        foreach ($classAttributes as $attribute) {
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

            if ($attribute instanceof ODM\VectorSearchIndex) {
                $this->addVectorSearchIndex($metadata, $attribute);
            }

            if ($attribute instanceof ODM\InheritanceType) {
                $metadata->setInheritanceType(constant(ClassMetadata::class . '::INHERITANCE_TYPE_' . $attribute->value));
            } elseif ($attribute instanceof ODM\DiscriminatorField) {
                $metadata->setDiscriminatorField($attribute->value);
            } elseif ($attribute instanceof ODM\DiscriminatorMap) {
                $metadata->setDiscriminatorMap($attribute->value);
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
            } elseif ($attribute instanceof ODM\Encrypt) {
                $metadata->markAsEncrypted();
            }
        }

        if ($documentAttribute === null) {
            throw MappingException::classIsNotAValidDocument($className);
        }

        if ($documentAttribute instanceof ODM\MappedSuperclass) {
            $metadata->markAsMappedSuperclass();
        } elseif ($documentAttribute instanceof ODM\EmbeddedDocument) {
            $metadata->markAsEmbeddedDocument();
        } elseif ($documentAttribute instanceof ODM\QueryResultDocument) {
            $metadata->markAsQueryResultDocument();
        } elseif ($documentAttribute instanceof ODM\View) {
            if (! $documentAttribute->rootClass) {
                throw MappingException::viewWithoutRootClass($className);
            }

            if (! class_exists($documentAttribute->rootClass)) {
                throw MappingException::viewRootClassNotFound($className, $documentAttribute->rootClass);
            }

            $metadata->markViewOf($documentAttribute->rootClass);
        } elseif ($documentAttribute instanceof ODM\File) {
            $metadata->markAsFile();

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

                if ($propertyAttribute instanceof ODM\AlsoLoad) {
                    $mapping['alsoLoadFields'] = (array) $propertyAttribute->value;
                } elseif ($propertyAttribute instanceof ODM\Version) {
                    $mapping['version'] = true;
                } elseif ($propertyAttribute instanceof ODM\Lock) {
                    $mapping['lock'] = true;
                } elseif ($propertyAttribute instanceof ODM\Encrypt) {
                    $mapping['encrypt'] = (array) $propertyAttribute;
                } elseif ($propertyAttribute instanceof ODM\Id) {
                    $mapping['id'] = true;
                } elseif (
                    $propertyAttribute instanceof ODM\EmbedOne
                    || $propertyAttribute instanceof ODM\EmbedMany
                    || $propertyAttribute instanceof ODM\File\Metadata
                ) {
                    $mapping['embedded'] = true;
                } elseif ($propertyAttribute instanceof ODM\ReferenceOne || $propertyAttribute instanceof ODM\ReferenceMany) {
                    $mapping['reference'] = true;
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
        $attribute = array_find($classAttributes, static fn ($attr) => $attr instanceof ODM\ShardKey);
        if ($attribute) {
            $this->setShardKey($metadata, $attribute);
        }

        // Mark as time series only after mapping all fields
        $attribute = array_find($classAttributes, static fn ($attr) => $attr instanceof ODM\TimeSeries);
        if ($attribute) {
            $metadata->markAsTimeSeries($attribute);
        }

        $hasLifecycleCallbacks = array_any($classAttributes, static fn ($attr) => $attr instanceof ODM\HasLifecycleCallbacks);
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

                if (! $hasLifecycleCallbacks) {
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

    /** @param array<string, int|string> $keys */
    private function addIndex(ClassMetadata $class, ODM\AbstractIndex $index, array $keys = []): void
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

    private function addSearchIndex(ClassMetadata $class, ODM\SearchIndex $index): void
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

        $class->addSearchIndex($definition, $index->name ?? null, 'search');
    }

    private function addVectorSearchIndex(ClassMetadata $class, ODM\VectorSearchIndex $index): void
    {
        $definition = [
            'fields' => $index->fields,
        ];

        $class->addSearchIndex($definition, $index->name ?? null, 'vectorSearch');
    }

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

    /**
     * Factory method for the Attribute Driver
     *
     * @param string|string[]|ClassLocator $paths
     */
    public static function create(string|array|ClassLocator $paths = []): self
    {
        return new self($paths);
    }

    /** @return object[] */
    private function getClassAttributes(ReflectionClass $class): array
    {
        return $this->reader->getClassAttributes($class);
    }

    /** @return object[] */
    private function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->reader->getMethodAttributes($method);
    }

    /** @return object[] */
    private function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->reader->getPropertyAttributes($property);
    }
}
