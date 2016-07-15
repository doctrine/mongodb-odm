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

use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;

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
 * @since       1.0
 */
class ClassMetadataInfo implements \Doctrine\Common\Persistence\Mapping\ClassMetadata
{
    /* The Id generator types. */
    /**
     * AUTO means Doctrine will automatically create a new \MongoId instance for us.
     */
    const GENERATOR_TYPE_AUTO = 1;

    /**
     * INCREMENT means a separate collection is used for maintaining and incrementing id generation.
     * Offers full portability.
     */
    const GENERATOR_TYPE_INCREMENT = 2;

    /**
     * UUID means Doctrine will generate a uuid for us.
     */
    const GENERATOR_TYPE_UUID = 3;

    /**
     * ALNUM means Doctrine will generate Alpha-numeric string identifiers, using the INCREMENT
     * generator to ensure identifier uniqueness
     */
    const GENERATOR_TYPE_ALNUM = 4;

    /**
     * CUSTOM means Doctrine expect a class parameter. It will then try to initiate that class
     * and pass other options to the generator. It will throw an Exception if the class
     * does not exist or if an option was passed for that there is not setter in the new
     * generator class.
     *
     * The class  will have to be a subtype of AbstractIdGenerator.
     */
    const GENERATOR_TYPE_CUSTOM = 5;

    /**
     * NONE means Doctrine will not generate any id for us and you are responsible for manually
     * assigning an id.
     */
    const GENERATOR_TYPE_NONE = 6;

    /**
     * Default discriminator field name.
     *
     * This is used for associations value for associations where a that do not define a "targetDocument" or
     * "discriminatorField" option in their mapping.
     */
    const DEFAULT_DISCRIMINATOR_FIELD = '_doctrine_class_name';

    const REFERENCE_ONE = 1;
    const REFERENCE_MANY = 2;
    const EMBED_ONE = 3;
    const EMBED_MANY = 4;
    const MANY = 'many';
    const ONE = 'one';

    /**
     * The types of storeAs references
     */
    const REFERENCE_STORE_AS_ID = 'id';
    const REFERENCE_STORE_AS_DB_REF = 'dbRef';
    const REFERENCE_STORE_AS_DB_REF_WITH_DB = 'dbRefWithDb';

    /* The inheritance mapping types */
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    const INHERITANCE_TYPE_NONE = 1;

    /**
     * SINGLE_COLLECTION means the class will be persisted according to the rules of
     * <tt>Single Collection Inheritance</tt>.
     */
    const INHERITANCE_TYPE_SINGLE_COLLECTION = 2;

    /**
     * COLLECTION_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Collection Inheritance</tt>.
     */
    const INHERITANCE_TYPE_COLLECTION_PER_CLASS = 3;

    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const CHANGETRACKING_DEFERRED_IMPLICIT = 1;

    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const CHANGETRACKING_DEFERRED_EXPLICIT = 2;

    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    const CHANGETRACKING_NOTIFY = 3;

    /**
     * SET means that fields will be written to the database using a $set operator
     */
    const STORAGE_STRATEGY_SET = 'set';

    /**
     * INCREMENT means that fields will be written to the database by calculating
     * the difference and using the $inc operator
     */
    const STORAGE_STRATEGY_INCREMENT = 'increment';

    const STORAGE_STRATEGY_PUSH_ALL = 'pushAll';
    const STORAGE_STRATEGY_ADD_TO_SET = 'addToSet';
    const STORAGE_STRATEGY_ATOMIC_SET = 'atomicSet';
    const STORAGE_STRATEGY_ATOMIC_SET_ARRAY = 'atomicSetArray';
    const STORAGE_STRATEGY_SET_ARRAY = 'setArray';

    /**
     * READ-ONLY: The name of the mongo database the document is mapped to.
     */
    public $db;

    /**
     * READ-ONLY: The name of the mongo collection the document is mapped to.
     */
    public $collection;

    /**
     * READ-ONLY: If the collection should be a fixed size.
     */
    public $collectionCapped;

    /**
     * READ-ONLY: If the collection is fixed size, its size in bytes.
     */
    public $collectionSize;

    /**
     * READ-ONLY: If the collection is fixed size, the maximum number of elements to store in the collection.
     */
    public $collectionMax;

    /**
     * READ-ONLY: Describes the level of acknowledgement requested from MongoDB for write operations.
     */
    public $writeConcern;

    /**
     * READ-ONLY: The field name of the document identifier.
     */
    public $identifier;

    /**
     * READ-ONLY: The field that stores a file reference and indicates the
     * document is a file and should be stored on the MongoGridFS.
     */
    public $file;

    /**
     * READ-ONLY: The field that stores the calculated distance when performing geo spatial
     * queries.
     */
    public $distance;

    /**
     * READ-ONLY: Whether or not reads for this class are okay to read from a slave.
     */
    public $slaveOkay;

    /**
     * READ-ONLY: The array of indexes for the document collection.
     */
    public $indexes = array();

    /**
     * READ-ONLY: Keys and options describing shard key. Only for sharded collections.
     */
    public $shardKey;

    /**
     * READ-ONLY: Whether or not queries on this document should require indexes.
     */
    public $requireIndexes = false;

    /**
     * READ-ONLY: The name of the document class.
     */
    public $name;

    /**
     * READ-ONLY: The namespace the document class is contained in.
     *
     * @var string
     * @todo Not really needed. Usage could be localized.
     */
    public $namespace;

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
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = array();

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @var array
     */
    public $subClasses = array();

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var \ReflectionProperty[]
     */
    public $reflFields = array();

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var integer
     */
    public $inheritanceType = self::INHERITANCE_TYPE_NONE;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var string
     */
    public $generatorType = self::GENERATOR_TYPE_AUTO;

    /**
     * READ-ONLY: The Id generator options.
     *
     * @var array
     */
    public $generatorOptions = array();

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var \Doctrine\ODM\MongoDB\Id\AbstractIdGenerator
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
    public $fieldMappings = array();

    /**
     * READ-ONLY: The association mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * @var array
     */
    public $associationMappings = array();

    /**
     * READ-ONLY: Array of fields to also load with a given method.
     *
     * @var array
     */
    public $alsoLoadMethods = array();

    /**
     * READ-ONLY: The registered lifecycle callbacks for documents of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_COLLECTION inheritance mapping strategies
     * where a discriminator field is used.</b>
     *
     * @var mixed
     * @see discriminatorField
     */
    public $discriminatorValue;

    /**
     * READ-ONLY: The discriminator map of all mapped classes in the hierarchy.
     *
     * <b>This does only apply to the SINGLE_COLLECTION inheritance mapping strategy
     * where a discriminator field is used.</b>
     *
     * @var mixed
     * @see discriminatorField
     */
    public $discriminatorMap = array();

    /**
     * READ-ONLY: The definition of the discriminator field used in SINGLE_COLLECTION
     * inheritance mapping.
     *
     * @var string
     */
    public $discriminatorField;

    /**
     * READ-ONLY: The default value for discriminatorField in case it's not set in the document
     *
     * @var string
     * @see discriminatorField
     */
    public $defaultDiscriminatorValue;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public $isMappedSuperclass = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a embedded document.
     *
     * @var boolean
     */
    public $isEmbeddedDocument = false;

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var integer
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to be versioned
     * with optimistic locking.
     *
     * @var boolean $isVersioned
     */
    public $isVersioned;

    /**
     * READ-ONLY: The name of the field which is used for versioning in optimistic locking (if any).
     *
     * @var mixed $versionField
     */
    public $versionField;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to allow pessimistic
     * locking.
     *
     * @var boolean $isLockable
     */
    public $isLockable;

    /**
     * READ-ONLY: The name of the field which is used for locking a document.
     *
     * @var mixed $lockField
     */
    public $lockField;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-document mapping
     * metadata of the class with the given name.
     *
     * @param string $documentName The name of the document class the new instance is used for.
     */
    public function __construct($documentName)
    {
        $this->name = $documentName;
        $this->rootDocumentName = $documentName;
    }

    /**
     * {@inheritDoc}
     */
    public function getReflectionClass()
    {
        if ( ! $this->reflClass) {
            $this->reflClass = new \ReflectionClass($this->name);
        }

        return $this->reflClass;
    }

    /**
     * {@inheritDoc}
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifier === $fieldName;
    }

    /**
     * INTERNAL:
     * Sets the mapped identifier field of this class.
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     *
     * Since MongoDB only allows exactly one identifier field
     * this will always return an array with only one value
     */
    public function getIdentifier()
    {
        return array($this->identifier);
    }

    /**
     * {@inheritDoc}
     *
     * Since MongoDB only allows exactly one identifier field
     * this will always return an array with only one value
     */
    public function getIdentifierFieldNames()
    {
        return array($this->identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Sets the inheritance type used by the class and it's subclasses.
     *
     * @param integer $type
     */
    public function setInheritanceType($type)
    {
        $this->inheritanceType = $type;
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @param  string $fieldName
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $repositoryClassName The class name of the custom repository.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        if ($this->isEmbeddedDocument) {
            return;
        }

        if ($repositoryClassName && strpos($repositoryClassName, '\\') === false && strlen($this->namespace)) {
            $repositoryClassName = $this->namespace . '\\' . $repositoryClassName;
        }

        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Dispatches the lifecycle event of the given document by invoking all
     * registered callbacks.
     *
     * @param string $event     Lifecycle event
     * @param object $document  Document on which the event occurred
     * @param array  $arguments Arguments to pass to all callbacks
     * @throws \InvalidArgumentException if document class is not this class or
     *                                   a Proxy of this class
     */
    public function invokeLifecycleCallbacks($event, $document, array $arguments = null)
    {
        if ( ! $document instanceof $this->name) {
            throw new \InvalidArgumentException(sprintf('Expected document class "%s"; found: "%s"', $this->name, get_class($document)));
        }

        if (empty($this->lifecycleCallbacks[$event])) {
            return;
        }

        foreach ($this->lifecycleCallbacks[$event] as $callback) {
            if ($arguments !== null) {
                call_user_func_array(array($document, $callback), $arguments);
            } else {
                $document->$callback();
            }
        }
    }

    /**
     * Checks whether the class has callbacks registered for a lifecycle event.
     *
     * @param string $event Lifecycle event
     *
     * @return boolean
     */
    public function hasLifecycleCallbacks($event)
    {
        return ! empty($this->lifecycleCallbacks[$event]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->lifecycleCallbacks[$event]) ? $this->lifecycleCallbacks[$event] : array();
    }

    /**
     * Adds a lifecycle callback for documents of this class.
     *
     * If the callback is already registered, this is a NOOP.
     *
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
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
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Registers a method for loading document data before field hydration.
     *
     * Note: A method may be registered multiple times for different fields.
     * it will be invoked only once for the first field found.
     *
     * @param string       $method Method name
     * @param array|string $fields Database field name(s)
     */
    public function registerAlsoLoadMethod($method, $fields)
    {
        $this->alsoLoadMethods[$method] = is_array($fields) ? $fields : array($fields);
    }

    /**
     * Sets the AlsoLoad methods for documents of this class.
     *
     * Any previously registered methods are overwritten.
     *
     * @param array $methods
     */
    public function setAlsoLoadMethods(array $methods)
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
     * @param string $discriminatorField
     *
     * @throws MappingException If the discriminator field conflicts with the
     *                          "name" attribute of a mapped field.
     */
    public function setDiscriminatorField($discriminatorField)
    {
        if ($discriminatorField === null) {
            $this->discriminatorField = null;

            return;
        }

        // Handle array argument with name/fieldName keys for BC
        if (is_array($discriminatorField)) {
            if (isset($discriminatorField['name'])) {
                $discriminatorField = $discriminatorField['name'];
            } elseif (isset($discriminatorField['fieldName'])) {
                $discriminatorField = $discriminatorField['fieldName'];
            }
        }

        foreach ($this->fieldMappings as $fieldMapping) {
            if ($discriminatorField == $fieldMapping['name']) {
                throw MappingException::discriminatorFieldConflict($this->name, $discriminatorField);
            }
        }

        $this->discriminatorField = $discriminatorField;
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @param array $map
     *
     * @throws MappingException
     */
    public function setDiscriminatorMap(array $map)
    {
        foreach ($map as $value => $className) {
            if (strpos($className, '\\') === false && strlen($this->namespace)) {
                $className = $this->namespace . '\\' . $className;
            }
            $this->discriminatorMap[$value] = $className;
            if ($this->name == $className) {
                $this->discriminatorValue = $value;
            } else {
                if ( ! class_exists($className)) {
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
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies if the document has no discriminator value
     *
     * @param string $defaultDiscriminatorValue
     *
     * @throws MappingException
     */
    public function setDefaultDiscriminatorValue($defaultDiscriminatorValue)
    {
        if ($defaultDiscriminatorValue === null) {
            $this->defaultDiscriminatorValue = null;

            return;
        }

        if (!array_key_exists($defaultDiscriminatorValue, $this->discriminatorMap)) {
            throw MappingException::invalidDiscriminatorValue($defaultDiscriminatorValue, $this->name);
        }

        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }

    /**
     * Sets the discriminator value for this class.
     * Used for JOINED/SINGLE_TABLE inheritance and multiple document types in a single
     * collection.
     *
     * @param string $value
     */
    public function setDiscriminatorValue($value)
    {
        $this->discriminatorMap[$value] = $this->name;
        $this->discriminatorValue = $value;
    }

    /**
     * Sets the slaveOkay option applied to collections for this class.
     *
     * @param boolean|null $slaveOkay
     */
    public function setSlaveOkay($slaveOkay)
    {
        $this->slaveOkay = $slaveOkay === null ? null : (boolean) $slaveOkay;
    }

    /**
     * Add a index for this Document.
     *
     * @param array $keys Array of keys for the index.
     * @param array $options Array of options for the index.
     */
    public function addIndex($keys, array $options = array())
    {
        $this->indexes[] = array(
            'keys' => array_map(function($value) {
                if ($value == 1 || $value == -1) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    $lower = strtolower($value);
                    if ($lower === 'asc') {
                        return 1;
                    } elseif ($lower === 'desc') {
                        return -1;
                    }
                }
                return $value;
            }, $keys),
            'options' => $options
        );
    }

    /**
     * Set whether or not queries on this document should require indexes.
     *
     * @param bool $requireIndexes
     */
    public function setRequireIndexes($requireIndexes)
    {
        $this->requireIndexes = $requireIndexes;
    }

    /**
     * Returns the array of indexes for this Document.
     *
     * @return array $indexes The array of indexes.
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Checks whether this document has indexes or not.
     *
     * @return boolean
     */
    public function hasIndexes()
    {
        return $this->indexes ? true : false;
    }

    /**
     * Set shard key for this Document.
     *
     * @param array $keys Array of document keys.
     * @param array $options Array of sharding options.
     *
     * @throws MappingException
     */
    public function setShardKey(array $keys, array $options = array())
    {
        if ($this->inheritanceType === self::INHERITANCE_TYPE_SINGLE_COLLECTION && !is_null($this->shardKey)) {
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

            if ($this->fieldMappings[$field]['strategy'] !== static::STORAGE_STRATEGY_SET) {
                throw MappingException::onlySetStrategyAllowedInShardKey($this->getName(), $field);
            }
        }

        $this->shardKey = array(
            'keys' => array_map(function($value) {
                if ($value == 1 || $value == -1) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    $lower = strtolower($value);
                    if ($lower === 'asc') {
                        return 1;
                    } elseif ($lower === 'desc') {
                        return -1;
                    }
                }
                return $value;
            }, $keys),
            'options' => $options
        );
    }

    /**
     * @return array
     */
    public function getShardKey()
    {
        return $this->shardKey;
    }

    /**
     * Checks whether this document has shard key or not.
     *
     * @return bool
     */
    public function isSharded()
    {
        return $this->shardKey ? true : false;
    }

    /**
     * Sets the write concern used by this class.
     *
     * @param string $writeConcern
     */
    public function setWriteConcern($writeConcern)
    {
        $this->writeConcern = $writeConcern;
    }

    /**
     * @return string
     */
    public function getWriteConcern()
    {
        return $this->writeConcern;
    }

    /**
     * Whether there is a write concern configured for this class.
     *
     * @return bool
     */
    public function hasWriteConcern()
    {
        return $this->writeConcern !== null;
    }

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     */
    public function setChangeTrackingPolicy($policy)
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredExplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredImplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return boolean
     */
    public function isChangeTrackingNotify()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_NOTIFY;
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return array An array of ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     *
     * @return \ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The namespace this Document class belongs to.
     *
     * @return string $namespace The namespace name.
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Returns the database this Document is mapped to.
     *
     * @return string $db The database name.
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * Set the database this Document is mapped to.
     *
     * @param string $db The database name
     */
    public function setDatabase($db)
    {
        $this->db = $db;
    }

    /**
     * Get the collection this Document is mapped to.
     *
     * @return string $collection The collection name.
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Sets the collection this Document is mapped to.
     *
     * @param array|string $name
     *
     * @throws \InvalidArgumentException
     */
    public function setCollection($name)
    {
        if (is_array($name)) {
            if ( ! isset($name['name'])) {
                throw new \InvalidArgumentException('A name key is required when passing an array to setCollection()');
            }
            $this->collectionCapped = isset($name['capped']) ? $name['capped'] : false;
            $this->collectionSize = isset($name['size']) ? $name['size'] : 0;
            $this->collectionMax = isset($name['max']) ? $name['max'] : 0;
            $this->collection = $name['name'];
        } else {
            $this->collection = $name;
        }
    }

    /**
     * Get whether or not the documents collection is capped.
     *
     * @return boolean
     */
    public function getCollectionCapped()
    {
        return $this->collectionCapped;
    }

    /**
     * Set whether or not the documents collection is capped.
     *
     * @param boolean $bool
     */
    public function setCollectionCapped($bool)
    {
        $this->collectionCapped = $bool;
    }

    /**
     * Get the collection size
     *
     * @return integer
     */
    public function getCollectionSize()
    {
        return $this->collectionSize;
    }

    /**
     * Set the collection size.
     *
     * @param integer $size
     */
    public function setCollectionSize($size)
    {
        $this->collectionSize = $size;
    }

    /**
     * Get the collection max.
     *
     * @return integer
     */
    public function getCollectionMax()
    {
        return $this->collectionMax;
    }

    /**
     * Set the collection max.
     *
     * @param integer $max
     */
    public function setCollectionMax($max)
    {
        $this->collectionMax = $max;
    }

    /**
     * Returns TRUE if this Document is mapped to a collection FALSE otherwise.
     *
     * @return boolean
     */
    public function isMappedToCollection()
    {
        return $this->collection ? true : false;
    }

    /**
     * Returns TRUE if this Document is a file to be stored on the MongoGridFS FALSE otherwise.
     *
     * @return boolean
     */
    public function isFile()
    {
        return $this->file ? true : false;
    }

    /**
     * Returns the file field name.
     *
     * @return string $file The file field name.
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the field name that stores the grid file.
     *
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Returns the distance field name.
     *
     * @return string $distance The distance field name.
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Set the field name that stores the distance.
     *
     * @param string $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * Map a field.
     *
     * @param array $mapping The mapping information.
     *
     * @return array
     *
     * @throws MappingException
     */
    public function mapField(array $mapping)
    {
        if ( ! isset($mapping['fieldName']) && isset($mapping['name'])) {
            $mapping['fieldName'] = $mapping['name'];
        }
        if ( ! isset($mapping['fieldName'])) {
            throw MappingException::missingFieldName($this->name);
        }
        if ( ! isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }
        if ($this->identifier === $mapping['name'] && empty($mapping['id'])) {
            throw MappingException::mustNotChangeIdentifierFieldsType($this->name, $mapping['name']);
        }
        if (isset($this->fieldMappings[$mapping['fieldName']])) {
            //throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
        }
        if ($this->discriminatorField !== null && $this->discriminatorField == $mapping['name']) {
            throw MappingException::discriminatorFieldConflict($this->name, $this->discriminatorField);
        }
        if (isset($mapping['targetDocument']) && strpos($mapping['targetDocument'], '\\') === false && strlen($this->namespace)) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
        }
        if (isset($mapping['collectionClass'])) {
            if (strpos($mapping['collectionClass'], '\\') === false && strlen($this->namespace)) {
                $mapping['collectionClass'] = $this->namespace . '\\' . $mapping['collectionClass'];
            }
            $mapping['collectionClass'] = ltrim($mapping['collectionClass'], '\\');
        }
        if ( ! empty($mapping['collectionClass'])) {
            $rColl = new \ReflectionClass($mapping['collectionClass']);
            if ( ! $rColl->implementsInterface('Doctrine\\Common\\Collections\\Collection')) {
                throw MappingException::collectionClassDoesNotImplementCommonInterface($this->name, $mapping['fieldName'], $mapping['collectionClass']);
            }
        }

        if (isset($mapping['discriminatorMap'])) {
            foreach ($mapping['discriminatorMap'] as $key => $class) {
                if (strpos($class, '\\') === false && strlen($this->namespace)) {
                    $mapping['discriminatorMap'][$key] = $this->namespace . '\\' . $class;
                }
            }
        }

        if (isset($mapping['cascade']) && isset($mapping['embedded'])) {
            throw MappingException::cascadeOnEmbeddedNotAllowed($this->name, $mapping['fieldName']);
        }

        $cascades = isset($mapping['cascade']) ? array_map('strtolower', (array) $mapping['cascade']) : array();

        if (in_array('all', $cascades) || isset($mapping['embedded'])) {
            $cascades = array('remove', 'persist', 'refresh', 'merge', 'detach');
        }

        if (isset($mapping['embedded'])) {
            unset($mapping['cascade']);
        } elseif (isset($mapping['cascade'])) {
            $mapping['cascade'] = $cascades;
        }

        $mapping['isCascadeRemove'] = in_array('remove', $cascades);
        $mapping['isCascadePersist'] = in_array('persist', $cascades);
        $mapping['isCascadeRefresh'] = in_array('refresh', $cascades);
        $mapping['isCascadeMerge'] = in_array('merge', $cascades);
        $mapping['isCascadeDetach'] = in_array('detach', $cascades);

        if (isset($mapping['type']) && $mapping['type'] === 'file') {
            $mapping['file'] = true;
        }
        if (isset($mapping['type']) && $mapping['type'] === 'increment') {
            $mapping['strategy'] = self::STORAGE_STRATEGY_INCREMENT;
        }
        if (isset($mapping['file']) && $mapping['file'] === true) {
            $this->file = $mapping['fieldName'];
            $mapping['name'] = 'file';
        }
        if (isset($mapping['distance']) && $mapping['distance'] === true) {
            $this->distance = $mapping['fieldName'];
        }
        if (isset($mapping['id']) && $mapping['id'] === true) {
            $mapping['name'] = '_id';
            $this->identifier = $mapping['fieldName'];
            if (isset($mapping['strategy'])) {
                $this->generatorType = constant(ClassMetadata::class . '::GENERATOR_TYPE_' . strtoupper($mapping['strategy']));
            }
            $this->generatorOptions = isset($mapping['options']) ? $mapping['options'] : array();
            switch ($this->generatorType) {
                case self::GENERATOR_TYPE_AUTO:
                    $mapping['type'] = 'id';
                    break;
                default:
                    if ( ! empty($this->generatorOptions['type'])) {
                        $mapping['type'] = $this->generatorOptions['type'];
                    } elseif (empty($mapping['type'])) {
                        $mapping['type'] = $this->generatorType === self::GENERATOR_TYPE_INCREMENT ? 'int_id' : 'custom_id';
                    }
            }
            unset($this->generatorOptions['type']);
        }

        if ( ! isset($mapping['nullable'])) {
            $mapping['nullable'] = false;
        }

        // Synchronize the "simple" and "storeAs" mapping information for backwards compatibility
        if (isset($mapping['simple']) && ($mapping['simple'] === true || $mapping['simple'] === 'true')) {
            $mapping['storeAs'] = ClassMetadataInfo::REFERENCE_STORE_AS_ID;
        }
        // Provide the correct value for the "simple" field for backwards compatibility
        if (isset($mapping['storeAs'])) {
            $mapping['simple'] = $mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID;
        }

        if (isset($mapping['reference'])
            && isset($mapping['storeAs'])
            && $mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID
            && ! isset($mapping['targetDocument'])
        ) {
            throw MappingException::simpleReferenceRequiresTargetDocument($this->name, $mapping['fieldName']);
        }

        if (isset($mapping['reference']) && empty($mapping['targetDocument']) && empty($mapping['discriminatorMap']) &&
                (isset($mapping['mappedBy']) || isset($mapping['inversedBy']))) {
            throw MappingException::owningAndInverseReferencesRequireTargetDocument($this->name, $mapping['fieldName']);
        }

        if ($this->isEmbeddedDocument && $mapping['type'] === 'many' && CollectionHelper::isAtomic($mapping['strategy'])) {
            throw MappingException::atomicCollectionStrategyNotAllowed($mapping['strategy'], $this->name, $mapping['fieldName']);
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

        /*
        if (isset($mapping['type']) && ($mapping['type'] === 'one' || $mapping['type'] === 'many')) {
            $mapping['type'] = $mapping['type'] === 'one' ? self::ONE : self::MANY;
        }
        */
        if (isset($mapping['version'])) {
            $mapping['notSaved'] = true;
            $this->setVersionMapping($mapping);
        }
        if (isset($mapping['lock'])) {
            $mapping['notSaved'] = true;
            $this->setLockMapping($mapping);
        }
        $mapping['isOwningSide'] = true;
        $mapping['isInverseSide'] = false;
        if (isset($mapping['reference'])) {
            if (isset($mapping['inversedBy']) && $mapping['inversedBy']) {
                $mapping['isOwningSide'] = true;
                $mapping['isInverseSide'] = false;
            }
            if (isset($mapping['mappedBy']) && $mapping['mappedBy']) {
                $mapping['isInverseSide'] = true;
                $mapping['isOwningSide'] = false;
            }
            if (isset($mapping['repositoryMethod'])) {
                $mapping['isInverseSide'] = true;
                $mapping['isOwningSide'] = false;
            }
            if (!isset($mapping['orphanRemoval'])) {
                $mapping['orphanRemoval'] = false;
            }
        }

        $this->applyStorageStrategy($mapping);

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        if (isset($mapping['association'])) {
            $this->associationMappings[$mapping['fieldName']] = $mapping;
        }

        return $mapping;
    }

    /**
     * Validates the storage strategy of a mapping for consistency
     * @param array $mapping
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    private function applyStorageStrategy(array &$mapping)
    {
        if (! isset($mapping['type']) || isset($mapping['id'])) {
            return;
        }

        switch (true) {
            case $mapping['type'] == 'int':
            case $mapping['type'] == 'float':
            case $mapping['type'] == 'increment':
                $defaultStrategy = self::STORAGE_STRATEGY_SET;
                $allowedStrategies = [self::STORAGE_STRATEGY_SET, self::STORAGE_STRATEGY_INCREMENT];
                break;

            case $mapping['type'] == 'many':
                $defaultStrategy = CollectionHelper::DEFAULT_STRATEGY;
                $allowedStrategies = [
                    self::STORAGE_STRATEGY_PUSH_ALL,
                    self::STORAGE_STRATEGY_ADD_TO_SET,
                    self::STORAGE_STRATEGY_SET,
                    self::STORAGE_STRATEGY_SET_ARRAY,
                    self::STORAGE_STRATEGY_ATOMIC_SET,
                    self::STORAGE_STRATEGY_ATOMIC_SET_ARRAY,
                ];
                break;

            default:
                $defaultStrategy = self::STORAGE_STRATEGY_SET;
                $allowedStrategies = [self::STORAGE_STRATEGY_SET];
        }

        if (! isset($mapping['strategy'])) {
            $mapping['strategy'] = $defaultStrategy;
        }

        if (! in_array($mapping['strategy'], $allowedStrategies)) {
            throw MappingException::invalidStorageStrategy($this->name, $mapping['fieldName'], $mapping['type'], $mapping['strategy']);
        }

        if (isset($mapping['reference']) && $mapping['type'] === 'many' && $mapping['isOwningSide']
            && ! empty($mapping['sort']) && ! CollectionHelper::usesSet($mapping['strategy'])) {
            throw MappingException::referenceManySortMustNotBeUsedWithNonSetCollectionStrategy($this->name, $mapping['fieldName'], $mapping['strategy']);
        }
    }

    /**
     * Map a MongoGridFSFile.
     *
     * @param array $mapping The mapping information.
     */
    public function mapFile(array $mapping)
    {
        $mapping['file'] = true;
        $mapping['type'] = 'file';
        $this->mapField($mapping);
    }

    /**
     * Map a single embedded document.
     *
     * @param array $mapping The mapping information.
     */
    public function mapOneEmbedded(array $mapping)
    {
        $mapping['embedded'] = true;
        $mapping['type'] = 'one';
        $this->mapField($mapping);
    }

    /**
     * Map a collection of embedded documents.
     *
     * @param array $mapping The mapping information.
     */
    public function mapManyEmbedded(array $mapping)
    {
        $mapping['embedded'] = true;
        $mapping['type'] = 'many';
        $this->mapField($mapping);
    }

    /**
     * Map a single document reference.
     *
     * @param array $mapping The mapping information.
     */
    public function mapOneReference(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'one';
        $this->mapField($mapping);
    }

    /**
     * Map a collection of document references.
     *
     * @param array $mapping The mapping information.
     */
    public function mapManyReference(array $mapping)
    {
        $mapping['reference'] = true;
        $mapping['type'] = 'many';
        $this->mapField($mapping);
    }

    /**
     * INTERNAL:
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @param array $fieldMapping
     */
    public function addInheritedFieldMapping(array $fieldMapping)
    {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;

        if (isset($fieldMapping['association'])) {
            $this->associationMappings[$fieldMapping['fieldName']] = $fieldMapping;
        }
    }

    /**
     * INTERNAL:
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @param array $mapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addInheritedAssociationMapping(array $mapping/*, $owningClassName = null*/)
    {
        $this->associationMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['reference']);
    }

    /**
     * Checks whether the class has a mapped embed with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasEmbed($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['embedded']);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped association (embed or reference) with the given field name.
     */
    public function hasAssociation($fieldName)
    {
        return $this->hasReference($fieldName) || $this->hasEmbed($fieldName);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a single valued association.
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return $this->isSingleValuedReference($fieldName) || $this->isSingleValuedEmbed($fieldName);
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a collection valued association.
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return $this->isCollectionValuedReference($fieldName) || $this->isCollectionValuedEmbed($fieldName);
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::REFERENCE_ONE;
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is collection-valued, FALSE otherwise.
     */
    public function isCollectionValuedReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::REFERENCE_MANY;
    }

    /**
     * Checks whether the class has a mapped embedded document for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedEmbed($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::EMBED_ONE;
    }

    /**
     * Checks whether the class has a mapped embedded document for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is collection-valued, FALSE otherwise.
     */
    public function isCollectionValuedEmbed($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['association']) &&
            $this->fieldMappings[$fieldName]['association'] === self::EMBED_MANY;
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param \Doctrine\ODM\MongoDB\Id\AbstractIdGenerator $generator
     */
    public function setIdGenerator($generator)
    {
        $this->idGenerator = $generator;
    }

    /**
     * Casts the identifier to its portable PHP type.
     *
     * @param mixed $id
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
     * @param object $document
     * @param mixed $id
     */
    public function setIdentifierValue($document, $id)
    {
        $id = $this->getPHPIdentifierValue($id);
        $this->reflFields[$this->identifier]->setValue($document, $id);
    }

    /**
     * Gets the document identifier as a PHP type.
     *
     * @param object $document
     * @return mixed $id
     */
    public function getIdentifierValue($document)
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
    public function getIdentifierValues($object)
    {
        return array($this->identifier => $this->getIdentifierValue($object));
    }

    /**
     * Get the document identifier object as a database type.
     *
     * @param object $document
     *
     * @return \MongoId $id The MongoID object.
     */
    public function getIdentifierObject($document)
    {
        return $this->getDatabaseIdentifierValue($this->getIdentifierValue($document));
    }

    /**
     * Sets the specified field to the specified value on the given document.
     *
     * @param object $document
     * @param string $field
     * @param mixed $value
     */
    public function setFieldValue($document, $field, $value)
    {
        if ($document instanceof Proxy && ! $document->__isInitialized()) {
            //property changes to an uninitialized proxy will not be tracked or persisted,
            //so the proxy needs to be loaded first.
            $document->__load();
        }

        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @param object $document
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue($document, $field)
    {
        if ($document instanceof Proxy && $field !== $this->identifier && ! $document->__isInitialized()) {
            $document->__load();
        }

        return $this->reflFields[$field]->getValue($document);
    }

    /**
     * Gets the mapping of a field.
     *
     * @param string $fieldName  The field name.
     *
     * @return array  The field mapping.
     *
     * @throws MappingException if the $fieldName is not found in the fieldMappings array
     */
    public function getFieldMapping($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Gets mappings of fields holding embedded document(s).
     *
     * @return array of field mappings
     */
    public function getEmbeddedFieldsMappings()
    {
        return array_filter(
            $this->associationMappings,
            function($assoc) { return ! empty($assoc['embedded']); }
        );
    }

    /**
     * Gets the field mapping by its DB name.
     * E.g. it returns identifier's mapping when called with _id.
     *
     * @param string $dbFieldName
     *
     * @return array
     * @throws MappingException
     */
    public function getFieldMappingByDbFieldName($dbFieldName)
    {
        foreach ($this->fieldMappings as $mapping) {
            if ($mapping['name'] == $dbFieldName) {
                return $mapping;
            }
        }

        throw MappingException::mappingNotFoundByDbName($this->name, $dbFieldName);
    }

    /**
     * Check if the field is not null.
     *
     * @param string $fieldName  The field name
     *
     * @return boolean  TRUE if the field is not null, FALSE otherwise.
     */
    public function isNullable($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);
        if ($mapping !== false) {
            return isset($mapping['nullable']) && $mapping['nullable'] == true;
        }
        return false;
    }

    /**
     * Checks whether the document has a discriminator field and value configured.
     *
     * @return boolean
     */
    public function hasDiscriminator()
    {
        return isset($this->discriminatorField, $this->discriminatorValue);
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     *
     * @param string $generatorType Generator type.
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Sets the Id generator options.
     *
     * @param array $generatorOptions Generator options.
     */
    public function setIdGeneratorOptions($generatorOptions)
    {
        $this->generatorOptions = $generatorOptions;
    }

    /**
     * @return boolean
     */
    public function isInheritanceTypeNone()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_NONE;
    }

    /**
     * Checks whether the mapped class uses the SINGLE_COLLECTION inheritance mapping strategy.
     *
     * @return boolean
     */
    public function isInheritanceTypeSingleCollection()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_SINGLE_COLLECTION;
    }

    /**
     * Checks whether the mapped class uses the COLLECTION_PER_CLASS inheritance mapping strategy.
     *
     * @return boolean
     */
    public function isInheritanceTypeCollectionPerClass()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @param string[] $subclasses The names of all mapped subclasses.
     */
    public function setSubclasses(array $subclasses)
    {
        foreach ($subclasses as $subclass) {
            if (strpos($subclass, '\\') === false && strlen($this->namespace)) {
                $this->subClasses[] = $this->namespace . '\\' . $subclass;
            } else {
                $this->subClasses[] = $subclass;
            }
        }
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     *
     * @param string[] $classNames
     */
    public function setParentClasses(array $classNames)
    {
        $this->parentClasses = $classNames;

        if (count($classNames) > 0) {
            $this->rootDocumentName = array_pop($classNames);
        }
    }

    /**
     * Checks whether the class will generate a new \MongoId instance for us.
     *
     * @return boolean TRUE if the class uses the AUTO generator, FALSE otherwise.
     */
    public function isIdGeneratorAuto()
    {
        return $this->generatorType == self::GENERATOR_TYPE_AUTO;
    }

    /**
     * Checks whether the class will use a collection to generate incremented identifiers.
     *
     * @return boolean TRUE if the class uses the INCREMENT generator, FALSE otherwise.
     */
    public function isIdGeneratorIncrement()
    {
        return $this->generatorType == self::GENERATOR_TYPE_INCREMENT;
    }

    /**
     * Checks whether the class will generate a uuid id.
     *
     * @return boolean TRUE if the class uses the UUID generator, FALSE otherwise.
     */
    public function isIdGeneratorUuid()
    {
        return $this->generatorType == self::GENERATOR_TYPE_UUID;
    }

    /**
     * Checks whether the class uses no id generator.
     *
     * @return boolean TRUE if the class does not use any id generator, FALSE otherwise.
     */
    public function isIdGeneratorNone()
    {
        return $this->generatorType == self::GENERATOR_TYPE_NONE;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @param array $mapping   The version field mapping array
     *
     * @throws LockException
     */
    public function setVersionMapping(array &$mapping)
    {
        if ($mapping['type'] !== 'int' && $mapping['type'] !== 'date') {
            throw LockException::invalidVersionFieldType($mapping['type']);
        }

        $this->isVersioned  = true;
        $this->versionField = $mapping['fieldName'];
    }

    /**
     * Sets whether this class is to be versioned for optimistic locking.
     *
     * @param boolean $bool
     */
    public function setVersioned($bool)
    {
        $this->isVersioned = $bool;
    }

    /**
     * Sets the name of the field that is to be used for versioning if this class is
     * versioned for optimistic locking.
     *
     * @param string $versionField
     */
    public function setVersionField($versionField)
    {
        $this->versionField = $versionField;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @param array $mapping   The version field mapping array
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     */
    public function setLockMapping(array &$mapping)
    {
        if ($mapping['type'] !== 'int') {
            throw LockException::invalidLockFieldType($mapping['type']);
        }

        $this->isLockable = true;
        $this->lockField = $mapping['fieldName'];
    }

    /**
     * Sets whether this class is to allow pessimistic locking.
     *
     * @param boolean $bool
     */
    public function setLockable($bool)
    {
        $this->isLockable = $bool;
    }

    /**
     * Sets the name of the field that is to be used for storing whether a document
     * is currently locked or not.
     *
     * @param string $lockField
     */
    public function setLockField($lockField)
    {
        $this->lockField = $lockField;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames()
    {
        return array_keys($this->associationMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) ?
            $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        if ( ! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName . "' is not an association.");
        }

        return $this->associationMappings[$assocName]['targetDocument'];
    }

    /**
     * Retrieve the collectionClass associated with an association
     *
     * @param string $assocName
     */
    public function getAssociationCollectionClass($assocName)
    {
        if ( ! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName . "' is not an association.");
        }

        if ( ! array_key_exists('collectionClass', $this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("collectionClass can only be applied to 'embedMany' and 'referenceMany' associations.");
        }

        return $this->associationMappings[$assocName]['collectionClass'];
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($fieldName)
    {
        throw new \BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        throw new \BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }
}
