<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use BadMethodCallException;
use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\ODM\MongoDB\Id\IdGenerator;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Types\Incrementable;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\Versionable;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use InvalidArgumentException;
use LogicException;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use ReflectionProperty;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function assert;
use function class_exists;
use function constant;
use function count;
use function extension_loaded;
use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function sprintf;
use function strtolower;
use function strtoupper;
use function trigger_deprecation;

/**
 * A <tt>ClassMetadata</tt> instance holds all the object-document mapping metadata
 * of a document and it's references.
 *
 * Once populated, ClassMetadata instances are usually cached in a serialized form.
 *
 * <b>IMPORTANT NOTE:</b>
 *
 * The fields of this class are only public for 2 reasons:
 * 1) To allow fast READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @final
 */
/* final */ class ClassMetadata implements BaseClassMetadata
{
    /* The Id generator types. */
    /**
     * AUTO means Doctrine will automatically create a new \MongoDB\BSON\ObjectId instance for us.
     */
    public const GENERATOR_TYPE_AUTO = 1;

    /**
     * INCREMENT means a separate collection is used for maintaining and incrementing id generation.
     * Offers full portability.
     */
    public const GENERATOR_TYPE_INCREMENT = 2;

    /**
     * UUID means Doctrine will generate a uuid for us.
     */
    public const GENERATOR_TYPE_UUID = 3;

    /**
     * ALNUM means Doctrine will generate Alpha-numeric string identifiers, using the INCREMENT
     * generator to ensure identifier uniqueness
     */
    public const GENERATOR_TYPE_ALNUM = 4;

    /**
     * CUSTOM means Doctrine expect a class parameter. It will then try to initiate that class
     * and pass other options to the generator. It will throw an Exception if the class
     * does not exist or if an option was passed for that there is not setter in the new
     * generator class.
     *
     * The class will have to implement IdGenerator.
     */
    public const GENERATOR_TYPE_CUSTOM = 5;

    /**
     * NONE means Doctrine will not generate any id for us and you are responsible for manually
     * assigning an id.
     */
    public const GENERATOR_TYPE_NONE = 6;

    /**
     * Default discriminator field name.
     *
     * This is used for associations value for associations where a that do not define a "targetDocument" or
     * "discriminatorField" option in their mapping.
     */
    public const DEFAULT_DISCRIMINATOR_FIELD = '_doctrine_class_name';

    public const REFERENCE_ONE  = 1;
    public const REFERENCE_MANY = 2;
    public const EMBED_ONE      = 3;
    public const EMBED_MANY     = 4;
    public const MANY           = 'many';
    public const ONE            = 'one';

    /**
     * The types of storeAs references
     */
    public const REFERENCE_STORE_AS_ID             = 'id';
    public const REFERENCE_STORE_AS_DB_REF         = 'dbRef';
    public const REFERENCE_STORE_AS_DB_REF_WITH_DB = 'dbRefWithDb';
    public const REFERENCE_STORE_AS_REF            = 'ref';

    /* The inheritance mapping types */
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    public const INHERITANCE_TYPE_NONE = 1;

    /**
     * SINGLE_COLLECTION means the class will be persisted according to the rules of
     * <tt>Single Collection Inheritance</tt>.
     */
    public const INHERITANCE_TYPE_SINGLE_COLLECTION = 2;

    /**
     * COLLECTION_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Collection Inheritance</tt>.
     */
    public const INHERITANCE_TYPE_COLLECTION_PER_CLASS = 3;

    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    public const CHANGETRACKING_DEFERRED_IMPLICIT = 1;

    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    public const CHANGETRACKING_DEFERRED_EXPLICIT = 2;

    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    public const CHANGETRACKING_NOTIFY = 3;

    /**
     * SET means that fields will be written to the database using a $set operator
     */
    public const STORAGE_STRATEGY_SET = 'set';

    /**
     * INCREMENT means that fields will be written to the database by calculating
     * the difference and using the $inc operator
     */
    public const STORAGE_STRATEGY_INCREMENT = 'increment';

    public const STORAGE_STRATEGY_PUSH_ALL         = 'pushAll';
    public const STORAGE_STRATEGY_ADD_TO_SET       = 'addToSet';
    public const STORAGE_STRATEGY_ATOMIC_SET       = 'atomicSet';
    public const STORAGE_STRATEGY_ATOMIC_SET_ARRAY = 'atomicSetArray';
    public const STORAGE_STRATEGY_SET_ARRAY        = 'setArray';

    private const ALLOWED_GRIDFS_FIELDS = ['_id', 'chunkSize', 'filename', 'length', 'metadata', 'uploadDate'];

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
     * @var string[][]
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
     * READ-ONLY: The array of indexes for the document collection.
     *
     * @var array
     */
    public $indexes = [];

    /**
     * READ-ONLY: Keys and options describing shard key. Only for sharded collections.
     *
     * @var array<string, array>
     */
    public $shardKey = [];

    /**
     * READ-ONLY: The name of the document class.
     *
     * @var string
     */
    public $name;

    /**
     * READ-ONLY: The name of the document class that is at the root of the mapped document inheritance
     * hierarchy. If the document is not part of a mapped inheritance hierarchy this is the same
     * as {@link $documentName}.
     *
     * @var string
     */
    public $rootDocumentName;

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string|null
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = [];

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @var array
     */
    public $subClasses = [];

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var ReflectionProperty[]
     */
    public $reflFields = [];

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
     * READ-ONLY: The Id generator options.
     *
     * @var array
     */
    public $generatorOptions = [];

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var IdGenerator|null
     */
    public $idGenerator;

    /**
     * READ-ONLY: The field mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the Document.
     *
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the document. Multiple fields of an
     * document can have the id attribute, forming a composite key.
     *
     * @var array
     */
    public $fieldMappings = [];

    /**
     * READ-ONLY: The association mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * @var array
     */
    public $associationMappings = [];

    /**
     * READ-ONLY: Array of fields to also load with a given method.
     *
     * @var array
     */
    public $alsoLoadMethods = [];

    /**
     * READ-ONLY: The registered lifecycle callbacks for documents of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = [];

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_COLLECTION inheritance mapping strategies
     * where a discriminator field is used.</b>
     *
     * @see discriminatorField
     *
     * @var mixed
     */
    public $discriminatorValue;

    /**
     * READ-ONLY: The discriminator map of all mapped classes in the hierarchy.
     *
     * <b>This does only apply to the SINGLE_COLLECTION inheritance mapping strategy
     * where a discriminator field is used.</b>
     *
     * @see discriminatorField
     *
     * @var mixed
     */
    public $discriminatorMap = [];

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
     * READ-ONLY: Whether this class describes the mapping of a database view.
     *
     * @var bool
     */
    private $isView = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a gridFS file
     *
     * @var bool
     */
    public $isFile = false;

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
     * @var ReflectionClass
     */
    public $reflClass;

    /**
     * READ_ONLY: A flag for whether or not this document is read-only.
     *
     * @var bool
     */
    public $isReadOnly;

    /** @var InstantiatorInterface */
    private $instantiator;

    /** @var ReflectionService */
    private $reflectionService;

    /** @var string|null */
    private $rootClass;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-document mapping
     * metadata of the class with the given name.
     */
    public function __construct(string $documentName)
    {
        $this->name              = $documentName;
        $this->rootDocumentName  = $documentName;
        $this->reflectionService = new RuntimeReflectionService();
        $this->reflClass         = new ReflectionClass($documentName);
        $this->setCollection($this->reflClass->getShortName());
        $this->instantiator = new Instantiator();
    }

    /**
     * Helper method to get reference id of ref* type references
     *
     * @internal
     *
     * @param mixed $reference
     *
     * @return mixed
     */
    public static function getReferenceId($reference, string $storeAs)
    {
        return $storeAs === self::REFERENCE_STORE_AS_ID ? $reference : $reference[self::getReferencePrefix($storeAs) . 'id'];
    }

    /**
     * Returns the reference prefix used for a reference
     */
    private static function getReferencePrefix(string $storeAs): string
    {
        if (! in_array($storeAs, [self::REFERENCE_STORE_AS_REF, self::REFERENCE_STORE_AS_DB_REF, self::REFERENCE_STORE_AS_DB_REF_WITH_DB])) {
            throw new LogicException('Can only get a reference prefix for DBRef and reference arrays');
        }

        return $storeAs === self::REFERENCE_STORE_AS_REF ? '' : '$';
    }

    /**
     * Returns a fully qualified field name for a given reference
     *
     * @internal
     *
     * @param string $pathPrefix The field path prefix
     */
    public static function getReferenceFieldName(string $storeAs, string $pathPrefix = ''): string
    {
        if ($storeAs === self::REFERENCE_STORE_AS_ID) {
            return $pathPrefix;
        }

        return ($pathPrefix ? $pathPrefix . '.' : '') . static::getReferencePrefix($storeAs) . 'id';
    }

    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflClass;
    }

    /**
     * {@inheritDoc}
     */
    public function isIdentifier($fieldName): bool
    {
        return $this->identifier === $fieldName;
    }

    /**
     * Sets the mapped identifier field of this class.
     *
     * @internal
     */
    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     *
     * Since MongoDB only allows exactly one identifier field
     * this will always return an array with only one value
     */
    public function getIdentifier(): array
    {
        return [$this->identifier];
    }

    /**
     * Since MongoDB only allows exactly one identifier field
     * this will always return an array with only one value
     *
     * return (string|null)[]
     */
    public function getIdentifierFieldNames(): array
    {
        return [$this->identifier];
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Sets the inheritance type used by the class and it's subclasses.
     */
    public function setInheritanceType(int $type): void
    {
        $this->inheritanceType = $type;
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     */
    public function isInheritedField(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Registers a custom repository class for the document class.
     */
    public function setCustomRepositoryClass(?string $repositoryClassName): void
    {
        if ($this->isEmbeddedDocument || $this->isQueryResultDocument) {
            return;
        }

        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Dispatches the lifecycle event of the given document by invoking all
     * registered callbacks.
     *
     * @throws InvalidArgumentException If document class is not this class or
     *                                   a Proxy of this class.
     */
    public function invokeLifecycleCallbacks(string $event, object $document, ?array $arguments = null): void
    {
        if ($this->isView()) {
            return;
        }

        if (! $document instanceof $this->name) {
            throw new InvalidArgumentException(sprintf('Expected document class "%s"; found: "%s"', $this->name, get_class($document)));
        }

        if (empty($this->lifecycleCallbacks[$event])) {
            return;
        }

        foreach ($this->lifecycleCallbacks[$event] as $callback) {
            if ($arguments !== null) {
                $document->$callback(...$arguments);
            } else {
                $document->$callback();
            }
        }
    }

    /**
     * Checks whether the class has callbacks registered for a lifecycle event.
     */
    public function hasLifecycleCallbacks(string $event): bool
    {
        return ! empty($this->lifecycleCallbacks[$event]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     */
    public function getLifecycleCallbacks(string $event): array
    {
        return $this->lifecycleCallbacks[$event] ?? [];
    }

    /**
     * Adds a lifecycle callback for documents of this class.
     *
     * If the callback is already registered, this is a NOOP.
     */
    public function addLifecycleCallback(string $callback, string $event): void
    {
        if (isset($this->lifecycleCallbacks[$event]) && in_array($callback, $this->lifecycleCallbacks[$event])) {
            return;
        }

        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for documents of this class.
     *
     * Any previously registered callbacks are overwritten.
     */
    public function setLifecycleCallbacks(array $callbacks): void
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Registers a method for loading document data before field hydration.
     *
     * Note: A method may be registered multiple times for different fields.
     * it will be invoked only once for the first field found.
     *
     * @param array|string $fields Database field name(s)
     */
    public function registerAlsoLoadMethod(string $method, $fields): void
    {
        $this->alsoLoadMethods[$method] = is_array($fields) ? $fields : [$fields];
    }

    /**
     * Sets the AlsoLoad methods for documents of this class.
     *
     * Any previously registered methods are overwritten.
     */
    public function setAlsoLoadMethods(array $methods): void
    {
        $this->alsoLoadMethods = $methods;
    }

    /**
     * Sets the discriminator field.
     *
     * The field name is the the unmapped database field. Discriminator values
     * are only used to discern the hydration class and are not mapped to class
     * properties.
     *
     * @param array|string|null $discriminatorField
     *
     * @throws MappingException If the discriminator field conflicts with the
     *                          "name" attribute of a mapped field.
     */
    public function setDiscriminatorField($discriminatorField): void
    {
        if ($this->isFile) {
            throw MappingException::discriminatorNotAllowedForGridFS($this->name);
        }

        if ($discriminatorField === null) {
            $this->discriminatorField = null;

            return;
        }

        // @todo: deprecate, document and remove this:
        // Handle array argument with name/fieldName keys for BC
        if (is_array($discriminatorField)) {
            if (isset($discriminatorField['name'])) {
                $discriminatorField = $discriminatorField['name'];
            } elseif (isset($discriminatorField['fieldName'])) {
                $discriminatorField = $discriminatorField['fieldName'];
            }
        }

        foreach ($this->fieldMappings as $fieldMapping) {
            if ($discriminatorField === $fieldMapping['name']) {
                throw MappingException::discriminatorFieldConflict($this->name, $discriminatorField);
            }
        }

        $this->discriminatorField = $discriminatorField;
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @throws MappingException
     */
    public function setDiscriminatorMap(array $map): void
    {
        if ($this->isFile) {
            throw MappingException::discriminatorNotAllowedForGridFS($this->name);
        }

        $this->subClasses         = [];
        $this->discriminatorMap   = [];
        $this->discriminatorValue = null;

        foreach ($map as $value => $className) {
            $this->discriminatorMap[$value] = $className;
            if ($this->name === $className) {
                $this->discriminatorValue = $value;
            } else {
                if (! class_exists($className)) {
                    throw MappingException::invalidClassInDiscriminatorMap($className, $this->name);
                }

                if (is_subclass_of($className, $this->name)) {
                    $this->subClasses[] = $className;
                }
            }
        }
    }

    /**
     * Sets the default discriminator value to be used for this class
     * Used for SINGLE_TABLE inheritance mapping strategies if the document has no discriminator value
     *
     * @throws MappingException
     */
    public function setDefaultDiscriminatorValue(?string $defaultDiscriminatorValue): void
    {
        if ($this->isFile) {
            throw MappingException::discriminatorNotAllowedForGridFS($this->name);
        }

        if ($defaultDiscriminatorValue === null) {
            $this->defaultDiscriminatorValue = null;

            return;
        }

        if (! array_key_exists($defaultDiscriminatorValue, $this->discriminatorMap)) {
            throw MappingException::invalidDiscriminatorValue($defaultDiscriminatorValue, $this->name);
        }

        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }

    /**
     * Sets the discriminator value for this class.
     * Used for JOINED/SINGLE_TABLE inheritance and multiple document types in a single
     * collection.
     *
     * @throws MappingException
     */
    public function setDiscriminatorValue(string $value): void
    {
        if ($this->isFile) {
            throw MappingException::discriminatorNotAllowedForGridFS($this->name);
        }

        $this->discriminatorMap[$value] = $this->name;
        $this->discriminatorValue       = $value;
    }

    /**
     * Add a index for this Document.
     */
    public function addIndex(array $keys, array $options = []): void
    {
        $this->indexes[] = [
            'keys' => array_map(static function ($value) {
                if ($value === 1 || $value === -1) {
                    return $value;
                }

                if (is_string($value)) {
                    $lower = strtolower($value);
                    if ($lower === 'asc') {
                        return 1;
                    }

                    if ($lower === 'desc') {
                        return -1;
                    }
                }

                return $value;
            }, $keys),
            'options' => $options,
        ];
    }

    /**
     * Returns the array of indexes for this Document.
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Checks whether this document has indexes or not.
     */
    public function hasIndexes(): bool
    {
        return $this->indexes !== [];
    }

    /**
     * Set shard key for this Document.
     *
     * @throws MappingException
     */
    public function setShardKey(array $keys, array $options = []): void
    {
        if ($this->inheritanceType === self::INHERITANCE_TYPE_SINGLE_COLLECTION && $this->shardKey !== []) {
            throw MappingException::shardKeyInSingleCollInheritanceSubclass($this->getName());
        }

        if ($this->isEmbeddedDocument) {
            throw MappingException::embeddedDocumentCantHaveShardKey($this->getName());
        }

        foreach (array_keys($keys) as $field) {
            if (! isset($this->fieldMappings[$field])) {
                continue;
            }

            if (in_array($this->fieldMappings[$field]['type'], ['many', 'collection'])) {
                throw MappingException::noMultiKeyShardKeys($this->getName(), $field);
            }

            if ($this->fieldMappings[$field]['strategy'] !== self::STORAGE_STRATEGY_SET) {
                throw MappingException::onlySetStrategyAllowedInShardKey($this->getName(), $field);
            }
        }

        $this->shardKey = [
            'keys' => array_map(static function ($value) {
                if ($value === 1 || $value === -1) {
                    return $value;
                }

                if (is_string($value)) {
                    $lower = strtolower($value);
                    if ($lower === 'asc') {
                        return 1;
                    }

                    if ($lower === 'desc') {
                        return -1;
                    }
                }

                return $value;
            }, $keys),
            'options' => $options,
        ];
    }

    public function getShardKey(): array
    {
        return $this->shardKey;
    }

    /**
     * Checks whether this document has shard key or not.
     */
    public function isSharded(): bool
    {
        return $this->shardKey !== [];
    }

    /**
     * Sets the read preference used by this class.
     */
    public function setReadPreference(?string $readPreference, array $tags): void
    {
        $this->readPreference     = $readPreference;
        $this->readPreferenceTags = $tags;
    }

    /**
     * Sets the write concern used by this class.
     *
     * @param string|int|null $writeConcern
     */
    public function setWriteConcern($writeConcern): void
    {
        $this->writeConcern = $writeConcern;
    }

    /**
     * @return int|string|null
     */
    public function getWriteConcern()
    {
        return $this->writeConcern;
    }

    /**
     * Whether there is a write concern configured for this class.
     */
    public function hasWriteConcern(): bool
    {
        return $this->writeConcern !== null;
    }

    /**
     * Sets the change tracking policy used by this class.
     */
    public function setChangeTrackingPolicy(int $policy): void
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     */
    public function isChangeTrackingDeferredExplicit(): bool
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     */
    public function isChangeTrackingDeferredImplicit(): bool
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     */
    public function isChangeTrackingNotify(): bool
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_NOTIFY;
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     */
    public function getReflectionProperties(): array
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     */
    public function getReflectionProperty(string $name): ReflectionProperty
    {
        return $this->reflFields[$name];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the database this Document is mapped to.
     */
    public function getDatabase(): ?string
    {
        return $this->db;
    }

    /**
     * Set the database this Document is mapped to.
     */
    public function setDatabase(?string $db): void
    {
        $this->db = $db;
    }

    /**
     * Get the collection this Document is mapped to.
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * Sets the collection this Document is mapped to.
     *
     * @param array|string $name
     *
     * @throws InvalidArgumentException
     */
    public function setCollection($name): void
    {
        if (is_array($name)) {
            if (! isset($name['name'])) {
                throw new InvalidArgumentException('A name key is required when passing an array to setCollection()');
            }

            $this->collectionCapped = $name['capped'] ?? false;
            $this->collectionSize   = $name['size'] ?? 0;
            $this->collectionMax    = $name['max'] ?? 0;
            $this->collection       = $name['name'];
        } else {
            $this->collection = $name;
        }
    }

    public function getBucketName(): ?string
    {
        return $this->bucketName;
    }

    public function setBucketName(string $bucketName): void
    {
        $this->bucketName = $bucketName;
        $this->setCollection($bucketName . '.files');
    }

    public function getChunkSizeBytes(): ?int
    {
        return $this->chunkSizeBytes;
    }

    public function setChunkSizeBytes(int $chunkSizeBytes): void
    {
        $this->chunkSizeBytes = $chunkSizeBytes;
    }

    /**
     * Get whether or not the documents collection is capped.
     */
    public function getCollectionCapped(): bool
    {
        return $this->collectionCapped;
    }

    /**
     * Set whether or not the documents collection is capped.
     */
    public function setCollectionCapped(bool $bool): void
    {
        $this->collectionCapped = $bool;
    }

    /**
     * Get the collection size
     */
    public function getCollectionSize(): ?int
    {
        return $this->collectionSize;
    }

    /**
     * Set the collection size.
     */
    public function setCollectionSize(int $size): void
    {
        $this->collectionSize = $size;
    }

    /**
     * Get the collection max.
     */
    public function getCollectionMax(): ?int
    {
        return $this->collectionMax;
    }

    /**
     * Set the collection max.
     */
    public function setCollectionMax(int $max): void
    {
        $this->collectionMax = $max;
    }

    /**
     * Returns TRUE if this Document is mapped to a collection FALSE otherwise.
     */
    public function isMappedToCollection(): bool
    {
        return $this->collection !== '' && $this->collection !== null;
    }

    /**
     * Validates the storage strategy of a mapping for consistency
     *
     * @throws MappingException
     */
    private function applyStorageStrategy(array &$mapping): void
    {
        if (! isset($mapping['type']) || isset($mapping['id'])) {
            return;
        }

        switch (true) {
            case $mapping['type'] === 'many':
                $defaultStrategy   = CollectionHelper::DEFAULT_STRATEGY;
                $allowedStrategies = [
                    self::STORAGE_STRATEGY_PUSH_ALL,
                    self::STORAGE_STRATEGY_ADD_TO_SET,
                    self::STORAGE_STRATEGY_SET,
                    self::STORAGE_STRATEGY_SET_ARRAY,
                    self::STORAGE_STRATEGY_ATOMIC_SET,
                    self::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
                ];
                break;

            case $mapping['type'] === 'one':
                $defaultStrategy   = self::STORAGE_STRATEGY_SET;
                $allowedStrategies = [self::STORAGE_STRATEGY_SET];
                break;

            default:
                $defaultStrategy   = self::STORAGE_STRATEGY_SET;
                $allowedStrategies = [self::STORAGE_STRATEGY_SET];
                $type              = Type::getType($mapping['type']);
                if ($type instanceof Incrementable) {
                    $allowedStrategies[] = self::STORAGE_STRATEGY_INCREMENT;
                }
        }

        if (! isset($mapping['strategy'])) {
            $mapping['strategy'] = $defaultStrategy;
        }

        if (! in_array($mapping['strategy'], $allowedStrategies)) {
            throw MappingException::invalidStorageStrategy($this->name, $mapping['fieldName'], $mapping['type'], $mapping['strategy']);
        }

        if (
            isset($mapping['reference']) && $mapping['type'] === 'many' && $mapping['isOwningSide']
            && ! empty($mapping['sort']) && ! CollectionHelper::usesSet($mapping['strategy'])
        ) {
            throw MappingException::referenceManySortMustNotBeUsedWithNonSetCollectionStrategy($this->name, $mapping['fieldName'], $mapping['strategy']);
        }
    }

    /**
     * Map a single embedded document.
     */
    public function mapOneEmbedded(array $mapping): void
    {
        $mapping['embedded'] = true;
        $mapping['type']     = 'one';
        $this->mapField($mapping);
    }

    /**
     * Map a collection of embedded documents.
     */
    public function mapManyEmbedded(array $mapping): void
    {
        $mapping['embedded'] = true;
        $mapping['type']     = 'many';
        $this->mapField($mapping);
    }

    /**
     * Map a single document reference.
     */
    public function mapOneReference(array $mapping): void
    {
        $mapping['reference'] = true;
        $mapping['type']      = 'one';
        $this->mapField($mapping);
    }

    /**
     * Map a collection of document references.
     */
    public function mapManyReference(array $mapping): void
    {
        $mapping['reference'] = true;
        $mapping['type']      = 'many';
        $this->mapField($mapping);
    }

    /**
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @internal
     */
    public function addInheritedFieldMapping(array $fieldMapping): void
    {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;

        if (! isset($fieldMapping['association'])) {
            return;
        }

        $this->associationMappings[$fieldMapping['fieldName']] = $fieldMapping;
    }

    /**
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @internal
     *
     * @throws MappingException
     */
    public function addInheritedAssociationMapping(array $mapping): void
    {
        $this->associationMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     */
    public function hasReference(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['reference']);
    }

    /**
     * Checks whether the class has a mapped embed with the given field name.
     */
    public function hasEmbed(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['embedded']);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped association (embed or reference) with the given field name.
     */
    public function hasAssociation($fieldName): bool
    {
        return $this->hasReference($fieldName) || $this->hasEmbed($fieldName);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a single valued association.
     */
    public function isSingleValuedAssociation($fieldName): bool
    {
        return $this->isSingleValuedReference($fieldName) || $this->isSingleValuedEmbed($fieldName);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a collection valued association.
     */
    public function isCollectionValuedAssociation($fieldName): bool
    {
        return $this->isCollectionValuedReference($fieldName) || $this->isCollectionValuedEmbed($fieldName);
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     */
    public function isSingleValuedReference(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::REFERENCE_ONE;
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     */
    public function isCollectionValuedReference(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::REFERENCE_MANY;
    }

    /**
     * Checks whether the class has a mapped embedded document for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     */
    public function isSingleValuedEmbed(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::EMBED_ONE;
    }

    /**
     * Checks whether the class has a mapped embedded document for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     */
    public function isCollectionValuedEmbed(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::EMBED_MANY;
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     */
    public function setIdGenerator(IdGenerator $generator): void
    {
        $this->idGenerator = $generator;
    }

    /**
     * Casts the identifier to its portable PHP type.
     *
     * @param mixed $id
     *
     * @return mixed $id
     */
    public function getPHPIdentifierValue($id)
    {
        $idType = $this->fieldMappings[$this->identifier]['type'];

        return Type::getType($idType)->convertToPHPValue($id);
    }

    /**
     * Casts the identifier to its database type.
     *
     * @param mixed $id
     *
     * @return mixed $id
     */
    public function getDatabaseIdentifierValue($id)
    {
        $idType = $this->fieldMappings[$this->identifier]['type'];

        return Type::getType($idType)->convertToDatabaseValue($id);
    }

    /**
     * Sets the document identifier of a document.
     *
     * The value will be converted to a PHP type before being set.
     *
     * @param mixed $id
     */
    public function setIdentifierValue(object $document, $id): void
    {
        $id = $this->getPHPIdentifierValue($id);
        $this->reflFields[$this->identifier]->setValue($document, $id);
    }

    /**
     * Gets the document identifier as a PHP type.
     *
     * @return mixed $id
     */
    public function getIdentifierValue(object $document)
    {
        return $this->reflFields[$this->identifier]->getValue($document);
    }

    /**
     * {@inheritDoc}
     *
     * Since MongoDB only allows exactly one identifier field this is a proxy
     * to {@see getIdentifierValue()} and returns an array with the identifier
     * field as a key.
     */
    public function getIdentifierValues($object): array
    {
        return [$this->identifier => $this->getIdentifierValue($object)];
    }

    /**
     * Get the document identifier object as a database type.
     *
     * @return mixed $id
     */
    public function getIdentifierObject(object $document)
    {
        return $this->getDatabaseIdentifierValue($this->getIdentifierValue($document));
    }

    /**
     * Sets the specified field to the specified value on the given document.
     *
     * @param mixed $value
     */
    public function setFieldValue(object $document, string $field, $value): void
    {
        if ($document instanceof GhostObjectInterface && ! $document->isProxyInitialized()) {
            //property changes to an uninitialized proxy will not be tracked or persisted,
            //so the proxy needs to be loaded first.
            $document->initializeProxy();
        }

        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @return mixed
     */
    public function getFieldValue(object $document, string $field)
    {
        if ($document instanceof GhostObjectInterface && $field !== $this->identifier && ! $document->isProxyInitialized()) {
            $document->initializeProxy();
        }

        return $this->reflFields[$field]->getValue($document);
    }

    /**
     * Gets the mapping of a field.
     *
     * @throws MappingException If the $fieldName is not found in the fieldMappings array.
     */
    public function getFieldMapping(string $fieldName): array
    {
        if (! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return $this->fieldMappings[$fieldName];
    }

    /**
     * Gets mappings of fields holding embedded document(s).
     */
    public function getEmbeddedFieldsMappings(): array
    {
        return array_filter(
            $this->associationMappings,
            static function ($assoc) {
                return ! empty($assoc['embedded']);
            }
        );
    }

    /**
     * Gets the field mapping by its DB name.
     * E.g. it returns identifier's mapping when called with _id.
     *
     * @throws MappingException
     */
    public function getFieldMappingByDbFieldName(string $dbFieldName): array
    {
        foreach ($this->fieldMappings as $mapping) {
            if ($mapping['name'] === $dbFieldName) {
                return $mapping;
            }
        }

        throw MappingException::mappingNotFoundByDbName($this->name, $dbFieldName);
    }

    /**
     * Check if the field is not null.
     */
    public function isNullable(string $fieldName): bool
    {
        $mapping = $this->getFieldMapping($fieldName);

        return isset($mapping['nullable']) && $mapping['nullable'] === true;
    }

    /**
     * Checks whether the document has a discriminator field and value configured.
     */
    public function hasDiscriminator(): bool
    {
        return isset($this->discriminatorField, $this->discriminatorValue);
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     */
    public function setIdGeneratorType(int $generatorType): void
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Sets the Id generator options.
     */
    public function setIdGeneratorOptions(array $generatorOptions): void
    {
        $this->generatorOptions = $generatorOptions;
    }

    public function isInheritanceTypeNone(): bool
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_NONE;
    }

    /**
     * Checks whether the mapped class uses the SINGLE_COLLECTION inheritance mapping strategy.
     */
    public function isInheritanceTypeSingleCollection(): bool
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_SINGLE_COLLECTION;
    }

    /**
     * Checks whether the mapped class uses the COLLECTION_PER_CLASS inheritance mapping strategy.
     */
    public function isInheritanceTypeCollectionPerClass(): bool
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @param string[] $subclasses The names of all mapped subclasses.
     */
    public function setSubclasses(array $subclasses): void
    {
        foreach ($subclasses as $subclass) {
            $this->subClasses[] = $subclass;
        }
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     *
     * @param string[] $classNames
     */
    public function setParentClasses(array $classNames): void
    {
        $this->parentClasses = $classNames;

        if (count($classNames) <= 0) {
            return;
        }

        $this->rootDocumentName = (string) array_pop($classNames);
    }

    /**
     * Checks whether the class will generate a new \MongoDB\BSON\ObjectId instance for us.
     */
    public function isIdGeneratorAuto(): bool
    {
        return $this->generatorType === self::GENERATOR_TYPE_AUTO;
    }

    /**
     * Checks whether the class will use a collection to generate incremented identifiers.
     */
    public function isIdGeneratorIncrement(): bool
    {
        return $this->generatorType === self::GENERATOR_TYPE_INCREMENT;
    }

    /**
     * Checks whether the class will generate a uuid id.
     */
    public function isIdGeneratorUuid(): bool
    {
        return $this->generatorType === self::GENERATOR_TYPE_UUID;
    }

    /**
     * Checks whether the class uses no id generator.
     */
    public function isIdGeneratorNone(): bool
    {
        return $this->generatorType === self::GENERATOR_TYPE_NONE;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @throws LockException
     */
    public function setVersionMapping(array &$mapping): void
    {
        if (! Type::getType($mapping['type']) instanceof Versionable) {
            throw LockException::invalidVersionFieldType($mapping['type']);
        }

        $this->isVersioned  = true;
        $this->versionField = $mapping['fieldName'];
    }

    /**
     * Sets whether this class is to be versioned for optimistic locking.
     */
    public function setVersioned(bool $bool): void
    {
        $this->isVersioned = $bool;
    }

    /**
     * Sets the name of the field that is to be used for versioning if this class is
     * versioned for optimistic locking.
     */
    public function setVersionField(?string $versionField): void
    {
        $this->versionField = $versionField;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @throws LockException
     */
    public function setLockMapping(array &$mapping): void
    {
        if ($mapping['type'] !== 'int') {
            throw LockException::invalidLockFieldType($mapping['type']);
        }

        $this->isLockable = true;
        $this->lockField  = $mapping['fieldName'];
    }

    /**
     * Sets whether this class is to allow pessimistic locking.
     */
    public function setLockable(bool $bool): void
    {
        $this->isLockable = $bool;
    }

    /**
     * Sets the name of the field that is to be used for storing whether a document
     * is currently locked or not.
     */
    public function setLockField(string $lockField): void
    {
        $this->lockField = $lockField;
    }

    /**
     * Marks this class as read only, no change tracking is applied to it.
     */
    public function markReadOnly(): void
    {
        $this->isReadOnly = true;
    }

    public function getRootClass(): ?string
    {
        return $this->rootClass;
    }

    public function isView(): bool
    {
        return $this->isView;
    }

    public function markViewOf(string $rootClass): void
    {
        $this->isView    = true;
        $this->rootClass = $rootClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames(): array
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames(): array
    {
        return array_keys($this->associationMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName): ?string
    {
        return isset($this->fieldMappings[$fieldName]) ?
            $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($assocName): ?string
    {
        if (! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName . "' is not an association.");
        }

        return $this->associationMappings[$assocName]['targetDocument'];
    }

    /**
     * Retrieve the collectionClass associated with an association
     */
    public function getAssociationCollectionClass(string $assocName): string
    {
        if (! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName . "' is not an association.");
        }

        if (! array_key_exists('collectionClass', $this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("collectionClass can only be applied to 'embedMany' and 'referenceMany' associations.");
        }

        return $this->associationMappings[$assocName]['collectionClass'];
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($fieldName): bool
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * Map a field.
     *
     * @throws MappingException
     */
    public function mapField(array $mapping): array
    {
        if (! isset($mapping['fieldName']) && isset($mapping['name'])) {
            $mapping['fieldName'] = $mapping['name'];
        }

        if (! isset($mapping['fieldName']) || ! is_string($mapping['fieldName'])) {
            throw MappingException::missingFieldName($this->name);
        }

        if (! isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }

        if ($this->identifier === $mapping['name'] && empty($mapping['id'])) {
            throw MappingException::mustNotChangeIdentifierFieldsType($this->name, (string) $mapping['name']);
        }

        if ($this->discriminatorField !== null && $this->discriminatorField === $mapping['name']) {
            throw MappingException::discriminatorFieldConflict($this->name, $this->discriminatorField);
        }

        if (isset($mapping['collectionClass'])) {
            $mapping['collectionClass'] = ltrim($mapping['collectionClass'], '\\');
        }

        if (! empty($mapping['collectionClass'])) {
            $rColl = new ReflectionClass($mapping['collectionClass']);
            if (! $rColl->implementsInterface('Doctrine\\Common\\Collections\\Collection')) {
                throw MappingException::collectionClassDoesNotImplementCommonInterface($this->name, $mapping['fieldName'], $mapping['collectionClass']);
            }
        }

        if (isset($mapping['cascade']) && isset($mapping['embedded'])) {
            throw MappingException::cascadeOnEmbeddedNotAllowed($this->name, $mapping['fieldName']);
        }

        $cascades = isset($mapping['cascade']) ? array_map('strtolower', (array) $mapping['cascade']) : [];

        if (in_array('all', $cascades) || isset($mapping['embedded'])) {
            $cascades = ['remove', 'persist', 'refresh', 'merge', 'detach'];
        }

        if (isset($mapping['embedded'])) {
            unset($mapping['cascade']);
        } elseif (isset($mapping['cascade'])) {
            $mapping['cascade'] = $cascades;
        }

        $mapping['isCascadeRemove']  = in_array('remove', $cascades);
        $mapping['isCascadePersist'] = in_array('persist', $cascades);
        $mapping['isCascadeRefresh'] = in_array('refresh', $cascades);
        $mapping['isCascadeMerge']   = in_array('merge', $cascades);
        $mapping['isCascadeDetach']  = in_array('detach', $cascades);

        if (isset($mapping['id']) && $mapping['id'] === true) {
            $mapping['name']  = '_id';
            $this->identifier = $mapping['fieldName'];
            if (isset($mapping['strategy'])) {
                $this->generatorType = constant(self::class . '::GENERATOR_TYPE_' . strtoupper($mapping['strategy']));
            }

            $this->generatorOptions = $mapping['options'] ?? [];
            switch ($this->generatorType) {
                case self::GENERATOR_TYPE_AUTO:
                    $mapping['type'] = 'id';
                    break;
                default:
                    if (! empty($this->generatorOptions['type'])) {
                        $mapping['type'] = $this->generatorOptions['type'];
                    } elseif (empty($mapping['type'])) {
                        $mapping['type'] = $this->generatorType === self::GENERATOR_TYPE_INCREMENT ? Type::INT : Type::CUSTOMID;
                    }
            }

            unset($this->generatorOptions['type']);
        }

        if (! isset($mapping['nullable'])) {
            $mapping['nullable'] = false;
        }

        if (
            isset($mapping['reference'])
            && isset($mapping['storeAs'])
            && $mapping['storeAs'] === self::REFERENCE_STORE_AS_ID
            && ! isset($mapping['targetDocument'])
        ) {
            throw MappingException::simpleReferenceRequiresTargetDocument($this->name, $mapping['fieldName']);
        }

        if (
            isset($mapping['reference']) && empty($mapping['targetDocument']) && empty($mapping['discriminatorMap']) &&
                (isset($mapping['mappedBy']) || isset($mapping['inversedBy']))
        ) {
            throw MappingException::owningAndInverseReferencesRequireTargetDocument($this->name, $mapping['fieldName']);
        }

        if ($this->isEmbeddedDocument && $mapping['type'] === 'many' && isset($mapping['strategy']) && CollectionHelper::isAtomic($mapping['strategy'])) {
            throw MappingException::atomicCollectionStrategyNotAllowed($mapping['strategy'], $this->name, $mapping['fieldName']);
        }

        if (isset($mapping['repositoryMethod']) && ! (empty($mapping['skip']) && empty($mapping['limit']) && empty($mapping['sort']))) {
            throw MappingException::repositoryMethodCanNotBeCombinedWithSkipLimitAndSort($this->name, $mapping['fieldName']);
        }

        if (isset($mapping['targetDocument']) && isset($mapping['discriminatorMap'])) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.2',
                'Mapping both "targetDocument" and "discriminatorMap" on field "%s" in class "%s" is deprecated. Only one of them can be used at a time',
                $mapping['fieldName'],
                $this->name
            );
        }

        if (isset($mapping['reference']) && $mapping['type'] === 'one') {
            $mapping['association'] = self::REFERENCE_ONE;
        }

        if (isset($mapping['reference']) && $mapping['type'] === 'many') {
            $mapping['association'] = self::REFERENCE_MANY;
        }

        if (isset($mapping['embedded']) && $mapping['type'] === 'one') {
            $mapping['association'] = self::EMBED_ONE;
        }

        if (isset($mapping['embedded']) && $mapping['type'] === 'many') {
            $mapping['association'] = self::EMBED_MANY;
        }

        if (isset($mapping['association']) && ! isset($mapping['targetDocument']) && ! isset($mapping['discriminatorField'])) {
            $mapping['discriminatorField'] = self::DEFAULT_DISCRIMINATOR_FIELD;
        }

        if (isset($mapping['version'])) {
            $mapping['notSaved'] = true;
            $this->setVersionMapping($mapping);
        }

        if (isset($mapping['lock'])) {
            $mapping['notSaved'] = true;
            $this->setLockMapping($mapping);
        }

        $mapping['isOwningSide']  = true;
        $mapping['isInverseSide'] = false;
        if (isset($mapping['reference'])) {
            if (isset($mapping['inversedBy']) && $mapping['inversedBy']) {
                $mapping['isOwningSide']  = true;
                $mapping['isInverseSide'] = false;
            }

            if (isset($mapping['mappedBy']) && $mapping['mappedBy']) {
                $mapping['isInverseSide'] = true;
                $mapping['isOwningSide']  = false;
            }

            if (isset($mapping['repositoryMethod'])) {
                $mapping['isInverseSide'] = true;
                $mapping['isOwningSide']  = false;
            }

            if (! isset($mapping['orphanRemoval'])) {
                $mapping['orphanRemoval'] = false;
            }
        }

        if (! empty($mapping['prime']) && ($mapping['association'] !== self::REFERENCE_MANY || ! $mapping['isInverseSide'])) {
            throw MappingException::referencePrimersOnlySupportedForInverseReferenceMany($this->name, $mapping['fieldName']);
        }

        if ($this->isFile && ! $this->isAllowedGridFSField($mapping['name'])) {
            throw MappingException::fieldNotAllowedForGridFS($this->name, $mapping['fieldName']);
        }

        $this->applyStorageStrategy($mapping);
        $this->checkDuplicateMapping($mapping);
        $this->typeRequirementsAreMet($mapping);

        $deprecatedTypes = [
            Type::BOOLEAN => Type::BOOL,
            Type::INTEGER => Type::INT,
            Type::INTID => Type::INT,
        ];
        if (isset($deprecatedTypes[$mapping['type']])) {
            trigger_deprecation(
                'doctrine/mongodb-odm',
                '2.1',
                'The "%s" mapping type is deprecated. Use "%s" instead.',
                $mapping['type'],
                $deprecatedTypes[$mapping['type']]
            );
        }

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        if (isset($mapping['association'])) {
            $this->associationMappings[$mapping['fieldName']] = $mapping;
        }

        $reflProp = $this->reflectionService->getAccessibleProperty($this->name, $mapping['fieldName']);
        assert($reflProp instanceof ReflectionProperty);
        $this->reflFields[$mapping['fieldName']] = $reflProp;

        return $mapping;
    }

    /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * Parts that are also NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     *
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = [
            'fieldMappings',
            'associationMappings',
            'identifier',
            'name',
            'db',
            'collection',
            'readPreference',
            'readPreferenceTags',
            'writeConcern',
            'rootDocumentName',
            'generatorType',
            'generatorOptions',
            'idGenerator',
            'indexes',
            'shardKey',
        ];

        // The rest of the metadata is only serialized if necessary.
        if ($this->changeTrackingPolicy !== self::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $serialized[] = 'changeTrackingPolicy';
        }

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->inheritanceType !== self::INHERITANCE_TYPE_NONE || $this->discriminatorField !== null) {
            $serialized[] = 'inheritanceType';
            $serialized[] = 'discriminatorField';
            $serialized[] = 'discriminatorValue';
            $serialized[] = 'discriminatorMap';
            $serialized[] = 'defaultDiscriminatorValue';
            $serialized[] = 'parentClasses';
            $serialized[] = 'subClasses';
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->isEmbeddedDocument) {
            $serialized[] = 'isEmbeddedDocument';
        }

        if ($this->isQueryResultDocument) {
            $serialized[] = 'isQueryResultDocument';
        }

        if ($this->isView()) {
            $serialized[] = 'isView';
            $serialized[] = 'rootClass';
        }

        if ($this->isFile) {
            $serialized[] = 'isFile';
            $serialized[] = 'bucketName';
            $serialized[] = 'chunkSizeBytes';
        }

        if ($this->isVersioned) {
            $serialized[] = 'isVersioned';
            $serialized[] = 'versionField';
        }

        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }

        if ($this->collectionCapped) {
            $serialized[] = 'collectionCapped';
            $serialized[] = 'collectionSize';
            $serialized[] = 'collectionMax';
        }

        if ($this->isReadOnly) {
            $serialized[] = 'isReadOnly';
        }

        return $serialized;
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     */
    public function __wakeup()
    {
        // Restore ReflectionClass and properties
        $this->reflectionService = new RuntimeReflectionService();
        $this->reflClass         = new ReflectionClass($this->name);
        $this->instantiator      = new Instantiator();

        foreach ($this->fieldMappings as $field => $mapping) {
            $prop = $this->reflectionService->getAccessibleProperty($mapping['declared'] ?? $this->name, $field);
            assert($prop instanceof ReflectionProperty);
            $this->reflFields[$field] = $prop;
        }
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     */
    public function newInstance(): object
    {
        return $this->instantiator->instantiate($this->name);
    }

    private function isAllowedGridFSField(string $name): bool
    {
        return in_array($name, self::ALLOWED_GRIDFS_FIELDS, true);
    }

    private function typeRequirementsAreMet(array $mapping): void
    {
        if ($mapping['type'] === Type::DECIMAL128 && ! extension_loaded('bcmath')) {
            throw MappingException::typeRequirementsNotFulfilled($this->name, $mapping['fieldName'], Type::DECIMAL128, 'ext-bcmath is missing');
        }
    }

    private function checkDuplicateMapping(array $mapping): void
    {
        if ($mapping['notSaved'] ?? false) {
            return;
        }

        foreach ($this->fieldMappings as $fieldName => $otherMapping) {
            // Ignore fields with the same name - we can safely override their mapping
            if ($mapping['fieldName'] === $fieldName) {
                continue;
            }

            // Ignore fields with a different name in the database
            if ($mapping['name'] !== $otherMapping['name']) {
                continue;
            }

            // If the other field is not saved, ignore it as well
            if ($otherMapping['notSaved'] ?? false) {
                continue;
            }

            throw MappingException::duplicateDatabaseFieldName($this->getName(), $mapping['fieldName'], $mapping['name'], $fieldName);
        }
    }
}
