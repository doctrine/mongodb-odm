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

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Id\AbstractIdGenerator;
use Doctrine\ODM\MongoDB\Id\AlnumGenerator;
use Doctrine\ODM\MongoDB\Id\AutoGenerator;
use Doctrine\ODM\MongoDB\Id\IncrementGenerator;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a document database.
 *
 * @since       1.0
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    protected $cacheSalt = "\$MONGODBODMCLASSMETADATA";

    /** @var DocumentManager The DocumentManager instance */
    private $dm;

    /** @var Configuration The Configuration instance */
    private $config;

    /** @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver The used metadata driver. */
    private $driver;

    /** @var \Doctrine\Common\EventManager The event manager instance */
    private $evm;

    /**
     * Sets the DocumentManager instance for this class.
     *
     * @param DocumentManager $dm The DocumentManager instance
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
    protected function initialize()
    {
        $this->driver = $this->config->getMetadataDriverImpl();
        $this->evm = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument;
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents = array())
    {
        /** @var $class ClassMetadata */
        /** @var $parent ClassMetadata */
        if ($parent) {
            $class->setInheritanceType($parent->inheritanceType);
            $class->setDiscriminatorField($parent->discriminatorField);
            $class->setDiscriminatorMap($parent->discriminatorMap);
            $class->setDefaultDiscriminatorValue($parent->defaultDiscriminatorValue);
            $class->setIdGeneratorType($parent->generatorType);
            $this->addInheritedFields($class, $parent);
            $this->addInheritedRelations($class, $parent);
            $this->addInheritedIndexes($class, $parent);
            $this->setInheritedShardKey($class, $parent);
            $class->setIdentifier($parent->identifier);
            $class->setVersioned($parent->isVersioned);
            $class->setVersionField($parent->versionField);
            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setAlsoLoadMethods($parent->alsoLoadMethods);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);
            $class->setWriteConcern($parent->writeConcern);
            $class->setFile($parent->getFile());
            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (\ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        $this->validateIdentifier($class);

        if ($parent && $rootEntityFound && $parent->generatorType === $class->generatorType) {
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

        $class->setParentClasses($nonSuperclassParents);

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->dm);
            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }
    }

    /**
     * Validates the identifier mapping.
     *
     * @param ClassMetadata $class
     * @throws MappingException
     */
    protected function validateIdentifier($class)
    {
        if ( ! $class->identifier && ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument) {
            throw MappingException::identifierRequired($class->name);
        }
    }

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    private function completeIdGeneratorMapping(ClassMetadataInfo $class)
    {
        $idGenOptions = $class->generatorOptions;
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_AUTO:
                $class->setIdGenerator(new AutoGenerator());
                break;
            case ClassMetadata::GENERATOR_TYPE_INCREMENT:
                $incrementGenerator = new IncrementGenerator();
                if (isset($idGenOptions['key'])) {
                    $incrementGenerator->setKey($idGenOptions['key']);
                }
                if (isset($idGenOptions['collection'])) {
                    $incrementGenerator->setCollection($idGenOptions['collection']);
                }
                $class->setIdGenerator($incrementGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_UUID:
                $uuidGenerator = new UuidGenerator();
                isset($idGenOptions['salt']) && $uuidGenerator->setSalt($idGenOptions['salt']);
                $class->setIdGenerator($uuidGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_ALNUM:
                $alnumGenerator = new AlnumGenerator();
                if (isset($idGenOptions['pad'])) {
                    $alnumGenerator->setPad($idGenOptions['pad']);
                }
                if (isset($idGenOptions['chars'])) {
                    $alnumGenerator->setChars($idGenOptions['chars']);
                } elseif (isset($idGenOptions['awkwardSafe'])) {
                    $alnumGenerator->setAwkwardSafeMode($idGenOptions['awkwardSafe']);
                }

                $class->setIdGenerator($alnumGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                if (empty($idGenOptions['class'])) {
                    throw MappingException::missingIdGeneratorClass($class->name);
                }

                $customGenerator = new $idGenOptions['class'];
                unset($idGenOptions['class']);
                if ( ! $customGenerator instanceof AbstractIdGenerator) {
                    throw MappingException::classIsNotAValidGenerator(get_class($customGenerator));
                }

                $methods = get_class_methods($customGenerator);
                foreach ($idGenOptions as $name => $value) {
                    $method = 'set' . ucfirst($name);
                    if ( ! in_array($method, $methods)) {
                        throw MappingException::missingGeneratorSetter(get_class($customGenerator), $name);
                    }

                    $customGenerator->$method($value);
                }
                $class->setIdGenerator($customGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_NONE;
                break;
            default:
                throw new MappingException('Unknown generator type: ' . $class->generatorType);
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
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
     * Adds inherited association mappings to the subclass mapping.
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $parentClass
     *
     * @return void
     *
     * @throws MappingException
     */
    private function addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->associationMappings as $field => $mapping) {
            if ($parentClass->isMappedSuperclass) {
                $mapping['sourceDocument'] = $subClass->name;
            }

            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if ( ! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedAssociationMapping($mapping);
        }
    }

    /**
     * Adds inherited indexes to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->indexes as $index) {
            $subClass->addIndex($index['keys'], $index['options']);
        }
    }

    /**
     * Adds inherited shard key to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function setInheritedShardKey(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        if ($parentClass->isSharded()) {
            $subClass->setShardKey(
                $parentClass->shardKey['keys'],
                $parentClass->shardKey['options']
            );
        }
    }
}
