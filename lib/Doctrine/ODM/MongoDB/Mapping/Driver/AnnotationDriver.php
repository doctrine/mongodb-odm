<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractIndex;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ShardKey;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use ReflectionClass;
use ReflectionMethod;
use function array_merge;
use function array_replace;
use function assert;
use function class_exists;
use function constant;
use function count;
use function get_class;
use function interface_exists;
use function is_array;
use function trigger_deprecation;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 */
class AnnotationDriver extends AbstractAnnotationDriver
{
    public function isTransient($className)
    {
        $classAnnotations = $this->reader->getClassAnnotations(new ReflectionClass($className));

        foreach ($classAnnotations as $annot) {
            if ($annot instanceof ODM\AbstractDocument) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, \Doctrine\Persistence\Mapping\ClassMetadata $class) : void
    {
        assert($class instanceof ClassMetadata);
        $reflClass = $class->getReflectionClass();

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);

        $documentAnnot = null;
        foreach ($classAnnotations as $annot) {
            $classAnnotations[get_class($annot)] = $annot;

            if ($annot instanceof ODM\AbstractDocument) {
                if ($documentAnnot !== null) {
                    throw MappingException::classCanOnlyBeMappedByOneAbstractDocument($className, $documentAnnot, $annot);
                }
                $documentAnnot = $annot;
            }

            // non-document class annotations
            if ($annot instanceof ODM\AbstractIndex) {
                $this->addIndex($class, $annot);
            }
            if ($annot instanceof ODM\Indexes) {
                trigger_deprecation(
                    'doctrine/mongodb-odm',
                    '2.2',
                    'The "@Indexes" annotation used in class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" annotations on the class.',
                    $className
                );
                $value = $annot->value;
                foreach (is_array($value) ? $value : [$value] as $index) {
                    $this->addIndex($class, $index);
                }
            } elseif ($annot instanceof ODM\InheritanceType) {
                $class->setInheritanceType(constant(ClassMetadata::class . '::INHERITANCE_TYPE_' . $annot->value));
            } elseif ($annot instanceof ODM\DiscriminatorField) {
                $class->setDiscriminatorField($annot->value);
            } elseif ($annot instanceof ODM\DiscriminatorMap) {
                /** @var array $value */
                $value = $annot->value;
                $class->setDiscriminatorMap($value);
            } elseif ($annot instanceof ODM\DiscriminatorValue) {
                $class->setDiscriminatorValue($annot->value);
            } elseif ($annot instanceof ODM\ChangeTrackingPolicy) {
                $class->setChangeTrackingPolicy(constant(ClassMetadata::class . '::CHANGETRACKING_' . $annot->value));
            } elseif ($annot instanceof ODM\DefaultDiscriminatorValue) {
                $class->setDefaultDiscriminatorValue($annot->value);
            } elseif ($annot instanceof ODM\ReadPreference) {
                $class->setReadPreference($annot->value, $annot->tags ?? []);
            }
        }

        if ($documentAnnot === null) {
            throw MappingException::classIsNotAValidDocument($className);
        }

        if ($documentAnnot instanceof ODM\MappedSuperclass) {
            $class->isMappedSuperclass = true;
        } elseif ($documentAnnot instanceof ODM\EmbeddedDocument) {
            $class->isEmbeddedDocument = true;
        } elseif ($documentAnnot instanceof ODM\QueryResultDocument) {
            $class->isQueryResultDocument = true;
        } elseif ($documentAnnot instanceof ODM\View) {
            if (! $documentAnnot->rootClass) {
                throw MappingException::viewWithoutRootClass($className);
            }

            if (! class_exists($documentAnnot->rootClass)) {
                throw MappingException::viewRootClassNotFound($className, $documentAnnot->rootClass);
            }

            $class->markViewOf($documentAnnot->rootClass);
        } elseif ($documentAnnot instanceof ODM\File) {
            $class->isFile = true;

            if ($documentAnnot->chunkSizeBytes !== null) {
                $class->setChunkSizeBytes($documentAnnot->chunkSizeBytes);
            }
        }

        if (isset($documentAnnot->db)) {
            $class->setDatabase($documentAnnot->db);
        }
        if (isset($documentAnnot->collection)) {
            $class->setCollection($documentAnnot->collection);
        }
        if (isset($documentAnnot->view)) {
            $class->setCollection($documentAnnot->view);
        }
        // Store bucketName as collection name for GridFS files
        if (isset($documentAnnot->bucketName)) {
            $class->setBucketName($documentAnnot->bucketName);
        }
        if (isset($documentAnnot->repositoryClass)) {
            $class->setCustomRepositoryClass($documentAnnot->repositoryClass);
        }
        if (isset($documentAnnot->writeConcern)) {
            $class->setWriteConcern($documentAnnot->writeConcern);
        }
        if (isset($documentAnnot->indexes) && count($documentAnnot->indexes)) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.2',
                'The "indexes" parameter in the "%s" annotation for class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" annotations on the class.',
                $className,
                get_class($documentAnnot)
            );

            foreach ($documentAnnot->indexes as $index) {
                $this->addIndex($class, $index);
            }
        }
        if (! empty($documentAnnot->readOnly)) {
            $class->markReadOnly();
        }

        foreach ($reflClass->getProperties() as $property) {
            if (($class->isMappedSuperclass && ! $property->isPrivate())
                ||
                ($class->isInheritedField($property->name) && $property->getDeclaringClass()->name !== $class->name)) {
                continue;
            }

            $indexes    = [];
            $mapping    = ['fieldName' => $property->getName()];
            $fieldAnnot = null;

            foreach ($this->reader->getPropertyAnnotations($property) as $annot) {
                if ($annot instanceof ODM\AbstractField) {
                    $fieldAnnot = $annot;
                }
                if ($annot instanceof ODM\AbstractIndex) {
                    $indexes[] = $annot;
                }
                if ($annot instanceof ODM\Indexes) {
                    // Setting the type to mixed is a workaround until https://github.com/doctrine/annotations/pull/209 is released.
                    /** @var mixed $value */
                    $value = $annot->value;
                    foreach (is_array($value) ? $value : [$value] as $index) {
                        $indexes[] = $index;
                    }
                } elseif ($annot instanceof ODM\AlsoLoad) {
                    $mapping['alsoLoadFields'] = (array) $annot->value;
                } elseif ($annot instanceof ODM\Version) {
                    $mapping['version'] = true;
                } elseif ($annot instanceof ODM\Lock) {
                    $mapping['lock'] = true;
                }
            }

            if ($fieldAnnot) {
                $mapping = array_replace($mapping, (array) $fieldAnnot);
                $class->mapField($mapping);
            }

            if (! $indexes) {
                continue;
            }

            foreach ($indexes as $index) {
                $name = $mapping['name'] ?? $mapping['fieldName'];
                $keys = [$name => $index->order ?: 'asc'];
                $this->addIndex($class, $index, $keys);
            }
        }

        // Set shard key after all fields to ensure we mapped all its keys
        if (isset($classAnnotations[ShardKey::class])) {
            assert($classAnnotations[ShardKey::class] instanceof ShardKey);
            $this->setShardKey($class, $classAnnotations[ShardKey::class]);
        }

        /** @var ReflectionMethod $method */
        foreach ($reflClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            /* Filter for the declaring class only. Callbacks from parent
             * classes will already be registered.
             */
            if ($method->getDeclaringClass()->name !== $reflClass->name) {
                continue;
            }

            foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                if ($annot instanceof ODM\AlsoLoad) {
                    $class->registerAlsoLoadMethod($method->getName(), $annot->value);
                }

                if (! isset($classAnnotations[ODM\HasLifecycleCallbacks::class])) {
                    continue;
                }

                if ($annot instanceof ODM\PrePersist) {
                    $class->addLifecycleCallback($method->getName(), Events::prePersist);
                } elseif ($annot instanceof ODM\PostPersist) {
                    $class->addLifecycleCallback($method->getName(), Events::postPersist);
                } elseif ($annot instanceof ODM\PreUpdate) {
                    $class->addLifecycleCallback($method->getName(), Events::preUpdate);
                } elseif ($annot instanceof ODM\PostUpdate) {
                    $class->addLifecycleCallback($method->getName(), Events::postUpdate);
                } elseif ($annot instanceof ODM\PreRemove) {
                    $class->addLifecycleCallback($method->getName(), Events::preRemove);
                } elseif ($annot instanceof ODM\PostRemove) {
                    $class->addLifecycleCallback($method->getName(), Events::postRemove);
                } elseif ($annot instanceof ODM\PreLoad) {
                    $class->addLifecycleCallback($method->getName(), Events::preLoad);
                } elseif ($annot instanceof ODM\PostLoad) {
                    $class->addLifecycleCallback($method->getName(), Events::postLoad);
                } elseif ($annot instanceof ODM\PreFlush) {
                    $class->addLifecycleCallback($method->getName(), Events::preFlush);
                }
            }
        }
    }

    private function addIndex(ClassMetadata $class, AbstractIndex $index, array $keys = []) : void
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

    /**
     * @throws MappingException
     */
    private function setShardKey(ClassMetadata $class, ODM\ShardKey $shardKey) : void
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
     * Factory method for the Annotation Driver
     *
     * @param string[]|string $paths
     */
    public static function create($paths = [], ?Reader $reader = null) : AnnotationDriver
    {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}

interface_exists(\Doctrine\Persistence\Mapping\ClassMetadata::class);
