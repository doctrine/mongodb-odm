Annotations Reference
=====================

In this chapter a reference of every Doctrine 2 ODM Annotation is
given with short explanations on their context and usage.

@AlsoLoad
---------

Specify one or more MongoDB fields to use for loading data if the original field
does not exist.

.. code-block:: php

    <?php

    /** @String @AlsoLoad("name") */
    public $fullName;

The ``$fullName`` property will be lodaed from ``fullName`` if it exists, but
fall back to ``name`` if it does not exist. If multiple fall back fields are
specified, ODM will consider them in order until the first is found.

Additionally, ``@AlsoLoad`` may annotate a method with one or more field names.
Before normal hydration, the field(s) will be considered in order and the method
will be invoked with the first value found as its single argument.

.. code-block:: php

    <?php

    /** @AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        list($this->firstName, $this->lastName) = explode(' ', $name);
    }

For additional information on using ``@AlsoLoad``, see
:doc:`Migrations <migrating-schemas>`.

@Bin
----

Alias of @Field, with "type" attribute set to
"bin". Converts value to
MongoBinData http://www.php.net/manual/en/class.mongobindata.php,
using MongoBinData::BYTE\_ARRAY type.

.. code-block:: php

    <?php

    /** @Bin */
    private $data;

@BinCustom
----------

Alias of @Field, with "type" attribute set to
"bin\_custom". Converts value to
MongoBinData http://www.php.net/manual/en/class.mongobindata.php,
using MongoBinData::CUSTOM type.

.. code-block:: php

    <?php

    /** @BinCustom */
    private $data;

@BinFunc
--------

Alias of @Field, with "type" attribute set to
"bin\_func". Converts value to
MongoBinData http://www.php.net/manual/en/class.mongobindata.php,
using MongoBinData::FUNC type.

.. code-block:: php

    <?php

    /** @BinFunc */
    private $data;

@BinMD5
-------

Alias of @Field, with "type" attribute set to
"bin\_md5". Converts value to
MongoBinData http://www.php.net/manual/en/class.mongobindata.php,
using MongoBinData::MD5 type.

.. code-block:: php

    <?php

    /** @BinMD5 */
    private $password;

@BinUUID
--------

Alias of @Field, with "type" attribute set to
"bin\_uuid". Converts value to
MongoBinData http://www.php.net/manual/en/class.mongobindata.php,
using MongoBinData::UUID type.

.. code-block:: php

    <?php

    /** @BinUUID */
    private $uuid;

@Boolean
--------

Alias of @Field, with "type" attribute set to
"boolean"

.. code-block:: php

    <?php

    /** @Boolean */
    private $active;

@Collection
-----------

Alias of @Field, with "type" attribute set to
"collection". Stores and retrieves the value as numeric indexed
array.

Example:

.. code-block:: php

    <?php

    /** @Collection */
    private $tags = array();

@Date
-----

Alias of @Field, with "type" attribute set to
"date" Converts value to
MongoDate http://www.php.net/manual/en/class.mongodate.php.

.. code-block:: php

    <?php

    /** @Date */
    private $createdAt;

@DiscriminatorField
-------------------

This annotation is required for the top-most class in a
:ref:`single collection inheritance <single_collection_inheritance>` hierarchy.
It takes a string as its only argument, which specifies the database field to
store a class name or key (if a discriminator map is used). ODM uses this field
during hydration to select the instantiation class.

Example:

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField("type")
     */
    class SuperUser
    {
        // ...
    }

.. note::

    For backwards compatibility, the discriminator field may also be specified
    via either the ``name`` or ``fieldName`` annotation attributes.

@DiscriminatorMap
-----------------

This annotation is required for the top-most class in a
:ref:`single collection inheritance <single_collection_inheritance>` hierarchy.
It takes an array as its only argument, which maps keys to class names. The
class names may be fully qualified or relative to the current namespace. When
a document is persisted to the database, its class name key will be stored in
the discriminator field instead of the fully qualified class name.

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField("type")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        // ...
    }

@Distance
---------

Use the @Distance annotation in combination with geospatial
indexes and when running $near queries the property will be
populated with a distance value.

.. code-block:: php

    <?php

    /**
     * @Document
     * @Index(keys={"coordinates"="2d"})
     */
    class Place
    {
        /** @Id */
        public $id;
    
        /** @EmbedOne(targetDocument="Coordinates") */
        public $coordinates;
    
        /** @Distance */
        public $distance;
    }
    
    /** @EmbeddedDocument */
    class Coordinates
    {
        /** @Float */
        public $latitude;
    
        /** @Float */
        public $longitude;
    }

Now you can run a near() query and access the distance. Get the
closest city to a set of coordinates:

.. code-block:: php

    <?php

    $city = $this->dm->createQuery('City')
        ->field('coordinates')->near(50, 60)
        ->limit(1)
        ->getQuery()
        ->getSingleResult();
    echo $city->distance;

@Document
---------

Required annotation to mark a PHP class as Document. Doctrine ODM
manages the persistence of all classes marked as document.

Optional attributes:

- 
   db - Document Manager uses the default mongo db database, unless it
   has database name to use set, this value can be specified to
   override database to use on per document basis.
- 
   collection - By default collection name is extracted from the
   document's class name, but this attribute can be used to override.
- 
   repositoryClass - Specifies custom repository class to use.
-
   indexes - Specifies an array of indexes for this document.
-
   requireIndexes - Specifies whether or not queries should require indexes.

Example:

.. code-block:: php

    <?php

    /**
     * @Document(
     *     db="documents",
     *     collection="users",
     *     repositoryClass="MyProject\UserRepository",
     *     indexes={
     *         @Index(keys={"username"="desc"}, options={"unique"=true})
     *     },
     *     requireIndexes=true
     * )
     */
    class User
    {
        //...
    }

@EmbedMany
----------

This annotation is similar to @EmbedOne, but
instead of embedding one document, it informs MongoDB to embed a
collection of documents

Optional attributes:

-
    targetDocument - A full class name of the target document.
-
    discriminatorField - The database field name to store the discriminator
    value within the embedded document.
-
    discriminatorMap - Map of discriminator values to class names.
-
    strategy - The strategy used to persist changes to the collection. Possible
    values are ``addToSet``, ``pushAll``, ``set``, and ``setArray``. ``pushAll``
    is the default. See :ref:`collection_strategies` for more information.

Example:

.. code-block:: php

    <?php

    /**
     * @EmbedMany(
     *     strategy="set",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "book"="Documents\BookTag",
     *         "song"="Documents\SongTag"
     *     }
     * )
     */
    private $tags = array();

Depending on the type of Document a value of ``user`` or ``author`` will be stored in a field named ``type``
and will be used to properly reconstruct the right class during hydration.

@EmbedOne
---------

The @EmbedOne annotation works almost exactly as the
@ReferenceOne, except that internally, the
document is embedded in the parent document in MongoDB. From
MongoDB docs:

    The key question in Mongo schema design is "does this object merit
    its own collection, or rather should it embed in objects in other
    collections?" In relational databases, each sub-item of interest
    typically becomes a separate table (unless denormalizing for
    performance). In Mongo, this is not recommended - embedding objects
    is much more efficient. Data is then collocated on disk;
    client-server turnarounds to the database are eliminated. So in
    general the question to ask is, "why would I not want to embed this
    object?"

Optional attributes:

- 
    targetDocument - A full class name of the target document.
- 
    discriminatorField - The database field name to store the discriminator
    value within the embedded document.
-
    discriminatorMap - Map of discriminator values to class names.
-
    strategy - The strategy to use to persist the reference. Possible values are ``set`` and ``pushAll``; ``pushAll`` is the default.

Example:

.. code-block:: php

    <?php

    /**
     * @EmbedOne(
     *     strategy="set",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "book"="Documents\BookTag",
     *         "song"="Documents\SongTag"
     *     }
     * )
     */
    private $tags = array();

Depending on the type of Document a value of ``user`` or ``author`` will be stored in a field named ``type``
and will be used to properly reconstruct the right class during hydration.

@EmbeddedDocument
-----------------

Marks the document as embeddable. Without this annotation, you
cannot embed non-document objects.

.. code-block:: php

    <?php

    class Money
    {
        /**
         * @Float
         */
        protected $amount
    
        public function __construct($amount)
        {
            $this->amount = (float) $amount;
        }
        //...
    }
    
    /**
     * @Document(db="finance", collection="wallets")
     */
    class Wallet
    {
        /**
         * @EmbedOne(targetDocument="Money")
         */
        protected $money;
    
        public function setMoney(Money $money)
        {
            $this->money = $money;
        }
        //...
    }
    //...
    $wallet = new Wallet();
    $wallet->setMoney(new Money(34.39));
    $dm->persist($wallet);
    $dm->flush();

The code above wouldn't store the money object. In order for the
above code to work, you should have:

.. code-block:: php

    <?php

    /**
     * @Document
     */
    class Money
    {
    //...
    }

or

.. code-block:: php

    <?php

    /**
     * @EmbeddedDocument
     */
    class Money
    {
    //...
    }

The difference is that @EmbeddedDocument cannot be stored without a
parent @Document and cannot specify its own db or collection
attributes.

@Field
------

Marks an annotated instance variable as "persistent". It has to be
inside the instance variables PHP DocBlock comment. Any value hold
inside this variable will be saved to and loaded from the document
store as part of the lifecycle of the instance variables
document-class.

Required attributes:

- 
   type - Name of the Doctrine ODM Type which is converted between PHP
   and Database representation. Can be one of: string, boolean, int,
   float, hash, date, key, timestamp, bin, bin\_func, bin\_uuid,
   bin\_md5, bin\_custom

Optional attributes:

- 
   name - By default the property name is used for the mongodb field
   name also, however the 'name' attribute allows you to specify the
   field name.

Examples:

.. code-block:: php

    <?php

    /**
     * @Field(type="string")
     */
    protected $username;
    
    /**
     * @Field(type="string" name="origin")
     */
    protected $country;
    
    /**
     * @Field(type="float")
     */
    protected $height;

@File
-----

Tells ODM that the property is a file, must be set to a existing
file path before saving to MongoDB Will be instantiated as instance
of
MongoGridFSFile http://www.php.net/manual/en/class.mongogridfsfile.php
class upon retrieval

@Float
------

Alias of @Field, with "type" attribute set to
"float"

.. _haslifecyclecallbacks:

@HasLifecycleCallbacks
----------------------

This annotation must be set on the document class to instruct Doctrine to check
for lifecycle callback annotations on public methods. Using `@PreFlush`_,
`@PreLoad`_, `@PostLoad`_, `@PrePersist`_, `@PostPersist`_, `@PreRemove`_,
`@PostRemove`_, `@PreUpdate`_, or `@PostUpdate`_ on methods without this
annotation will cause Doctrine to ignore the callbacks.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class User
    {
        /** @PostPersist */
        public function sendWelcomeEmail() {}
    }

@Hash
-----

Alias of @Field, with "type" attribute set to
"hash". Stores and retrieves the value as associative array.

@Id
---

The annotated instance variable will be marked as document
identifier. This annotation is a marker only and has no required or
optional attributes.

Example:

.. code-block:: php

    <?php

    /**
     * @Document
     */
    class User
    {
        /**
         * @Id
         */
        protected $id;
    }

@Increment
----------

The increment type is just like an integer field except that it will be updated
using the ``$inc`` operator instead of ``$set``:

.. code-block:: php

    <?php

    class Package
    {
        // ...

        /** @Increment */
        protected $downloads = 0;

        public function incrementDownloads()
        {
            $this->downloads++;
        }

        // ...
    }

Now update a Package instance like the following:

.. code-block:: php

    <?php

    $package->incrementDownloads();
    $dm->flush();

The query sent to Mongo would be something like the following:

::

    array(
        '$inc' => array(
            'downloads' => 1
        )
    )

The field will be incremented by the difference between the new and old values.

@Index
------

Annotation is used inside the @Document
annotation on the class level. It allows to hint the MongoDB to
generate a database index on the specified document fields.

Required attributes:

-  keys - Fields to index
-  options - Array of MongoCollection options.

Example:

.. code-block:: php

    <?php

    /**
     * @Document(
     *   db="my_database",
     *   collection="users",
     *   indexes={
     *     @Index(keys={"username"="desc"}, options={"unique"=true})
     *   }
     * )
     */
    class User
    {
        //...
    }

You can also simply specify an @Index or @UniqueIndex on a
property:

.. code-block:: php

    <?php

    /** @String @UniqueIndex(safe="true") */
    private $username;

@InheritanceType
----------------

This annotation must appear on the top-most class in an
:ref:`inheritance hierarchy <inheritance_mapping>`. ``SINGLE_COLLECTION`` and
``COLLECTION_PER_CLASS`` are currently supported.

Examples:

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("COLLECTION_PER_CLASS")
     */
    class Person
    {
        // ...
    }
    
    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField("type")
     * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
     */
    class Person
    {
        // ...
    }

@Int
----

Alias of @Field, with "type" attribute set to
"int"

@Key
----

Alias of @Field, with "type" attribute set to "key"
It is then converted to
MongoMaxKey http://www.php.net/manual/en/class.mongomaxkey.php
or
MongoMinKey http://www.php.net/manual/en/class.mongominkey.php,
if the value evaluates to true or false respectively.

@MappedSuperclass
-----------------

The annotation is used to specify classes that are parents of
document classes and should not be managed
read more at http://www.doctrine-project.org/projects/mongodb_odm/1.0/docs/reference/inheritance/en>

.. code-block:: php

    <?php

    /** @MappedSuperclass */
    class BaseDocument
    {
        // ...
    }

@NotSaved
---------

The annotation is used to specify properties that are loaded if
they exist but never saved.

.. code-block:: php

    <?php

    /** @NotSaved */
    public $field;

@PostLoad
---------

Marks a method on the document class to be called on the ``postLoad`` event. The
`@HasLifecycleCallbacks`_ annotation must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PostLoad */
        public function postLoad()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PostPersist
------------

Marks a method on the document class to be called on the ``postPersist`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PostPersist */
        public function postPersist()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PostRemove
-----------

Marks a method on the document class to be called on the ``postRemove`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PostRemove */
        public function postRemove()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PostUpdate
-----------

Marks a method on the document class to be called on the ``postUpdate`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PostUpdate */
        public function postUpdate()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PreFlush
---------

Marks a method on the document class to be called on the ``preFlush`` event. The
`@HasLifecycleCallbacks`_ annotation must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PreFlush */
        public function preFlush()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PreLoad
--------

Marks a method on the document class to be called on the ``preLoad`` event. The
`@HasLifecycleCallbacks`_ annotation must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PreLoad */
        public function preLoad(array &$data)
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PrePersist
-----------

Marks a method on the document class to be called on the ``prePersist`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PrePersist */
        public function prePersist()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PreRemove
----------

Marks a method on the document class to be called on the ``preRemove`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PreRemove */
        public function preRemove()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@PreUpdate
----------

Marks a method on the document class to be called on the ``preUpdate`` event.
The `@HasLifecycleCallbacks`_ annotation must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...
    
        /** @PreUpdate */
        public function preUpdated()
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

@ReferenceMany
--------------

Defines that the annotated instance variable holds a collection of
referenced documents.

Optional attributes:

-
    targetDocument - A full class name of the target document.
-
    simple - Create simple references and only store the referenced document's
    identifier (e.g. ``MongoId``) instead of a `DBRef`_. Note that simple
    references are not compatible with the discriminators.
-
    cascade - Cascade Option
- 
    discriminatorField - The field name to store the discriminator value within
    the `DBRef`_ object.
-
    discriminatorMap - Map of discriminator values to class names.
-
    inversedBy - The field name of the inverse side. Only allowed on owning side.
-
    mappedBy - The field name of the owning side. Only allowed on the inverse side.
-
    repositoryMethod - The name of the repository method to call to populate this reference.
-
    sort - The default sort for the query that loads the reference.
-
    criteria - Array of default criteria for the query that loads the reference.
-
    limit - Limit for the query that loads the reference.
-
    skip - Skip for the query that loads the reference.
-
    strategy - The strategy used to persist changes to the collection. Possible
    values are ``addToSet``, ``pushAll``, ``set``, and ``setArray``. ``pushAll``
    is the default. See :ref:`collection_strategies` for more information.

Example:

.. code-block:: php

    <?php

    /**
     * @ReferenceMany(
     *     strategy="set",
     *     targetDocument="Documents\Item",
     *     cascade="all",
     *     sort={"sort_field": "asc"}
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "book"="Documents\BookItem",
     *         "song"="Documents\SongItem"
     *     }
     * )
     */
    private $cart;

@ReferenceOne
-------------

Defines an instance variable holds a related document instance.

Optional attributes:

-
    targetDocument - A full class name of the target document.
-
    simple - Create simple references and only store the referenced document's
    identifier (e.g. ``MongoId``) instead of a `DBRef`_. Note that simple
    references are not compatible with the discriminators.
-
    cascade - Cascade Option
- 
    discriminatorField - The field name to store the discriminator value within
    the `DBRef`_ object.
-
    discriminatorMap - Map of discriminator values to class names.
-
    inversedBy - The field name of the inverse side. Only allowed on owning side.
-
    mappedBy - The field name of the owning side. Only allowed on the inverse side.
-
    repositoryMethod - The name of the repository method to call to populate this reference.
-
    sort - The default sort for the query that loads the reference.
-
    criteria - Array of default criteria for the query that loads the reference.
-
    limit - Limit for the query that loads the reference.
-
    skip - Skip for the query that loads the reference.

Example:

.. code-block:: php

    <?php

    /**
     * @ReferenceOne(
     *     targetDocument="Documents\Item",
     *     cascade="all",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "book"="Documents\BookItem",
     *         "song"="Documents\SongItem"
     *     }
     * )
     */
    private $cart;

@String
-------

Defines that the annotated instance variable holds a string.

.. code-block:: php

    <?php

    /** @String */
    private $username;

@Timestamp
----------

Defines that the annotated instance variable holds a timestamp.

.. code-block:: php

    <?php

    /** @Timestamp */
    private $created;

@UniqueIndex
------------

Defines a unique index on the given document.

.. code-block:: php

    <?php

    /** @String @UniqueIndex */
    private $email;

.. _`DBRef`: http://docs.mongodb.org/manual/reference/database-references/#dbref
