<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\Id\IdGenerator;
use Doctrine\ODM\MongoDB\Mapping\Attribute\TimeSeries;
use ReflectionClass;

/**
 * @internal
 *
 * @template-covariant T of object
 * @phpstan-import-type ShardKey from ClassMetadata
 */
trait ClassMetadataPropertiesTrait
{
    // phpcs:disable SlevomatCodingStandard.Classes.PropertySpacing.IncorrectCountOfBlankLinesAfterProperty
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Multiple
    // phpcs:disable PSR2.Classes.PropertyDeclaration.ScopeMissing
    /**
     * READ-ONLY: The name of the mongo database the document is mapped to.
     *
     * @var string|null
     */
    public $db {
        set => $this->setPropertyValue('db', $value);
    }

    /**
     * READ-ONLY: The name of the mongo collection the document is mapped to.
     *
     * @var string
     */
    public $collection {
        set => $this->setPropertyValue('collection', $value);
    }

    /**
     * READ-ONLY: The name of the GridFS bucket the document is mapped to.
     *
     * @var string
     */
    public $bucketName = 'fs' {
        set => $this->setPropertyValue('bucketName', $value);
    }

    /**
     * READ-ONLY: If the collection should be a fixed size.
     *
     * @var bool
     */
    public $collectionCapped = false {
        set => $this->setPropertyValue('collectionCapped', $value);
    }

    /**
     * READ-ONLY: If the collection is fixed size, its size in bytes.
     *
     * @var int|null
     */
    public $collectionSize {
        set => $this->setPropertyValue('collectionSize', $value);
    }

    /**
     * READ-ONLY: If the collection is fixed size, the maximum number of elements to store in the collection.
     *
     * @var int|null
     */
    public $collectionMax {
        set => $this->setPropertyValue('collectionMax', $value);
    }

    /**
     * READ-ONLY Describes how MongoDB clients route read operations to the members of a replica set.
     *
     * @var string|null
     */
    public $readPreference {
        set => $this->setPropertyValue('readPreference', $value);
    }

    /**
     * READ-ONLY Associated with readPreference Allows to specify criteria so that your application can target read
     * operations to specific members, based on custom parameters.
     *
     * @var array<array<string, string>>
     */
    public $readPreferenceTags = [] {
        set => $this->setPropertyValue('readPreferenceTags', $value);
    }

    /**
     * READ-ONLY: Describes the level of acknowledgement requested from MongoDB for write operations.
     *
     * @var string|int|null
     */
    public $writeConcern {
        set => $this->setPropertyValue('writeConcern', $value);
    }

    /**
     * READ-ONLY: The field name of the document identifier.
     *
     * @var string|null
     */
    public $identifier {
        set => $this->setPropertyValue('identifier', $value);
    }

    /**
     * READ-ONLY: Keys and options describing shard key. Only for sharded collections.
     *
     * @var array<string, array>
     * @phpstan-var ShardKey
     */
    public $shardKey = [] {
        set => $this->setPropertyValue('shardKey', $value);
    }

    /**
     * Allows users to specify a validation schema for the collection.
     *
     * @phpstan-var array<string, mixed>|object|null
     */
    private array|object|null $validator = null {
        set => $this->setPropertyValue('validator', $value);
    }

    /**
     * Determines whether to error on invalid documents or just warn about the violations but allow invalid documents to be inserted.
     */
    private string $validationAction = self::SCHEMA_VALIDATION_ACTION_ERROR {
        set => $this->setPropertyValue('validationAction', $value);
    }

    /**
     * READ-ONLY: The name of the document class.
     *
     * @var class-string<T>
     */
    public $name {
        set => $this->setPropertyValue('name', $value);
    }

    /**
     * READ-ONLY: The name of the document class that is at the root of the mapped document inheritance
     * hierarchy. If the document is not part of a mapped inheritance hierarchy this is the same
     * as {@link $documentName}.
     *
     * @var class-string
     */
    public $rootDocumentName {
        set => $this->setPropertyValue('rootDocumentName', $value);
    }

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var class-string|null
     */
    public $customRepositoryClassName {
        set => $this->setPropertyValue('customRepositoryClassName', $value);
    }

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var list<class-string>
     */
    public $parentClasses = [] {
        set => $this->setPropertyValue('parentClasses', $value);
    }

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var int
     */
    public $inheritanceType = self::INHERITANCE_TYPE_NONE {
        set => $this->setPropertyValue('inheritanceType', $value);
    }

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var int
     */
    public $generatorType = self::GENERATOR_TYPE_AUTO {
        set => $this->setPropertyValue('generatorType', $value);
    }

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var IdGenerator|null
     */
    public $idGenerator {
        set => $this->setPropertyValue('idGenerator', $value);
    }

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_COLLECTION inheritance mapping strategies
     * where a discriminator field is used.</b>
     *
     * @see discriminatorField
     *
     * @var class-string|null
     */
    public $discriminatorValue {
        set => $this->setPropertyValue('discriminatorValue', $value);
    }

    /**
     * READ-ONLY: The definition of the discriminator field used in SINGLE_COLLECTION
     * inheritance mapping.
     *
     * @var string|null
     */
    public $discriminatorField {
        set => $this->setPropertyValue('discriminatorField', $value);
    }

    /**
     * READ-ONLY: The default value for discriminatorField in case it's not set in the document
     *
     * @see discriminatorField
     *
     * @var string|null
     */
    public $defaultDiscriminatorValue {
        set => $this->setPropertyValue('defaultDiscriminatorValue', $value);
    }

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var bool
     */
    public $isMappedSuperclass = false {
        set => $this->setPropertyValue('isMappedSuperclass', $value);
    }

    /**
     * READ-ONLY: Whether this class describes the mapping of a embedded document.
     *
     * @var bool
     */
    public $isEmbeddedDocument = false {
        set => $this->setPropertyValue('isEmbeddedDocument', $value);
    }

    /**
     * READ-ONLY: Whether this class describes the mapping of an aggregation result document.
     *
     * @var bool
     */
    public $isQueryResultDocument = false {
        set => $this->setPropertyValue('isQueryResultDocument', $value);
    }

    /**
     * Whether this class describes the mapping of a database view.
     */
    public private(set) bool $isView = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a gridFS file
     *
     * @var bool
     */
    public $isFile = false {
        set => $this->setPropertyValue('isFile', $value);
    }

    /**
     * READ-ONLY: The default chunk size in bytes for the file
     *
     * @var int|null
     */
    public $chunkSizeBytes {
        set => $this->setPropertyValue('chunkSizeBytes', $value);
    }

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var int
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT {
        set => $this->setPropertyValue('changeTrackingPolicy', $value);
    }

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to be versioned
     * with optimistic locking.
     *
     * @var bool $isVersioned
     */
    public $isVersioned = false {
        set => $this->setPropertyValue('isVersioned', $value);
    }

    /**
     * READ-ONLY: The name of the field which is used for versioning in optimistic locking (if any).
     *
     * @var string|null $versionField
     */
    public $versionField {
        set => $this->setPropertyValue('versionField', $value);
    }

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to allow pessimistic
     * locking.
     *
     * @var bool $isLockable
     */
    public $isLockable = false {
        set => $this->setPropertyValue('isLockable', $value);
    }

    /**
     * READ-ONLY: The name of the field which is used for locking a document.
     *
     * @var mixed $lockField
     */
    public $lockField {
        set => $this->setPropertyValue('lockField', $value);
    }

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass<T>
     */
    public $reflClass {
        set => $this->setPropertyValue('reflClass', $value);
    }

    /**
     * READ_ONLY: A flag for whether or not this document is read-only.
     *
     * @var bool
     */
    public $isReadOnly {
        set => $this->setPropertyValue('isReadOnly', $value);
    }

    /**
     * READ-ONLY: A flag for whether or not this document has encrypted fields.
     */
    public bool $isEncrypted = false {
        set => $this->setPropertyValue('isEncrypted', $value);
    }

    /** READ ONLY: stores metadata about the time series collection */
    public ?TimeSeries $timeSeriesOptions = null {
        set => $this->setPropertyValue('timeSeriesOptions', $value);
    }
    // phpcs:enable

    /**
     * @param ValueType $value
     *
     * @return ValueType
     *
     * @template ValueType
     */
    abstract private function setPropertyValue(string $property, mixed $value): mixed;
}
