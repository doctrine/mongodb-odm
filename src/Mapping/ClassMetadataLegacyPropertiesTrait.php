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
    /**
     * READ-ONLY: The name of the mongo database the document is mapped to.
     *
     * @var string|null
     */
    public $db;

    /**
     * READ-ONLY: The name of the mongo collection the document is mapped to.
     *
     * @var string
     */
    public $collection;

    /**
     * READ-ONLY: The name of the GridFS bucket the document is mapped to.
     *
     * @var string
     */
    public $bucketName = 'fs';

    /**
     * READ-ONLY: If the collection should be a fixed size.
     *
     * @var bool
     */
    public $collectionCapped = false;

    /**
     * READ-ONLY: If the collection is fixed size, its size in bytes.
     *
     * @var int|null
     */
    public $collectionSize;

    /**
     * READ-ONLY: If the collection is fixed size, the maximum number of elements to store in the collection.
     *
     * @var int|null
     */
    public $collectionMax;

    /**
     * READ-ONLY Describes how MongoDB clients route read operations to the members of a replica set.
     *
     * @var string|null
     */
    public $readPreference;

    /**
     * READ-ONLY Associated with readPreference Allows to specify criteria so that your application can target read
     * operations to specific members, based on custom parameters.
     *
     * @var array<array<string, string>>
     */
    public $readPreferenceTags = [];

    /**
     * READ-ONLY: Describes the level of acknowledgement requested from MongoDB for write operations.
     *
     * @var string|int|null
     */
    public $writeConcern;

    /**
     * READ-ONLY: The field name of the document identifier.
     *
     * @var string|null
     */
    public $identifier;

    /**
     * READ-ONLY: Keys and options describing shard key. Only for sharded collections.
     *
     * @var array<string, array>
     * @phpstan-var ShardKey
     */
    public $shardKey = [];

    /**
     * Allows users to specify a validation schema for the collection.
     *
     * @phpstan-var array<string, mixed>|object|null
     */
    private array|object|null $validator = null;

    /**
     * Determines whether to error on invalid documents or just warn about the violations but allow invalid documents to be inserted.
     */
    private string $validationAction = self::SCHEMA_VALIDATION_ACTION_ERROR;

    /**
     * READ-ONLY: The name of the document class.
     *
     * @var class-string<T>
     */
    public $name;

    /**
     * READ-ONLY: The name of the document class that is at the root of the mapped document inheritance
     * hierarchy. If the document is not part of a mapped inheritance hierarchy this is the same
     * as {@link $documentName}.
     *
     * @var class-string
     */
    public $rootDocumentName;

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var class-string|null
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var list<class-string>
     */
    public $parentClasses = [];

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var int
     */
    public $inheritanceType = self::INHERITANCE_TYPE_NONE;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var int
     */
    public $generatorType = self::GENERATOR_TYPE_AUTO;

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var IdGenerator|null
     */
    public $idGenerator;

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
    public $discriminatorValue;

    /**
     * READ-ONLY: The definition of the discriminator field used in SINGLE_COLLECTION
     * inheritance mapping.
     *
     * @var string|null
     */
    public $discriminatorField;

    /**
     * READ-ONLY: The default value for discriminatorField in case it's not set in the document
     *
     * @see discriminatorField
     *
     * @var string|null
     */
    public $defaultDiscriminatorValue;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var bool
     */
    public $isMappedSuperclass = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a embedded document.
     *
     * @var bool
     */
    public $isEmbeddedDocument = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of an aggregation result document.
     *
     * @var bool
     */
    public $isQueryResultDocument = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a gridFS file
     *
     * @var bool
     */
    public $isFile = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a database view.
     */
    public bool $isView = false;

    /**
     * READ-ONLY: The default chunk size in bytes for the file
     *
     * @var int|null
     */
    public $chunkSizeBytes;

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var int
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to be versioned
     * with optimistic locking.
     *
     * @var bool $isVersioned
     */
    public $isVersioned = false;

    /**
     * READ-ONLY: The name of the field which is used for versioning in optimistic locking (if any).
     *
     * @var string|null $versionField
     */
    public $versionField;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to allow pessimistic
     * locking.
     *
     * @var bool $isLockable
     */
    public $isLockable = false;

    /**
     * READ-ONLY: The name of the field which is used for locking a document.
     *
     * @var mixed $lockField
     */
    public $lockField;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass<T>
     */
    public $reflClass;

    /**
     * READ_ONLY: A flag for whether or not this document is read-only.
     *
     * @var bool
     */
    public $isReadOnly;

    /**
     * READ-ONLY: A flag for whether or not this document has encrypted fields.
     */
    public bool $isEncrypted = false;

    /** READ ONLY: stores metadata about the time series collection */
    public ?TimeSeries $timeSeriesOptions = null;
}
