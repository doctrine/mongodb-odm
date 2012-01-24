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

use Doctrine\ODM\MongoDB\MongoDBException,
    Doctrine\ODM\MongoDB\LockException,
    ReflectionClass;

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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
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
     * NONE means Doctrine will not generate any id for us and you are responsible for manually
     * assigning an id.
     */
    const GENERATOR_TYPE_NONE = 5;


    const REFERENCE_ONE  = 1;
    const REFERENCE_MANY = 2;
    const EMBED_ONE      = 3;
    const EMBED_MANY     = 4;
    const MANY           = 'many';
    const ONE            = 'one';

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
     * READ-ONLY: The name of the mongo database the document is mapped to.
     */
    public $db;

    /**
     * READ-ONLY: The name of the monge collection the document is mapped to.
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
     * READ-ONLY: The array of indexes for the document collection.
     */
    public $indexes = array();

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
     * @var array
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
     * @var AbstractIdGenerator
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
     * @var ReflectionClass
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
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        if ( ! $this->reflClass) {
            $this->reflClass = new ReflectionClass($this->name);
        }
        return $this->reflClass;
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s),
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifier === $fieldName ? true : false;
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
     * Gets the mapped identifier field of this class.
     *
     * @return string $identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get identifier field names of this class.
     *
     * Since MongoDB only allows exactly one identifier field this is a proxy
     * to {@see getIdentifier()} and returns an array.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
        return array($this->identifier);
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     *
     * @return boolean
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
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Dispatches the lifecycle event of the given document to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event The lifecycle event.
     * @param Document $document The Document on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $document, array $arguments = null)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            if ($arguments !== null) {
                call_user_func_array(array($document, $callback), $arguments);
            } else {
                $document->$callback();
            }
        }
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     * @return boolean
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
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
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     *
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
    {
        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for documents of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Sets the discriminator field name.
     *
     * @param string $discriminatorField
     * @see getDiscriminatorField()
     */
    public function setDiscriminatorField($discriminatorField)
    {
        if ( ! isset($discriminatorField['name']) && isset($discriminatorField['fieldName'])) {
            $discriminatorField['name'] = $discriminatorField['fieldName'];
        }
        if (isset($this->fieldMappings[$discriminatorField['name']])) {
            throw MongoDBException::duplicateFieldMapping($this->name, $discriminatorField['name']);
        }
        $this->discriminatorField = $discriminatorField;
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @param array $map
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
                    throw MongoDBException::invalidClassInDiscriminatorMap($className, $this->name);
                }
                if (is_subclass_of($className, $this->name)) {
                    $this->subClasses[] = $className;
                }
            }
        }
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
                } elseif(is_string($value)) {
                    $lower = strtolower($value);
                    if ($lower === 'asc') {
                        return 1;
                    } elseif ($lower === 'desc') {
                        return -1;
                    } else {
                        return $value;
                    }
                } else {
                    return $value;
                }
            }, $keys),
            'options' => $options
        );
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
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
    }

    /**
     * The name of this Document class.
     *
     * @return string $name The Document class name.
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
     * @param string $collection The collection name.
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
        return $this->file ? true :false;
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
     */
    public function mapField(array $mapping)
    {
        if ( ! isset($mapping['fieldName']) && isset($mapping['name'])) {
            $mapping['fieldName'] = $mapping['name'];
        }
        if ( ! isset($mapping['fieldName'])) {
            throw MongoDBException::missingFieldName($this->name);
        }
        if ( ! isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }
        if (isset($this->fieldMappings[$mapping['fieldName']])) {
            throw MongoDBException::duplicateFieldMapping($this->name, $mapping['fieldName']);
        }
        if ($this->discriminatorField['name'] === $mapping['fieldName']) {
            throw MongoDBException::duplicateFieldMapping($this->name, $mapping['fieldName']);
        }
        if (isset($mapping['targetDocument']) && strpos($mapping['targetDocument'], '\\') === false && strlen($this->namespace)) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
        }

        if (isset($mapping['discriminatorMap'])) {
            foreach ($mapping['discriminatorMap'] as $key => $class) {
                if (strpos($class, '\\') === false && strlen($this->namespace)) {
                    $mapping['discriminatorMap'][$key] = $this->namespace . '\\' . $class;
                }
            }
        }

        if (isset($mapping['cascade']) && is_string($mapping['cascade'])) {
            $mapping['cascade'] = array($mapping['cascade']);
        }
        if (isset($mapping['cascade']) && in_array('all', (array) $mapping['cascade'])) {
            unset($mapping['cascade']);
            $default = true;
        } else {
            $default = false;
        }
        $mapping['isCascadeRemove'] = $default;
        $mapping['isCascadePersist'] = $default;
        $mapping['isCascadeRefresh'] = $default;
        $mapping['isCascadeMerge'] = $default;
        $mapping['isCascadeDetach'] = $default;
        $mapping['isCascadeCallbacks'] = $default;
        if (isset($mapping['cascade']) && is_array($mapping['cascade'])) {
            foreach ($mapping['cascade'] as $cascade) {
                $mapping['isCascade' . ucfirst($cascade)] = true;
            }
        }
        unset($mapping['cascade']);
        if (isset($mapping['file']) && $mapping['file'] === true) {
            $this->file = $mapping['fieldName'];
            $mapping['name'] = 'file';
        }
        if (isset($mapping['distance']) && $mapping['distance'] === true) {
            $this->distance = $mapping['fieldName'];
        }
        if (isset($mapping['id']) && $mapping['id'] === true) {
            $mapping['name'] = '_id';
            $mapping['type'] = isset($mapping['type']) ? $mapping['type'] : 'id';
            $this->identifier = $mapping['fieldName'];
            if (isset($mapping['strategy'])) {
                $generatorType = constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::GENERATOR_TYPE_' . strtoupper($mapping['strategy']));
                if ($generatorType !== self::GENERATOR_TYPE_AUTO) {
                    $mapping['type'] = 'custom_id';
                }
                $this->generatorType = $generatorType;
                $this->generatorOptions = isset($mapping['options']) ? $mapping['options'] : array();
            }
        }
        if ( ! isset($mapping['nullable'])) {
            $mapping['nullable'] = false;
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
        /*
        if (isset($mapping['type']) && ($mapping['type'] === 'one' || $mapping['type'] === 'many')) {
            $mapping['type'] = $mapping['type'] === 'one' ? self::ONE : self::MANY;
        }
        */
        if (isset($mapping['version'])) {
            $this->setVersionMapping($mapping);
        }
        if (isset($mapping['lock'])) {
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
        }
        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        return $mapping;
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
     * @param array $mapping
     */
    public function addInheritedFieldMapping(array $fieldMapping)
    {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;
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
     * Checks whether the class has a mapped association (embed or reference) with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return $this->hasReference($fieldName) || $this->hasEmbed($fieldName);
    }

    /**
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a single valued association.
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return $this->isSingleValuedReference($fieldName) || $this->isSingleValuedEmbed($fieldName);
    }

    /**
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a collection valued association.
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is collection-valued, FALSE otherwise.
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
     * @param AbstractIdGenerator $generator
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
        return Types\Type::getType($idType)->convertToPHPValue($id);
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
        return Types\Type::getType($idType)->convertToDatabaseValue($id);
    }

    /**
     * Sets the document identifier of a document.
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
     * Gets the document identifier.
     *
     * @param object $document
     * @return string $id
     */
    public function getIdentifierValue($document)
    {
        return (string) $this->reflFields[$this->identifier]->getValue($document);
    }

    /**
     * Get identifier values of this document.
     *
     * Since MongoDB only allows exactly one identifier field this is a proxy
     * to {@see getIdentifierValue()} and returns an array with the identifier
     * field as a key.
     *
     * @param object $document
     * @return array
     */
    public function getIdentifierValues($document)
    {
        return array($this->identifier => $this->getIdentifierValue($document));
    }

    /**
     * Get the document identifier object.
     *
     * @param string $document
     * @return MongoId $id  The MongoID object.
     */
    public function getIdentifierObject($document)
    {
        if ($id = $this->getIdentifierValue($document)) {
            return $this->getDatabaseIdentifierValue($id);
        }
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
        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @param object $document
     * @param string $field
     */
    public function getFieldValue($document, $field)
    {
        return $this->reflFields[$field]->getValue($document);
    }

    /**
     * Gets the mapping of a field.
     *
     * @param string $fieldName  The field name.
     * @return array  The field mapping.
     */
    public function getFieldMapping($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MongoDBException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Check if the field is not null.
     *
     * @param string $fieldName  The field name
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
        return $this->discriminatorField && $this->discriminatorValue ? true : false;
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Sets the Id generator options.
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
     * @return boolean TRUE if the class participates in a SINGLE_COLLECTION inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeSingleCollection()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_SINGLE_COLLECTION;
    }

    /**
     * Checks whether the mapped class uses the COLLECTION_PER_CLASS inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a COLLECTION_PER_CLASS inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeCollectionPerClass()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @param array $subclasses The names of all mapped subclasses.
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
     */
    public function setVersionMapping(array &$mapping)
    {
        if ($mapping['type'] !== 'int' && $mapping['type'] !== 'date') {
            throw LockException::invalidVersionFieldType($mapping['type']);
        }
        $this->isVersioned = true;
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
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * A numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array
     */
    public function getAssociationNames()
    {
        throw new \BadMethodCallException(__METHOD__.'() is not implemented yet.');
    }

    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     * @return Doctrine\DBAL\Types\Type
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) ?
                $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param string $assocName
     * @return string
     */
    public function getAssociationTargetClass($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'($assocName) is not implemented yet.');
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function isAssociationInverseSide($fieldName)
    {
        throw new \BadMethodCallException(__METHOD__.'() is not implemented yet.');
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        throw new \BadMethodCallException(__METHOD__.'() is not implemented yet.');
    }
}