<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\ConfigurationException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Id\AlnumGenerator;
use Doctrine\ODM\MongoDB\Id\AutoGenerator;
use Doctrine\ODM\MongoDB\Id\IdGenerator;
use Doctrine\ODM\MongoDB\Id\IncrementGenerator;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionException;

use function assert;
use function get_class_methods;
use function in_array;
use function interface_exists;
use function trigger_deprecation;
use function ucfirst;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a document database.
 *
 * @internal
 *
 * @template-extends AbstractClassMetadataFactory<ClassMetadata>
 */
final class ClassMetadataFactory extends AbstractClassMetadataFactory implements ClassMetadataFactoryInterface
{
    /** @var DocumentManager The DocumentManager instance */
    private DocumentManager $dm;

    /** @var Configuration The Configuration instance */
    private Configuration $config;

    /** @var MappingDriver The used metadata driver. */
    private MappingDriver $driver;

    /** @var EventManager The event manager instance */
    private EventManager $evm;

    public function __construct()
    {
        $this->cacheSalt = '$MONGODBODMCLASSMETADATA';
    }

    public function setDocumentManager(DocumentManager $dm): void
    {
        $this->dm = $dm;
    }

    public function setConfiguration(Configuration $config): void
    {
        $this->config = $config;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    protected function initialize(): void
    {
        $driver = $this->config->getMetadataDriverImpl();
        if ($driver === null) {
            throw ConfigurationException::noMetadataDriverConfigured();
        }

        $this->driver      = $driver;
        $this->evm         = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /** @param string $className */
    protected function onNotFoundMetadata($className): ?ClassMetadata
    {
        if (! $this->evm->hasListeners(Events::onClassMetadataNotFound)) {
            return null;
        }

        $eventArgs = new OnClassMetadataNotFoundEventArgs($className, $this->dm);

        $this->evm->dispatchEvent(Events::onClassMetadataNotFound, $eventArgs);

        return $eventArgs->getFoundMetadata();
    }

    /**
     * @deprecated
     *
     * @param string $namespaceAlias
     * @param string $simpleClassName
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName): string
    {
        return $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    protected function getDriver(): MappingDriver
    {
        return $this->driver;
    }

    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
    }

    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
    }

    protected function isEntity(ClassMetadataInterface $class): bool
    {
        assert($class instanceof ClassMetadata);

        return ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument && ! $class->isQueryResultDocument && ! $class->isView();
    }

    /** @param bool $rootEntityFound */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents = []): void
    {
        assert($class instanceof ClassMetadata);
        if ($parent instanceof ClassMetadata) {
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
            $class->setReadPreference($parent->readPreference, $parent->readPreferenceTags);
            $class->setWriteConcern($parent->writeConcern);

            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }

            if ($parent->isFile) {
                $class->isFile = true;
                $class->setBucketName($parent->bucketName);

                if ($parent->chunkSizeBytes !== null) {
                    $class->setChunkSizeBytes($parent->chunkSizeBytes);
                }
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        $this->validateIdentifier($class);

        if ($parent instanceof ClassMetadata && $rootEntityFound && $parent->generatorType === $class->generatorType) {
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

        if ($parent instanceof ClassMetadata && $parent->isInheritanceTypeSingleCollection()) {
            $class->setDatabase($parent->getDatabase());
            $class->setCollection($parent->getCollection());
        }

        $class->setParentClasses($nonSuperclassParents);

        $this->evm->dispatchEvent(
            Events::loadClassMetadata,
            new LoadClassMetadataEventArgs($class, $this->dm),
        );

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($class->isChangeTrackingNotify()) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.4',
                'NOTIFY tracking policy used in class "%s" is deprecated. Please use DEFERRED_EXPLICIT instead.',
                $class->name,
            );
        }
    }

    /**
     * Validates the identifier mapping.
     *
     * @throws MappingException
     */
    protected function validateIdentifier(ClassMetadata $class): void
    {
        if (! $class->identifier && $this->isEntity($class)) {
            throw MappingException::identifierRequired($class->name);
        }
    }

    protected function newClassMetadataInstance($className): ClassMetadata
    {
        return new ClassMetadata($className);
    }

    private function completeIdGeneratorMapping(ClassMetadata $class): void
    {
        $idGenOptions = $class->generatorOptions;
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_AUTO:
                $class->setIdGenerator(new AutoGenerator());
                break;
            case ClassMetadata::GENERATOR_TYPE_INCREMENT:
                $incrementGenerator = new IncrementGenerator();
                if (isset($idGenOptions['key'])) {
                    $incrementGenerator->setKey((string) $idGenOptions['key']);
                }

                if (isset($idGenOptions['collection'])) {
                    $incrementGenerator->setCollection((string) $idGenOptions['collection']);
                }

                if (isset($idGenOptions['startingId'])) {
                    $incrementGenerator->setStartingId((int) $idGenOptions['startingId']);
                }

                $class->setIdGenerator($incrementGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_UUID:
                $uuidGenerator = new UuidGenerator();
                if (isset($idGenOptions['salt'])) {
                    $uuidGenerator->setSalt((string) $idGenOptions['salt']);
                }

                $class->setIdGenerator($uuidGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_ALNUM:
                $alnumGenerator = new AlnumGenerator();
                if (isset($idGenOptions['pad'])) {
                    $alnumGenerator->setPad((int) $idGenOptions['pad']);
                }

                if (isset($idGenOptions['chars'])) {
                    $alnumGenerator->setChars((string) $idGenOptions['chars']);
                } elseif (isset($idGenOptions['awkwardSafe'])) {
                    $alnumGenerator->setAwkwardSafeMode((bool) $idGenOptions['awkwardSafe']);
                }

                $class->setIdGenerator($alnumGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                if (empty($idGenOptions['class'])) {
                    throw MappingException::missingIdGeneratorClass($class->name);
                }

                $customGenerator = new $idGenOptions['class']();
                unset($idGenOptions['class']);
                if (! $customGenerator instanceof IdGenerator) {
                    throw MappingException::classIsNotAValidGenerator($customGenerator::class);
                }

                $methods = get_class_methods($customGenerator);
                foreach ($idGenOptions as $name => $value) {
                    $method = 'set' . ucfirst($name);
                    if (! in_array($method, $methods)) {
                        throw MappingException::missingGeneratorSetter($customGenerator::class, $name);
                    }

                    $customGenerator->$method($value);
                }

                $class->setIdGenerator($customGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_NONE:
                break;
            default:
                throw new MappingException('Unknown generator type: ' . $class->generatorType);
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if (! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }

            if (! isset($mapping['declared'])) {
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
     * @throws MappingException
     */
    private function addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->associationMappings as $field => $mapping) {
            if ($parentClass->isMappedSuperclass) {
                $mapping['sourceDocument'] = $subClass->name;
            }

            if (! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }

            if (! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }

            $subClass->addInheritedAssociationMapping($mapping);
        }
    }

    /**
     * Adds inherited indexes to the subclass mapping.
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->indexes as $index) {
            $subClass->addIndex($index['keys'], $index['options']);
        }
    }

    /**
     * Adds inherited shard key to the subclass mapping.
     */
    private function setInheritedShardKey(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        if (! $parentClass->isSharded()) {
            return;
        }

        $subClass->setShardKey(
            $parentClass->shardKey['keys'],
            $parentClass->shardKey['options'],
        );
    }
}

interface_exists(ClassMetadataInterface::class);
interface_exists(ReflectionService::class);
