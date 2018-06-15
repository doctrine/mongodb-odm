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

    /** @Field(type="string") @AlsoLoad("name") */
    public $fullName;

The ``$fullName`` property will be loaded from ``fullName`` if it exists, but
fall back to ``name`` if it does not exist. If multiple fall back fields are
specified, ODM will consider them in order until the first is found.

Additionally, `@AlsoLoad`_ may annotate a method with one or more field names.
Before normal hydration, the field(s) will be considered in order and the method
will be invoked with the first value found as its single argument.

.. code-block:: php

    <?php

    /** @AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        list($this->firstName, $this->lastName) = explode(' ', $name);
    }

For additional information on using `@AlsoLoad`_, see
:doc:`Migrations <migrating-schemas>`.

@ChangeTrackingPolicy
---------------------

This annotation is used to change the change tracking policy for a document:

.. code-block:: php

    <?php

    /**
     * @Document
     * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
     */
    class Person
    {
        // ...
    }

For a list of available policies, read the section on :ref:`change tracking policies <change_tracking_policies>`.

@DefaultDiscriminatorValue
--------------------------

This annotation can be used when using `@DiscriminatorField`_. It will be used
as a fallback value if a document has no discriminator field set. This must
correspond to a value from the configured discriminator map.

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField("type")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     * @DefaultDiscriminatorValue("person")
     */
    class Person
    {
        // ...
    }

@DiscriminatorField
-------------------

This annotation is required for the top-most class in a
:ref:`single collection inheritance <single_collection_inheritance>` hierarchy.
It takes a string as its only argument, which specifies the database field to
store a class name or key (if a discriminator map is used). ODM uses this field
during hydration to select the instantiation class.

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
the discriminator field instead of the |FQCN|.

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

@Document
---------

Required annotation to mark a PHP class as a document, whose peristence will be
managed by ODM.

Optional attributes:

-
   ``db`` - By default, the document manager will use the MongoDB database
   defined in the configuration, but this option may be used to override the
   database for a particular document class.
-
   ``collection`` - By default, the collection name is derived from the
   document's class name, but this option may be used to override that behavior.
-
   ``repositoryClass`` - Specifies a custom repository class to use.
-
   ``indexes`` - Specifies an array of indexes for this document.
-
   ``readOnly`` - Prevents document from being updated: it can only be inserted,
   upserted or removed.
-
   ``writeConcern`` - Specifies the write concern for this document that
   overwrites the default write concern specified in the configuration. It does
   not overwrite a write concern given as :ref:`option <flush_options>` to the
  ``flush``  method when committing your documents.

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
     *     readOnly=true,
     * )
     */
    class User
    {
        //...
    }

@EmbedMany
----------

This annotation is similar to `@EmbedOne`_, but instead of embedding one
document, it embeds a collection of documents.

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document.
-
    ``discriminatorField`` - The database field name to store the discriminator
    value within the embedded document.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for discriminatorField if no
    value has been set in the embedded document.
-
    ``strategy`` - The strategy used to persist changes to the collection.
    Possible values are ``addToSet``, ``pushAll``, ``set``, and ``setArray``.
    ``pushAll`` is the default. See :ref:`storage_strategies` for more
    information.
-
    ``collectionClass`` - A |FQCN| of class that implements ``Collection``
    interface and is used to hold documents. Doctrine's ``ArrayCollection`` is
    used by default.

.. code-block:: php

    <?php

    /**
     * @EmbedMany(
     *     strategy="set",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "book"="Documents\BookTag",
     *         "song"="Documents\SongTag"
     *     },
     *     defaultDiscriminatorValue="book"
     * )
     */
    private $tags = array();

Depending on the embedded document's class, a value of ``user`` or ``author``
will be stored in the ``type`` field and used to reconstruct the proper class
during hydration. The ``type`` field need not be mapped on the embedded
document classes.

@EmbedOne
---------

The `@EmbedOne`_ annotation works similarly to `@ReferenceOne`_, except that
that document will be embedded within the parent document. Consider the
following excerpt from the MongoDB documentation:

    The key question in MongoDB schema design is "does this object merit its own
    collection, or rather should it be embedded within objects in other
    collections?" In relational databases, each sub-item of interest typically
    becomes a separate table (unless you are denormalizing for performance). In
    MongoDB, this is not recommended – embedding objects is much more efficient.
    Data is then collocated on disk; client-server turnarounds to the database
    are eliminated. So in general, the question to ask is, "why would I not want
    to embed this object?"

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document.
-
    ``discriminatorField`` - The database field name to store the discriminator
    value within the embedded document.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for discriminatorField if no
    value has been set in the embedded document.

.. code-block:: php

    <?php

    /**
     * @EmbedOne(
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "user"="Documents\User",
     *         "author"="Documents\Author"
     *     },
     *     defaultDiscriminatorValue="user"
     * )
     */
    private $creator;

Depending on the embedded document's class, a value of ``user`` or ``author``
will be stored in the ``type`` field and used to reconstruct the proper class
during hydration. The ``type`` field need not be mapped on the embedded
document classes.

@EmbeddedDocument
-----------------

Marks the document as embeddable. This annotation is required for any documents
to be stored within an `@EmbedOne`_, `@EmbedMany`_ or `@File\\Metadata`_
relationship.

.. code-block:: php

    <?php

    /** @EmbeddedDocument */
    class Money
    {
        /** @Field(type="float") */
        private $amount;

        public function __construct($amount)
        {
            $this->amount = (float) $amount;
        }
        //...
    }

    /** @Document(db="finance", collection="wallets") */
    class Wallet
    {
        /** @EmbedOne(targetDocument=Money::class) */
        private $money;

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

Unlike normal documents, embedded documents cannot specify their own database or
collection. That said, a single embedded document class may be used with
multiple document classes, and even other embedded documents!

Optional attributes:

-
   ``indexes`` - Specifies an array of indexes for this embedded document, to be
   included in the schemas of any embedding documents.

@Field
------

Marks an annotated instance variable for persistence. Values for this field will
be saved to and loaded from the document store as part of the document class'
lifecycle.

Optional attributes:

-
   ``type`` - Name of the ODM type, which will determine the value's
   representation in PHP and BSON (i.e. MongoDB). See
   :ref:`doctrine_mapping_types` for a list of types. Defaults to "string".
-
   ``name`` - By default, the property name is used for the field name in
   MongoDB; however, this option may be used to specify a database field name.
-
   ``nullable`` - By default, ODM will ``$unset`` fields in MongoDB if the PHP
   value is null. Specify true for this option to force ODM to store a null
   value in the database instead of unsetting the field.

Examples:

.. code-block:: php

    <?php

    /**
     * @Field(type="string")
     */
    protected $username;

    /**
     * @Field(type="string", name="co")
     */
    protected $country;

    /**
     * @Field(type="float")
     */
    protected $height;

.. _file:

@File
-----

This marks the document as a GridFS file. GridFS allow storing larger amounts of
data than regular documents.

Optional attributes:

-
   ``db`` - By default, the document manager will use the MongoDB database
   defined in the configuration, but this option may be used to override the
   database for a particular file.
-
   ``bucketName`` - By default, files are stored in a bucket called ``fs``. You
   can customize that bucket name with this property.
-
   ``repositoryClass`` - Specifies a custom repository class to use. The class
   must extend the ``Doctrine\ODM\MongoDB\Repository\GridFSRepository``
   interface.
-
   ``indexes`` - Specifies an array of indexes for this document.
-
   ``readOnly`` - Prevents the file from being updated: it can only be inserted,
   upserted or removed.
-
   ``writeConcern`` - Specifies the write concern for this file that overwrites
   the default write concern specified in the configuration.

.. _file_chunksize:

@File\ChunkSize
---------------

This maps the ``chunkSize`` property of a GridFS file to a property. It contains
the size of a single file chunk in bytes. No other options can be set.

.. _file_filename:

@File\Filename
--------------

This maps the ``filename`` property of a GridFS file to a property. No other
options can be set.

.. _file_length:

@File\Length
------------

This maps the ``length`` property of a GridFS file to a property. It contains
the size of the entire file in bytes. No other options can be set.

.. _file_metadata:

@File\Metadata
--------------

This maps the ``metadata`` property of a GridFS file to a property. Metadata can
be used to store additional properties in a file. The metadata document must be
an embedded document mapped using `@EmbeddedDocument`_.

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document.
-
    ``discriminatorField`` - The database field name to store the discriminator
    value within the embedded document.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for ``discriminatorField``
    if no value has been set in the embedded document.

@File\UploadDate
----------------

This maps the ``uploadDate`` property of a GridFS file to a property. No other
options can be set.

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

@Id
---

The annotated instance variable will be marked as the document identifier. The
default behavior is to store an `MongoDB\BSON\ObjectId`_ instance, but you may
customize this via the :ref:`strategy <basic_mapping_identifiers>` attribute.

.. code-block:: php

    <?php

    /** @Document */
    class User
    {
        /** @Id */
        protected $id;
    }

@Index
------

This annotation is used inside of the class-level `@Document`_ or
`@EmbeddedDocument`_ annotations to specify indexes to be created on the
collection (or embedding document's collection in the case of
`@EmbeddedDocument`_). It may also be used at the property-level to define
single-field indexes.

Optional attributes:

-
    ``keys`` - Mapping of indexed fields to their ordering or index type. ODM
    will allow ``asc`` and ``desc`` to be used in place of ``1`` and ``-1``,
    respectively. Special index types (e.g. ``2dsphere``) should be specified as
    strings. This is required when `@Index`_ is used at the class level.
-
    ``options`` - Options for creating the index

The ``keys`` and ``options`` attributes correspond to the arguments for
`MongoCollection::createIndex() <http://php.net/manual/en/mongocollection.createindex.php>`_.
ODM allows mapped field names (i.e. PHP property names) to be used when defining
``keys``.

.. code-block:: php

    <?php

    /**
     * @Document(
     *   indexes={
     *     @Index(keys={"username"="desc"}, options={"unique"=true})
     *   }
     * )
     */
    class User
    {
        //...
    }

If you are creating a single-field index, you can simply specify an `@Index`_ or
`@UniqueIndex`_ on a mapped property:

.. code-block:: php

    <?php

    /** @Field(type="string") @UniqueIndex */
    private $username;

@Indexes
--------

This annotation may be used at the class level to specify an array of `@Index`_
annotations. It is functionally equivalent to using the ``indexes`` option for
the `@Document`_ or `@EmbeddedDocument`_ annotations.

.. code-block:: php

    <?php

    /**
     * @Document
     * @Indexes({
     *   @Index(keys={"username"="desc"}, options={"unique"=true})
     * })
     */
    class User
    {
        //...
    }

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

@Lock
-----

The annotated instance variable will be used to store lock information for :ref:`pessimistic locking <transactions_and_concurrency_pessimistic_locking>`.
This is only compatible with the ``int`` type, and cannot be combined with `@Id`_.

.. code-block:: php

    <?php

    /** @Field(type="int") @Lock */
    private $lock;

@MappedSuperclass
-----------------

The annotation is used to specify classes that are parents of document classes
and should not be managed directly. See
:ref:`inheritance mapping <inheritance_mapping>` for additional information.

.. code-block:: php

    <?php

    /** @MappedSuperclass */
    class BaseDocument
    {
        // ...
    }

@NotSaved
---------

The annotation is used to specify properties that are loaded if they exist in
MongoDB; however, ODM will not save the property value back to the database.

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

    use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;

    /** @Document @HasLifecycleCallbacks */
    class Article
    {
        // ...

        /** @PreLoad */
        public function preLoad(PreLoadEventArgs $eventArgs)
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

@ReadPreference
---------------

Specifies `Read Preference <https://docs.mongodb.com/manual/core/read-preference/>_`
that will be applied when querying for the annotated document.

.. code-block:: php

    <?php

    namespace Documents;

    /**
     * @Document
     * @ODM\ReadPreference("primaryPreferred", tags={
     *   { "dc"="east" },
     *   { "dc"="west" },
     *   {  }
     * })
     */
    class User
    {
    }

.. _annotations_reference_reference_many:

@ReferenceMany
--------------

Defines that the annotated instance variable holds a collection of referenced
documents.

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document. A ``targetDocument``
    is required when using ``storeAs: id``.
-
    ``storeAs`` - Indicates how to store the reference. ``id`` stores the
    identifier, ``ref`` an embedded object containing the ``id`` field and
    (optionally) a discriminator. ``dbRef`` and ``dbRefWithDb`` store a `DBRef`_
    object and are deprecated in favor of ``ref``. Note that ``id`` references
    are not compatible with the discriminators.
-
    ``cascade`` - Cascade Option
-
    ``discriminatorField`` - The field name to store the discriminator value within
    the reference object.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for ``discriminatorField``
    if no value has been set in the referenced document.
-
    ``inversedBy`` - The field name of the inverse side. Only allowed on owning side.
-
    ``mappedBy`` - The field name of the owning side. Only allowed on the
    inverse side.
-
    ``repositoryMethod`` - The name of the repository method to call to populate
    this reference.
-
    ``sort`` - The default sort for the query that loads the reference.
-
    ``criteria`` - Array of default criteria for the query that loads the
    reference.
-
    ``limit`` - Limit for the query that loads the reference.
-
    ``skip`` - Skip for the query that loads the reference.
-
    ``strategy`` - The strategy used to persist changes to the collection.
    Possible values are ``addToSet``, ``pushAll``, ``set``, and ``setArray``.
    ``pushAll`` is the default. See :ref:`storage_strategies` for more
    information.
-
    ``collectionClass`` - A |FQCN| of class that implements ``Collection``
    interface and is used to hold documents. Doctrine's ``ArrayCollection`` is
    used by default
-
    ``prime`` - A list of references contained in the target document that will
    be initialized when the collection is loaded. Only allowed for inverse
    references.

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
     *     },
     *     defaultDiscriminatorValue="book"
     * )
     */
    private $cart;

.. _annotations_reference_reference_one:

@ReferenceOne
-------------

Defines an instance variable holds a related document instance.

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document. A ``targetDocument``
    is required when using ``storeAs: id``.
-
    ``storeAs`` - Indicates how to store the reference. ``id`` stores the
    identifier, ``ref`` an embedded object containing the ``id`` field and
    (optionally) a discriminator. ``dbRef`` and ``dbRefWithDb`` store a `DBRef`_
    object and are deprecated in favor of ``ref``. Note that ``id`` references
    are not compatible with the discriminators.
-
    ``cascade`` - Cascade Option
-
    ``discriminatorField`` - The field name to store the discriminator value
    within the reference object.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for ``discriminatorField``
    if no value has been set in the referenced document.
-
    ``inversedBy`` - The field name of the inverse side. Only allowed on owning
    side.
-
    ``mappedBy`` - The field name of the owning side. Only allowed on the
    inverse side.
-
    ``repositoryMethod`` - The name of the repository method to call to populate
    this reference.
-
    ``sort`` - The default sort for the query that loads the reference.
-
    ``criteria`` - Array of default criteria for the query that loads the
    reference.
-
    ``limit`` - Limit for the query that loads the reference.
-
    ``skip`` - Skip for the query that loads the reference.

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
     *     },
     *     defaultDiscriminatorValue="book"
     * )
     */
    private $cart;

@ShardKey
---------

This annotation may be used at the class level to specify a shard key to be used
for sharding the document collection.

.. code-block:: php

    <?php

    /**
     * @Document
     * @ShardKey(keys={"username"="asc"})
     */
    class User
    {
        //...
    }

@UniqueIndex
------------

Alias of `@Index`_, with the ``unique`` option set by default.

.. code-block:: php

    <?php

    /** @Field(type="string") @UniqueIndex */
    private $email;

.. _annotations_reference_version:

@Version
--------

The annotated instance variable will be used to store version information for :ref:`optimistic locking <transactions_and_concurrency_optimistic_locking>`.
This is only compatible with ``int`` and ``date`` field types, and cannot be combined with `@Id`_.

.. code-block:: php

    <?php

    /** @Field(type="int") @Version */
    private $version;

By default, Doctrine ODM updates :ref:`embed-many <embed_many>` and
:ref:`reference-many <reference_many>` collections in separate write operations,
which do not bump the document version. Users employing document versioning are
encouraged to use the :ref:`atomicSet <atomic_set>` or
:ref:`atomicSetArray <atomic_set_array>` strategies for such collections, which
will ensure that collections are updated in the same write operation as the
versioned parent document.

.. _BSON specification: http://bsonspec.org/spec.html
.. _DBRef: https://docs.mongodb.com/manual/reference/database-references/#dbrefs
.. _geoNear command: https://docs.mongodb.com/manual/reference/command/geoNear/
.. _MongoDB\BSON\ObjectId: https://secure.php.net/manual/en/class.mongodb-bson-objectid.php
.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
