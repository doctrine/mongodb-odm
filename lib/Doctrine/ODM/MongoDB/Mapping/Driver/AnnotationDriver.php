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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Annotations\Reader,
    Doctrine\ODM\MongoDB\Events,
    Doctrine\ODM\MongoDB\Mapping\Annotations as ODM,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo,
    Doctrine\ODM\MongoDB\MongoDBException;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class AnnotationDriver implements Driver
{
    /**
     * Document annotation classes, ordered by precedence.
     */
    static private $documentAnnotationClasses = array(
        'Doctrine\\ODM\\MongoDB\\Mapping\\Annotations\\Document',
        'Doctrine\\ODM\\MongoDB\\Mapping\\Annotations\\MappedSuperclass',
        'Doctrine\\ODM\\MongoDB\\Mapping\\Annotations\\EmbeddedDocument',
    );

    /**
     * The annotation reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    private $paths = array();

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    private $fileExtension = '.php';

    /**
     * @param array
     */
    private $classNames;

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
     * Initializes a new AnnotationDriver that uses the given Reader for reading
     * docblock annotations.
     * 
     * @param $reader Reader The annotation reader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found. 
     */
    public function __construct(Reader $reader, $paths = null)
    {
        $this->reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    /**
     * Append lookup paths to metadata driver.
     *
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $class)
    {
        $reflClass = $class->getReflectionClass();

        $documentAnnots = array();
        foreach ($this->reader->getClassAnnotations($reflClass) as $annot) {
            foreach (self::$documentAnnotationClasses as $i => $annotClass) {
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
                $class->setInheritanceType(constant('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata::INHERITANCE_TYPE_'.$annot->value));
            } elseif ($annot instanceof ODM\DiscriminatorField) {
                $class->setDiscriminatorField(array('fieldName' => $annot->fieldName));
            } elseif ($annot instanceof ODM\DiscriminatorMap) {
                $class->setDiscriminatorMap($annot->value);
            } elseif ($annot instanceof ODM\DiscriminatorValue) {
                $class->setDiscriminatorValue($annot->value);
            } elseif ($annot instanceof ODM\ChangeTrackingPolicy) {
                $class->setChangeTrackingPolicy(constant('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadata::CHANGETRACKING_'.$annot->value));
            }

        }

        if (!$documentAnnots) {
            throw MongoDBException::classIsNotAValidDocument($className);
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
        if (isset($documentAnnot->repositoryClass)) {
            $class->setCustomRepositoryClass($documentAnnot->repositoryClass);
        }
        if (isset($documentAnnot->indexes)) {
            foreach ($documentAnnot->indexes as $index) {
                $this->addIndex($class, $index);
            }
        }

        foreach ($reflClass->getProperties() as $property) {
            if ($class->isMappedSuperclass && !$property->isPrivate() || $class->isInheritedField($property->name)) {
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

        foreach ($reflClass->getMethods() as $method) {
            if ($method->isPublic()) {
                foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                    if ($annot instanceof ODM\AlsoLoad) {
                        foreach (is_array($annot->value) ? $annot->value : array($annot->value) as $field) {
                            $class->alsoLoadMethods[$field] = $method->getName();
                        }
                    } elseif ($annot instanceof ODM\PrePersist) {
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
                    }
                }
            }
        }
    }

    private function addIndex(ClassMetadataInfo $class, $index, array $keys = array())
    {
        $keys = array_merge($keys, $index->keys);
        $options = array();
        $allowed = array('name', 'dropDups', 'background', 'safe', 'unique');
        foreach ($allowed as $name) {
            if (isset($index->$name)) {
                $options[$name] = $index->$name;
            }
        }
        $options = array_merge($options, $index->options);
        $class->addIndex($keys, $options);
    }

    /**
     * Whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     * A class is non-transient if it is annotated with either @Entity or
     * @MappedSuperclass in the class doc block.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $classAnnotations = $this->reader->getClassAnnotations(new \ReflectionClass($className));

        foreach ($classAnnotations as $annot) {
            if ($annot instanceof ODM\AbstractDocument) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if ( ! $this->paths) {
            throw MongoDBException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->paths as $path) {
            if ( ! is_dir($path)) {
                throw MongoDBException::fileMappingDriversRequireConfiguredDirectoryPath();
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename($this->fileExtension)) == $file->getBasename()) {
                    continue;
                }

                $sourceFile = realpath($file->getPathName());
                require_once $sourceFile;
                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && ! $this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->classNames = $classes;

        return $classes;
    }

    /**
     * Factory method for the Annotation Driver
     * 
     * @param array|string $paths
     * @param Reader $reader
     * @return AnnotationDriver
     */
    static public function create($paths = array(), Reader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
        }
        return new self($reader, $paths);
    }
}
