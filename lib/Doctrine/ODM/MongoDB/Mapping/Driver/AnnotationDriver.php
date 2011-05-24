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
    Doctrine\Common\Annotations\IndexedReader,
    Doctrine\Common\Annotations\Reader,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo,
    Doctrine\ODM\MongoDB\MongoDBException;

require __DIR__ . '/../Annotations/DoctrineAnnotations.php';

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

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Document'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Document'];
        } elseif (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass'];
            $class->isMappedSuperclass = true;
        } elseif (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument'];
            $class->isEmbeddedDocument = true;
        } else {
            throw MongoDBException::classIsNotAValidDocument($className);
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
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Indexes'])) {
            $indexes = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Indexes']->value;
            $indexes = is_array($indexes) ? $indexes : array($indexes);
            foreach ($indexes as $index) {
                $this->addIndex($class, $index);
            }
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Index'])) {
            $index = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Index'];
            $this->addIndex($class, $index);
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\UniqueIndex'])) {
            $index = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\UniqueIndex'];
            $this->addIndex($class, $index);
        }
        if (isset($documentAnnot->indexes)) {
            foreach($documentAnnot->indexes as $index) {
                $this->addIndex($class, $index);
            }
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\InheritanceType'])) {
            $inheritanceTypeAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\InheritanceType'];
            $class->setInheritanceType(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value));
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorField'])) {
            $discrFieldAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorField'];
            $class->setDiscriminatorField(array(
                'fieldName' => $discrFieldAnnot->fieldName,
            ));
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorMap'])) {
            $discrMapAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorMap'];
            $class->setDiscriminatorMap($discrMapAnnot->value);
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorValue'])) {
            $discrValueAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorValue'];
            $class->setDiscriminatorValue($discrValueAnnot->value);
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\ChangeTrackingPolicy'])) {
            $changeTrackingAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\ChangeTrackingPolicy'];
            $class->setChangeTrackingPolicy(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAnnot->value));
        }

        $methods = $reflClass->getMethods();

        foreach ($reflClass->getProperties() as $property) {
            if ($class->isMappedSuperclass && ! $property->isPrivate()
                || $class->isInheritedField($property->name)) {
                continue;
            }
            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            if ($alsoLoad = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\AlsoLoad')) {
                $mapping['alsoLoadFields'] = (array) $alsoLoad->value;
            }
            if ($notSaved = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\NotSaved')) {
                $mapping['notSaved'] = true;
            }

            if ($versionAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Version')) {
                $mapping['version'] = true;
            }

            if ($versionAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Lock')) {
                $mapping['lock'] = true;
            }

            $indexes = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Indexes');
            $indexes = $indexes ? $indexes : array();
            if ($index = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Index')) {
                $indexes[] = $index;
            }
            if ($index = $this->reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\Annotations\UniqueIndex')) {
                $indexes[] = $index;
            }
            foreach ($this->reader->getPropertyAnnotations($property) as $fieldAnnot) {
                if ($fieldAnnot instanceof \Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapField($mapping);
                }
            }
            if ($indexes) {
                foreach ($indexes as $index) {
                    $name = isset($mapping['name']) ? $mapping['name'] : $mapping['fieldName'];
                    $keys = array();
                    $keys[$name] = 'asc';
                    if (isset($index->order)) {
                        $keys[$name] = $index->order;
                    }
                    $this->addIndex($class, $index, $keys);
                }
            }
        }

        foreach ($methods as $method) {
            if ($method->isPublic()) {
                if ($alsoLoad = $this->reader->getMethodAnnotation($method, 'Doctrine\ODM\MongoDB\Mapping\Annotations\AlsoLoad')) {
                    $fields = (array) $alsoLoad->value;
                    foreach ($fields as $value) {
                        $class->alsoLoadMethods[$value] = $method->getName();
                    }
                }
            }
        }
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks'])) {
            foreach ($methods as $method) {
                if ($method->isPublic()) {
                    $annotations = $this->reader->getMethodAnnotations($method);

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PrePersist'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::prePersist);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PostPersist'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postPersist);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::preUpdate);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PostUpdate'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postUpdate);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PreRemove'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::preRemove);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PostRemove'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postRemove);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PreLoad'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::preLoad);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\Annotations\PostLoad'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postLoad);
                    }
                }
            }
        }
    }

    private function addIndex(ClassMetadata $class, $index, array $keys = array())
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

        return ! isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\Document']) &&
               ! isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass']) &&
               ! isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument']);
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
            $reader = new IndexedReader(new AnnotationReader());
        }
        return new self($reader, $paths);
    }
}