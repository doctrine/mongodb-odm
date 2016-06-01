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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MappingClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @since       1.0
 */
class AnnotationDriver extends AbstractAnnotationDriver
{
    protected $entityAnnotationClasses = array(
        ODM\Document::class         => 1,
        ODM\MappedSuperclass::class => 2,
        ODM\EmbeddedDocument::class => 3,
    );

    /**
     * Registers annotation classes to the common registry.
     *
     * This method should be called when bootstrapping your application.
     */
    public static function registerAnnotationClasses()
    {
        AnnotationRegistry::registerFile(__DIR__ . '/../Annotations/DoctrineAnnotations.php');
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        /** @var $class ClassMetadataInfo */
        $reflClass = $class->getReflectionClass();

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);

        $documentAnnots = array();
        foreach ($classAnnotations as $annot) {
            $classAnnotations[get_class($annot)] = $annot;

            foreach ($this->entityAnnotationClasses as $annotClass => $i) {
                if ($annot instanceof $annotClass) {
                    $documentAnnots[$i] = $annot;
                    continue 2;
                }
            }

            // non-document class annotations
            if ($annot instanceof ODM\AbstractIndex) {
                $this->addIndex($class, $annot);
            }
            if ($annot instanceof ODM\Indexes) {
                foreach (is_array($annot->value) ? $annot->value : array($annot->value) as $index) {
                    $this->addIndex($class, $index);
                }
            } elseif ($annot instanceof ODM\InheritanceType) {
                $class->setInheritanceType(constant(MappingClassMetadata::class . '::INHERITANCE_TYPE_'.$annot->value));
            } elseif ($annot instanceof ODM\DiscriminatorField) {
                // $fieldName property is deprecated, but fall back for BC
                if (isset($annot->value)) {
                    $class->setDiscriminatorField($annot->value);
                } elseif (isset($annot->name)) {
                    $class->setDiscriminatorField($annot->name);
                } elseif (isset($annot->fieldName)) {
                    $class->setDiscriminatorField($annot->fieldName);
                }
            } elseif ($annot instanceof ODM\DiscriminatorMap) {
                $class->setDiscriminatorMap($annot->value);
            } elseif ($annot instanceof ODM\DiscriminatorValue) {
                $class->setDiscriminatorValue($annot->value);
            } elseif ($annot instanceof ODM\ChangeTrackingPolicy) {
                $class->setChangeTrackingPolicy(constant(MappingClassMetadata::class . '::CHANGETRACKING_'.$annot->value));
            } elseif ($annot instanceof ODM\DefaultDiscriminatorValue) {
                $class->setDefaultDiscriminatorValue($annot->value);
            }

        }

        if ( ! $documentAnnots) {
            throw MappingException::classIsNotAValidDocument($className);
        }

        // find the winning document annotation
        ksort($documentAnnots);
        $documentAnnot = reset($documentAnnots);

        if ($documentAnnot instanceof ODM\MappedSuperclass) {
            $class->isMappedSuperclass = true;
        } elseif ($documentAnnot instanceof ODM\EmbeddedDocument) {
            $class->isEmbeddedDocument = true;
        }
        if (isset($documentAnnot->db)) {
            $class->setDatabase($documentAnnot->db);
        }
        if (isset($documentAnnot->collection)) {
            $class->setCollection($documentAnnot->collection);
        }
        if (isset($documentAnnot->repositoryClass) && !$class->isEmbeddedDocument) {
            $class->setCustomRepositoryClass($documentAnnot->repositoryClass);
        }
        if (isset($documentAnnot->writeConcern)) {
            $class->setWriteConcern($documentAnnot->writeConcern);
        }
        if (isset($documentAnnot->indexes)) {
            foreach ($documentAnnot->indexes as $index) {
                $this->addIndex($class, $index);
            }
        }
        if (isset($documentAnnot->requireIndexes)) {
            $class->setRequireIndexes($documentAnnot->requireIndexes);
        }
        if (isset($documentAnnot->slaveOkay)) {
            $class->setSlaveOkay($documentAnnot->slaveOkay);
        }

        foreach ($reflClass->getProperties() as $property) {
            if (($class->isMappedSuperclass && ! $property->isPrivate())
                ||
                ($class->isInheritedField($property->name) && $property->getDeclaringClass()->name !== $class->name)) {
                continue;
            }

            $indexes = array();
            $mapping = array('fieldName' => $property->getName());
            $fieldAnnot = null;

            foreach ($this->reader->getPropertyAnnotations($property) as $annot) {
                if ($annot instanceof ODM\AbstractField) {
                    $fieldAnnot = $annot;
                }
                if ($annot instanceof ODM\AbstractIndex) {
                    $indexes[] = $annot;
                }
                if ($annot instanceof ODM\Indexes) {
                    foreach (is_array($annot->value) ? $annot->value : array($annot->value) as $index) {
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

            if ($indexes) {
                foreach ($indexes as $index) {
                    $name = isset($mapping['name']) ? $mapping['name'] : $mapping['fieldName'];
                    $keys = array($name => $index->order ?: 'asc');
                    $this->addIndex($class, $index, $keys);
                }
            }
        }

        // Set shard key after all fields to ensure we mapped all its keys
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\ShardKey'])) {
            $this->setShardKey($class, $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\ShardKey']);
        }

        /** @var $method \ReflectionMethod */
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
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

                if ( ! isset($classAnnotations[ODM\HasLifecycleCallbacks::class])) {
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

    private function addIndex(ClassMetadataInfo $class, $index, array $keys = array())
    {
        $keys = array_merge($keys, $index->keys);
        $options = array();
        $allowed = array('name', 'dropDups', 'background', 'safe', 'unique', 'sparse', 'expireAfterSeconds');
        foreach ($allowed as $name) {
            if (isset($index->$name)) {
                $options[$name] = $index->$name;
            }
        }
        if (! empty($index->partialFilterExpression)) {
            $options['partialFilterExpression'] = $index->partialFilterExpression;
        }
        $options = array_merge($options, $index->options);
        $class->addIndex($keys, $options);
    }

    /**
     * @param ClassMetadataInfo $class
     * @param ODM\ShardKey      $shardKey
     *
     * @throws MappingException
     */
    private function setShardKey(ClassMetadataInfo $class, ODM\ShardKey $shardKey)
    {
        $options = array();
        $allowed = array('unique', 'numInitialChunks');
        foreach ($allowed as $name) {
            if (isset($shardKey->$name)) {
                $options[$name] = $shardKey->$name;
            }
        }

        $class->setShardKey($shardKey->keys, $options);
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param Reader $reader
     * @return AnnotationDriver
     */
    public static function create($paths = array(), Reader $reader = null)
    {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }
        return new self($reader, $paths);
    }
}
