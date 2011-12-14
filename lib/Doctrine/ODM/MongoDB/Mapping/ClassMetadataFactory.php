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

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\Cache\Cache,
    Doctrine\ODM\MongoDB\Mapping\Types\Type;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a document database.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class ClassMetadataFactory implements \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
{
    /** The DocumentManager instance */
    private $dm;

    /** The Configuration instance */
    private $config;

    /** The array of loaded ClassMetadata instances */
    private $loadedMetadata;

    /** The used metadata driver. */
    private $driver;

    /** The event manager instance */
    private $evm;

    /** The used cache driver. */
    private $cacheDriver;

    /** Whether factory has been lazily initialized yet */
    private $initialized = false;

    /**
     * Sets the DocumentManager instance for this class.
     *
     * @param EntityManager $dm The DocumentManager instance
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Sets the Configuration instance
     *
     * @param Configuration $config
     */
    public function setConfiguration(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    private function initialize()
    {
        $this->driver = $this->config->getMetadataDriverImpl();
        $this->evm = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @param Doctrine\Common\Cache\Cache $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return Doctrine\Common\Cache\Cache
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * Gets the array of loaded ClassMetadata instances.
     *
     * @return array $loadedMetadata The loaded metadata.
     */
    public function getLoadedMetadata()
    {
        return $this->loadedMetadata;
    }

    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     *
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata()
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        $metadata = array();
        foreach ($this->driver->getAllClassNames() as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->loadedMetadata[$className])) {
            $realClassName = $className;

            // Check for namespace alias
            if (strpos($className, ':') !== false) {
                list($namespaceAlias, $simpleClassName) = explode(':', $className);
                $realClassName = $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;

                if (isset($this->loadedMetadata[$realClassName])) {
                    // We do not have the alias name in the map, include it
                    $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];

                    return $this->loadedMetadata[$realClassName];
                }
            }

            if ($this->cacheDriver) {
                if (($cached = $this->cacheDriver->fetch("$realClassName\$MONGODBODMCLASSMETADATA")) !== false) {
                    $this->loadedMetadata[$realClassName] = $cached;
                } else {
                    foreach ($this->loadMetadata($realClassName) as $loadedClassName) {
                        $this->cacheDriver->save(
                            "$loadedClassName\$MONGODBODMCLASSMETADATA", $this->loadedMetadata[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->loadMetadata($realClassName);
            }

            if ($className != $realClassName) {
                // We do not have the alias name in the map, include it
                $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];
            }
        }

        return $this->loadedMetadata[$className];
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    private function loadMetadata($className)
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        $loaded = array();

        $parentClasses = $this->getParentClasses($className);
        $parentClasses[] = $className;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $visited = array();
        foreach ($parentClasses as $className) {
            if (isset($this->loadedMetadata[$className])) {
                $parent = $this->loadedMetadata[$className];
                if ( ! $parent->isMappedSuperclass && ! $parent->isEmbeddedDocument) {
                    array_unshift($visited, $className);
                }
                continue;
            }

            $class = $this->newClassMetadataInstance($className);

            if ($parent) {
                if (!$parent->isMappedSuperclass) {
                    $class->setInheritanceType($parent->inheritanceType);
                    $class->setDiscriminatorField($parent->discriminatorField);
                    $class->setDiscriminatorMap($parent->discriminatorMap);
                }
                $class->setIdGeneratorType($parent->generatorType);
                $this->addInheritedFields($class, $parent);
                $this->addInheritedIndexes($class, $parent);
                $class->setIdentifier($parent->identifier);
                $class->setVersioned($parent->isVersioned);
                $class->setVersionField($parent->versionField);
                $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
                $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);
                $class->setFile($parent->getFile());
            }

            // Invoke driver
            try {
                $this->driver->loadMetadataForClass($className, $class);
            } catch(ReflectionException $e) {
                throw MongoDBException::reflectionFailure($className, $e);
            }

            if ( ! $class->identifier && ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument) {
                throw MongoDBException::identifierRequired($className);
            }
            if ($parent && ! $parent->isMappedSuperclass && ! $class->isEmbeddedDocument) {
                if ($parent->generatorType) {
                    $class->setIdGeneratorType($parent->generatorType);
                }
                if ($parent->generatorOptions) {
                    $class->setIdGeneratorOptions($parent->generatorOptions);
                }
                if ($parent->idGenerator) {
                    $class->setIdGenerator($parent->idGenerator);
                }
            } else {
                $this->completeIdGeneratorMapping($class);
            }

            if ($parent && $parent->isInheritanceTypeSingleCollection()) {
                $class->setDatabase($parent->getDatabase());
                $class->setCollection($parent->getCollection());
            }

            $class->setParentClasses($visited);

            if ($this->evm->hasListeners(Events::loadClassMetadata)) {
                $eventArgs = new \Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs($class, $this->dm);
                $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
            }

            $this->loadedMetadata[$className] = $class;

            $parent = $class;

            if ( ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument) {
                array_unshift($visited, $className);
            }

            $loaded[] = $className;
        }

        return $loaded;
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     *
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        return isset($this->loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     *
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->loadedMetadata[$className] = $class;
    }

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    /**
     * Get array of parent classes for the given document class
     *
     * @param string $name
     * @return array $parentClasses
     */
    protected function getParentClasses($name)
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = array();
        foreach (array_reverse(class_parents($name)) as $parentClass) {
            if ( ! $this->driver->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }
        return $parentClasses;
    }

    private function completeIdGeneratorMapping(ClassMetadataInfo $class)
    {
        $idGenOptions = $class->generatorOptions;
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_AUTO:
                $class->setIdGenerator(new \Doctrine\ODM\MongoDB\Id\AutoGenerator($class));
                break;
            case ClassMetadata::GENERATOR_TYPE_INCREMENT:
                $incrementGenerator = new \Doctrine\ODM\MongoDB\Id\IncrementGenerator($class);
                if (isset($idGenOptions['key'])) {
                    $incrementGenerator->setKey($idGenOptions['key']);
                }
                if (isset($idGenOptions['collection'])) {
                    $incrementGenerator->setCollection($idGenOptions['collection']);
                }
                $class->setIdGenerator($incrementGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_UUID:
                $uuidGenerator = new \Doctrine\ODM\MongoDB\Id\UuidGenerator($class);
                $uuidGenerator->setSalt(isset($idGenOptions['salt']) ? $idGenOptions['salt'] : php_uname('n'));
                $class->setIdGenerator($uuidGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_ALNUM:
                $alnumGenerator = new \Doctrine\ODM\MongoDB\Id\AlnumGenerator($class);
                if(isset($idGenOptions['pad'])) {
                    $alnumGenerator->setPad($idGenOptions['pad']);
                }
                if(isset($idGenOptions['awkwardSafe'])) {
                    $alnumGenerator->setAwkwardSafeMode($idGenOptions['awkwardSafe']);
                }
                $class->setIdGenerator($alnumGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_NONE;
                break;
            default:
                throw new MongoDBException("Unknown generator type: " . $class->generatorType);
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $subClass
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $parentClass
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if ( ! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Adds inherited indexes to the subclass mapping.
     *
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $subClass
     * @param Doctrine\ODM\MongoDB\Mapping\ClassMetadata $parentClass
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->indexes as $index) {
            $subClass->addIndex($index['keys'], $index['options']);
        }
    }
    
    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Document or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        // Check for namespace alias
        if (strpos($className, ':') !== false) {
            list($namespaceAlias, $simpleClassName) = explode(':', $className);
            $className = $this->dm->getConfiguration()->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }

        return $this->driver->isTransient($className);
    }
}
