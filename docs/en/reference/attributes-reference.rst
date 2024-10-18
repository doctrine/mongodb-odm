Attributes Reference
=====================

In this chapter a reference of every Doctrine ODM Attribute is
given with short explanations on their context and usage.

#[AlsoLoad]
-----------

Specify one or more MongoDB fields to use for loading data if the original field
does not exist.

.. code-block:: php

    <?php

    class User
    {
        #[Field(type: 'string')]
        #[AlsoLoad('name')]
        public string $fullName;
    }

The ``$fullName`` property will be loaded from ``fullName`` if it exists, but
fall back to ``name`` if it does not exist. If multiple fall back fields are
specified, ODM will consider them in order until the first is found.

Additionally, `#[AlsoLoad]`_ may annotate a method with one or more field names.
Before normal hydration, the field(s) will be considered in order and the method
will be invoked with the first value found as its single argument.

.. code-block:: php

    <?php

    class User
    {
        #[AlsoLoad(['name', 'fullName'])]
        public function populateFirstAndLastName(string $name): void
        {
            list($this->firstName, $this->lastName) = explode(' ', $name);
        }
    }

For additional information on using `#[AlsoLoad]`_, see
:doc:`Migrations <migrating-schemas>`.

#[ChangeTrackingPolicy]
-----------------------

This attribute is used to change the change tracking policy for a document:

.. code-block:: php

    <?php

    #[Document]
    #[ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
    class Person
    {
        // ...
    }

For a list of available policies, read the section on :ref:`change tracking policies <change_tracking_policies>`.

#[DefaultDiscriminatorValue]
----------------------------

This attribute can be used when using `#[DiscriminatorField]`_. It will be used
as a fallback value if a document has no discriminator field set. This must
correspond to a value from the configured discriminator map.

.. code-block:: php

    <?php

    #[Document]
    #[InheritanceType('SINGLE_COLLECTION')]
    #[DiscriminatorField('type')]
    #[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
    #[DefaultDiscriminatorValue('person')]
    class Person
    {
        // ...
    }

#[DiscriminatorField]
---------------------

This attribute is required for the top-most class in a
:ref:`single collection inheritance <single_collection_inheritance>` hierarchy.
It takes a string as its only argument, which specifies the database field to
store a class name or key (if a discriminator map is used). ODM uses this field
during hydration to select the instantiation class.

.. code-block:: php

    <?php

    #[Document]
    #[InheritanceType("SINGLE_COLLECTION")]
    #[DiscriminatorField("type")]
    class SuperUser
    {
        // ...
    }

.. note::

    For backwards compatibility, the discriminator field may also be specified
    via either the ``name`` or ``fieldName`` attribute.

#[DiscriminatorMap]
-------------------

This attribute is required for the top-most class in a
:ref:`single collection inheritance <single_collection_inheritance>` hierarchy.
It takes an array as its only argument, which maps keys to class names. The
class names must be fully qualified. Using the ``::class constant`` is supported. When
a document is persisted to the database, its class name key will be stored in
the discriminator field instead of the |FQCN|. If the discriminator map is non-empty
and it does not contain the class name of the persisted document, a
``\Doctrine\ODM\MongoDB\Mapping\MappingException`` will be thrown.

.. code-block:: php

    <?php

    #[Document]
    #[InheritanceType('SINGLE_COLLECTION')]
    #[DiscriminatorField('type')]
    #[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
    class Person
    {
        // ...
    }

#[Document]
-----------

Required attribute to mark a PHP class as a document, whose persistence will be
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
   ``readOnly`` - Prevents document from being updated: it can only be inserted,
   upserted or removed.
-
   ``writeConcern`` - Specifies the write concern for this document that
   overwrites the default write concern specified in the configuration. It does
   not overwrite a write concern given as :ref:`option <flush_options>` to the
   ``flush``  method when committing your documents.

.. code-block:: php

    <?php

    #[Document(
        db: 'documents',
        collection: 'users',
        repositoryClass: MyProject\UserRepository::class,
        indexes: [
            new Index(keys: ['username' => 'desc'], options: ['unique' => true])
        ],
        readOnly: true,
    )]
    class User
    {
        //...
    }

#[EmbedMany]
------------

This attribute is similar to `#[EmbedOne]`_, but instead of embedding one
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
    interface and is used to hold documents. When typed properties
    are used it is inherited from PHP type, otherwise Doctrine's ``ArrayCollection`` is
    used by default.
-
    ``notSaved`` - The property is loaded if it exists in the database; however,
    ODM will not save the property value back to the database.

.. code-block:: php

    <?php

    class User
    {
        /** @var Collection<BookTag|SongTag> */
        #[EmbedMany(
            strategy:'set',
            discriminatorField:'type',
            discriminatorMap: [
                'book' => Documents\BookTag::class,
                'song' => Documents\SongTag::class,
            ],
            defaultDiscriminatorValue: 'book',
        )]
        private Collection $tags;
    }

Depending on the embedded document's class, a value of ``user`` or ``author``
will be stored in the ``type`` field and used to reconstruct the proper class
during hydration. The ``type`` field need not be mapped on the embedded
document classes.

#[EmbedOne]
-----------

The `#[EmbedOne]`_ attribute works similarly to `#[ReferenceOne]`_, except that
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
    ``targetDocument`` - A |FQCN| of the target document. When typed properties
    are used it is inherited from PHP type.
-
    ``discriminatorField`` - The database field name to store the discriminator
    value within the embedded document.
-
    ``discriminatorMap`` - Map of discriminator values to class names.
-
    ``defaultDiscriminatorValue`` - A default value for discriminatorField if no
    value has been set in the embedded document.
-
    ``notSaved`` - The property is loaded if it exists in the database; however,
    ODM will not save the property value back to the database.

.. code-block:: php

    <?php

    class Thing
    {
        #[EmbedOne(
             discriminatorField: 'type',
             discriminatorMap: [
                 'user' => Documents\User::class,
                 'author' => Documents\Author::class,
             ],
             defaultDiscriminatorValue: 'user',
        )]
        private User|Author $creator;
    }

Depending on the embedded document's class, a value of ``user`` or ``author``
will be stored in the ``type`` field and used to reconstruct the proper class
during hydration. The ``type`` field need not be mapped on the embedded
document classes.

#[EmbeddedDocument]
-------------------

Marks the document as embeddable. This attribute is required for any documents
to be stored within an `#[EmbedOne]`_, `#[EmbedMany]`_ or `#[File\\Metadata]`_
relationship.

.. code-block:: php

    <?php

    #[EmbeddedDocument]
    class Money
    {
        #[Field(type: 'float')]
        private float $amount;

        public function __construct(float $amount)
        {
            $this->amount = $amount;
        }
        //...
    }

    #[Document(db: 'finance', collection: 'wallets')]
    class Wallet
    {
        #[EmbedOne(targetDocument: Money::class)]
        private Money $money;

        public function setMoney(Money $money): void
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

#[Field]
--------

Marks an annotated instance variable for persistence. Values for this field will
be saved to and loaded from the document store as part of the document class'
lifecycle.

Optional attributes:

-
   ``type`` - Name of the ODM type, which will determine the value's
   representation in PHP and BSON (i.e. MongoDB). See
   :ref:`doctrine_mapping_types` for a list of types. Defaults to "string" or
   :ref:`Type from PHP property type <reference-php-mapping-types>`.
-
   ``enumType`` - A |FQCN| of an ``enum``. ODM will automatically handle conversion
   from the backing value stored in the database to an ``enum``. Can be auto-detected
   by :ref:`type from PHP property type <reference-php-mapping-types>`.
-
   ``name`` - By default, the property name is used for the field name in
   MongoDB; however, this option may be used to specify a database field name.
-
   ``nullable`` - By default, ODM will ``$unset`` fields in MongoDB if the PHP
   value is null. Specify true for this option to force ODM to store a null
   value in the database instead of unsetting the field.
-
   ``notSaved`` - The property is loaded if it exists in the database; however,
   ODM will not save the property value back to the database.

Examples:

.. code-block:: php

    <?php

    #[Document]
    class User
    {
        #[Field(type: 'string')]
        protected string $username;

        #[Field(type: 'string', name: 'co')]
        protected string $country;

        #[Field(type: 'float')]
        protected float $height;
    }

.. _file:

#[File]
-------

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
   ``readOnly`` - Prevents the file from being updated: it can only be inserted,
   upserted or removed.
-
   ``writeConcern`` - Specifies the write concern for this file that overwrites
   the default write concern specified in the configuration.

.. _file_chunksize:

#[File\ChunkSize]
-----------------

This maps the ``chunkSize`` property of a GridFS file to a property. It contains
the size of a single file chunk in bytes. No other options can be set.

.. _file_filename:

#[File\Filename]
----------------

This maps the ``filename`` property of a GridFS file to a property. No other
options can be set.

.. _file_length:

#[File\Length]
--------------

This maps the ``length`` property of a GridFS file to a property. It contains
the size of the entire file in bytes. No other options can be set.

.. _file_metadata:

#[File\Metadata]
----------------

This maps the ``metadata`` property of a GridFS file to a property. Metadata can
be used to store additional properties in a file. The metadata document must be
an embedded document mapped using `#[EmbeddedDocument]`_.

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

#[File\UploadDate]
------------------

This maps the ``uploadDate`` property of a GridFS file to a property. No other
options can be set.

.. _haslifecyclecallbacks:

#[HasLifecycleCallbacks]
------------------------

This attribute must be set on the document class to instruct Doctrine to check
for lifecycle callback attributes on public methods. Using `#[PreFlush]`_,
`#[PreLoad]`_, `#[PostLoad]`_, `#[PrePersist]`_, `#[PostPersist]`_, `#[PreRemove]`_,
`#[PostRemove]`_, `#[PreUpdate]`_, or `#[PostUpdate]`_ on methods without this
attribute will cause Doctrine to ignore the callbacks.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class User
    {
        #[PostPersist]
        public function sendWelcomeEmail(): void {}
    }

#[Id]
-----

The annotated instance variable will be marked as the document identifier. The
default behavior is to store an `MongoDB\BSON\ObjectId`_ instance, but you may
customize this via the :ref:`strategy <basic_mapping_identifiers>` attribute.

.. code-block:: php

    <?php

    #[Document]
    class User
    {
        #[Id]
        protected string $id;
    }

.. note::

   The property annotated with `#[Id]`_ cannot be ``readonly`` even if the
   value is set only once and should never be updated. This is because the
   property is set outside of the scope of the class, which is not allowed in
   PHP for ``readonly`` properties.

#[Index]
--------

This attribute is used  to specify indexes to be created on the
collection (or embedding document's collection in the case of
`#[EmbeddedDocument]`_). It may also be used at the property-level to define
single-field indexes.

Optional attributes:

-
    ``keys`` - Mapping of indexed fields to their ordering or index type. ODM
    will allow ``asc`` and ``desc`` to be used in place of ``1`` and ``-1``,
    respectively. Special index types (e.g. ``2dsphere``) should be specified as
    strings. This is required when `#[Index]`_ is used at the class level.
-
    ``options`` - Options for creating the index. Options are documented in the
    :ref:`indexes chapter <indexes>`.

The ``keys`` and ``options`` attributes correspond to the arguments for
`MongoDB\Collection::createIndex() <https://docs.mongodb.com/php-library/current/reference/method/MongoDBCollection-createIndex/>`_.
ODM allows mapped field names (i.e. PHP property names) to be used when defining
``keys``.

.. code-block:: php

    <?php

    #[Document]
    #[Index(keys: ['username' => 'desc' ], options: ['unique' => true])]
    class User
    {
        //...
    }

If you are creating a single-field index, you can simply specify an `#[Index]`_ or
`#[UniqueIndex]`_ on a mapped property:

.. code-block:: php

    <?php

    #[Document]
    class User
    {
        #[Field(type: 'string')]
        #[UniqueIndex]
        private string $username;
    }

.. note::

    If the ``name`` option is specified on an index in an embedded document, it
    will be prefixed with the embedded field path before creating the index.
    This is necessary to avoid index name conflict when the same document is
    embedded multiple times in a single collection. Prefixing of the index name
    can cause errors due to excessive index name length. In this case, try
    shortening the index name or embedded field path.

#[Indexes]
----------

.. note::
    The ``#[Indexes]`` attribute was deprecated in 2.2 and will be removed in 3.0.
    Please move all nested ``new Index`` instances to a class level attributes.

This attribute may be used at the class level to specify an array of `#[Index]`_
attributes. It is functionally equivalent to specifying multiple ``#[Index]``
attributes on a class level.

.. code-block:: php

    <?php

    #[Document]
    #[Indexes([
        new Index(keys: ['username' => 'desc'], options: ['unique' => true]),
    ])]
    class User
    {
        //...
    }

#[InheritanceType]
------------------

This attribute must appear on the top-most class in an
:ref:`inheritance hierarchy <inheritance_mapping>`. ``SINGLE_COLLECTION`` and
``COLLECTION_PER_CLASS`` are currently supported.

Examples:

.. code-block:: php

    <?php

    #[Document]
    #[InheritanceType('COLLECTION_PER_CLASS')]
    class Person
    {
        // ...
    }

    #[Document]
    #[InheritanceType('SINGLE_COLLECTION')]
    #[DiscriminatorField('type')]
    #[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
    class Person
    {
        // ...
    }

#[Lock]
-------

The annotated instance variable will be used to store lock information for :ref:`pessimistic locking <transactions_and_concurrency_pessimistic_locking>`.
This is only compatible with the ``int`` type, and cannot be combined with `#[Id]`_.

.. code-block:: php

    <?php

    #[Document]
    class Thing
    {
        #[Field(type: 'int')]
        #[Lock]
        private int $lock;
    }

#[MappedSuperclass]
-------------------

The attribute is used to specify classes that are parents of document classes
and should not be managed directly. See
:ref:`inheritance mapping <inheritance_mapping>` for additional information.

.. code-block:: php

    <?php

    #[MappedSuperclass]
    class BaseDocument
    {
        // ...
    }

#[PostLoad]
-----------

Marks a method on the document class to be called on the ``postLoad`` event. The
`#[HasLifecycleCallbacks]`_ attribute must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PostLoad]
        public function postLoad(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PostPersist]
--------------

Marks a method on the document class to be called on the ``postPersist`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PostPersist]
        public function postPersist(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PostRemove]
-------------

Marks a method on the document class to be called on the ``postRemove`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PostRemove]
        public function postRemove(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PostUpdate]
-------------

Marks a method on the document class to be called on the ``postUpdate`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PostUpdate]
        public function postUpdate(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PreFlush]
-----------

Marks a method on the document class to be called on the ``preFlush`` event. The
`#[HasLifecycleCallbacks]`_ attribute must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PreFlush]
        public function preFlush(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PreLoad]
----------

Marks a method on the document class to be called on the ``preLoad`` event. The
`#[HasLifecycleCallbacks]`_ attribute must be present on the same class for the
method to be registered.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PreLoad]
        public function preLoad(PreLoadEventArgs $eventArgs): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PrePersist]
-------------

Marks a method on the document class to be called on the ``prePersist`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PrePersist]
        public function prePersist(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PreRemove]
------------

Marks a method on the document class to be called on the ``preRemove`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PreRemove]
        public function preRemove(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[PreUpdate]
------------

Marks a method on the document class to be called on the ``preUpdate`` event.
The `#[HasLifecycleCallbacks]`_ attribute must be present on the same class for
the method to be registered.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Article
    {
        // ...

        #[PreUpdate]
        public function preUpdated(): void
        {
            // ...
        }
    }

See :ref:`lifecycle_events` for more information.

#[ReadPreference]
-----------------

Specifies `Read Preference <https://docs.mongodb.com/manual/core/read-preference/>_`
that will be applied when querying for the annotated document.

.. code-block:: php

    <?php

    namespace Documents;

    #[Document]
    #[ODM\ReadPreference('primaryPreferred', tags: [
        [ 'dc' => 'east' ],
        [ 'dc' => 'west' ],
        []
    ])]
    class User
    {
    }

.. _attributes_reference_reference_many:

#[ReferenceMany]
----------------

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
    interface and is used to hold documents. When typed properties
    are used it is inherited from PHP type, otherwise Doctrine's ``ArrayCollection`` is
    used by default
-
    ``prime`` - A list of references contained in the target document that will
    be initialized when the collection is loaded. Only allowed for inverse
    references.
-
    ``notSaved`` - The property is loaded if it exists in the database; however,
    ODM will not save the property value back to the database.

.. code-block:: php

    <?php

    class User
    {
        /** @var Collection<Item> */
        #[ReferenceMany(
            strategy: 'set',
            targetDocument: Item::class,
            cascade: 'all',
            sort: ['sort_field' => 'asc'],
            discriminatorField: 'type',
            discriminatorMap: [
                'book' => BookItem::class,
                'song' => SongItem::class,
            ],
            defaultDiscriminatorValue: 'book',
        )]
        private Collection $cart;
    }

.. _attributes_reference_reference_one:

#[ReferenceOne]
---------------

Defines an instance variable holds a related document instance.

Optional attributes:

-
    ``targetDocument`` - A |FQCN| of the target document. A ``targetDocument``
    is required when using ``storeAs: id``. When typed properties are used
    it is inherited from PHP type.
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
-
    ``notSaved`` - The property is loaded if it exists in the database; however,
    ODM will not save the property value back to the database.

.. code-block:: php

    <?php

    class User
    {
        #[ReferenceOne(
            targetDocument: Documents\Item::class,
            cascade: 'all',
            discriminatorField: 'type',
            discriminatorMap: [
                'book' => Documents\BookItem::class,
                'song' => Documents\SongItem::class,
            ],
            defaultDiscriminatorValue: 'book'
        )]
        private Item $cart;
    }

#[SearchIndex]
--------------

This attribute is used to specify :ref:`search indexes <search_indexes>` for
`MongoDB Atlas Search <https://www.mongodb.com/docs/atlas/atlas-search/>`__.

The attributes correspond to arguments for
`MongoDB\Collection::createSearchIndex() <https://www.mongodb.com/docs/php-library/current/reference/method/MongoDBCollection-createSearchIndex/>`__.
Excluding ``name``, attributes are used to create the
`search index definition <https://www.mongodb.com/docs/manual/reference/command/createSearchIndexes/#search-index-definition-syntax>`__.

Optional attributes:

-
    ``name`` - Name of the search index to create, which must be unique to the
    collection. Defaults to ``"default"``.
-
    ``dynamic`` - Enables or disables dynamic field mapping for this index.
    If ``true``, the index will include all fields with
    `supported data types <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#std-label-bson-data-chart>`__.
    If ``false``, the ``fields`` attribute must be specified. Defaults to ``false``.
-
    ``fields`` - Associative array of `field mappings <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/>`__
    that specify the fields to index (keys). Required only if dynamic mapping is disabled.
-
    ``analyzer`` - Specifies the `analyzer <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/>`__
    to apply to string fields when indexing. Defaults to the
    `standard analyzer <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/standard/>`__.
-
    ``searchAnalyzer`` - Specifies the `analyzer <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/>`__
    to apply to query text before the text is searched. Defaults to the
    ``analyzer`` attribute, or the `standard analyzer <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/standard/>`__.
    if both are unspecified.
-
    ``analyzers`` - Array of `custom analyzers <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/custom/>`__
    to use in this index.
-
    ``storedSource`` - Specifies document fields to store for queries performed
    using the `returnedStoredSource <https://www.mongodb.com/docs/atlas/atlas-search/return-stored-source/>`__
    option. Specify ``true`` to store all fields, ``false`` to store no fields,
    or a `document <https://www.mongodb.com/docs/atlas/atlas-search/stored-source-definition/#std-label-fts-stored-source-document>`__
    to specify individual fields to include or exclude from storage. Defaults to ``false``.
-
    ``synonyms`` - Array of `synonym mapping definitions <https://www.mongodb.com/docs/atlas/atlas-search/synonyms/>`__
    to use in this index.

.. note::

    Search indexes have some notable differences from `#[Index]`_. They may only
    be defined on document classes. Definitions will not be incorporated from
    embedded documents. Additionally, ODM will **NOT** translate field names in
    search index definitions. Database field names must be used instead of
    mapped field names (i.e. PHP property names).

#[ShardKey]
-----------

This attribute may be used at the class level to specify a shard key to be used
for sharding the document collection.

.. code-block:: php

    <?php

    #[Document]
    #[ShardKey(keys: ['username' => 'asc'])]
    class User
    {
        //...
    }

#[TimeSeries]
-------------

This attribute may be used at the class level to mark a collection as containing
:doc:`time-series data <../cookbook/time-series-data>`.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;

    #[Document]
    #[TimeSeries(timeField: 'time', metaField: 'metadata', granularity: Granularity::Seconds)]
    class Measurements
    {
        #[Id]
        public string $id;

        #[Field]
        public DateTimeImmutable $time;

        #[EmbedOne(targetDocument: MeasurementMetadata)]
        public MeasurementMetadata $metadata;

        #[Field]
        public int $measurement;
    }

The ``timeField`` attribute is required and denotes the field where the time of
a time series entry is stored. The following optional attributes may be set:

-
    ``metaField`` -  The name of the field which contains metadata in each time
    series document. The field can be of any data type.

-
    ``granularity`` - Set the granularity to the value that most closely matches
    the time between consecutive incoming timestamps. This allows MongoDB to
    optimize how data is stored. Note: this attribute cannot be combined with
    ``bucketMaxSpanSeconds`` and ``bucketRoundingSeconds``.

-
    ``bucketMaxSpanSeconds`` - Used with ``bucketRoundingSeconds`` as an
    alternative to ``granularity``. Sets the maximum time between timestamps
    in the same bucket. Possible values are 1 - 31356000.

-
    ``bucketRoundingSeconds`` - Used with ``bucketMaxSpanSeconds``, must be set
    to the same value as ``bucketMaxSpanSeconds``. When a document requires a
    new bucket, MongoDB rounds down the document's timestamp value by this
    interval to set the minimum time for the bucket.

-
    ``expireAfterSeconds`` - Enables the automatic deletion of documents in a
    time series collection by specifying the number of seconds after which
    documents expire. MongoDB deletes these expired documents automatically.

#[UniqueIndex]
--------------

Alias of `#[Index]`_, with the ``unique`` option set by default.

.. code-block:: php

    <?php

    class User
    {
        #[Field(type: 'string')]
        #[UniqueIndex]
        private string $email;
    }

.. _attributes_reference_version:

#[Validation]
-------------

This attribute may be used at the class level to specify the validation schema
for the related collection.

-
   ``validator`` - Specifies a schema that will be used by
   MongoDB to validate data inserted or updated in the collection.
   Please refer to the following
   `MongoDB documentation (Schema Validation ¶) <https://docs.mongodb.com/manual/core/schema-validation/>`_
   for more details. The value should be a string representing a BSON document under the
   `Extended JSON specification <https://github.com/mongodb/specifications/blob/master/source/extended-json.rst>`_.
   The recommended way to fill up this property is to create a class constant
   (eg. ``::VALIDATOR``) using the
   `HEREDOC/NOWDOC syntax <https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.nowdoc>`_
   for clarity and to reference it as the attribute value.
-
   ``action`` - Determines how MongoDB handles documents that violate
   the validation rules. Please refer to the related
   `MongoDB documentation (Accept or Reject Invalid Documents ¶) <https://docs.mongodb.com/manual/core/schema-validation/#accept-or-reject-invalid-documents>`_
   for more details. The allowed values are the following:

       - ``error``
       - ``warn``

   If it is not defined then the default behavior (``error``) will be used.
   Those values are also declared as constants for convenience:

      - ``\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR``
      - ``\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN``

   Import the ``ClassMetadata`` namespace to use those constants in your attribute.
-
   ``level`` - Determines which operations MongoDB applies the
   validation rules. Please refer to the related
   `MongoDB documentation (Existing Documents ¶) <https://docs.mongodb.com/manual/core/schema-validation/#existing-documents>`_
   for more details. The allowed values are the following:

      - ``off``
      - ``strict``
      - ``moderate``

   If it is not defined then the default behavior (``strict``) will be used.
   Those values are also declared as constants for convenience:

      - ``\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF``
      - ``\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT``
      - ``\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE``

   Import the ``ClassMetadata`` namespace to use those constants in your attribute.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
    // ... other imports

    #[Document]
    #[Validation(
        validator: SchemaValidated::VALIDATOR,
        action: ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
        level: ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
    )]
    class SchemaValidated
    {
        private const VALIDATOR = <<<'EOT'
            {
                "$jsonSchema": {
                    "required": ["name"],
                    "properties": {
                        "name": {
                            "bsonType": "string",
                            "description": "must be a string and is required"
                        }
                    }
                },
                "$or": [
                    { "phone": { "$type": "string" } },
                    { "email": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } },
                    { "status": { "$in": [ "Unknown", "Incomplete" ] } }
                ]
            }
            EOT;

        // rest of the class code...
    }

#[Version]
----------

The annotated instance variable will be used to store version information for :ref:`optimistic locking <transactions_and_concurrency_optimistic_locking>`.
This is only compatible with types implementing the ``\Doctrine\ODM\MongoDB\Types\Versionable`` interface and cannot be
combined with `#[Id]`_. Following ODM types can be used for versioning: ``int``, ``decimal128``, ``date``, and
``date_immutable``.

.. code-block:: php

    <?php

    class Thing
    {
        #[Field(type: 'int')]
        #[Version]
        private int $version;
    }

By default, Doctrine ODM updates :ref:`embed-many <embed_many>` and
:ref:`reference-many <reference_many>` collections in separate write operations,
which do not bump the document version. Users employing document versioning are
encouraged to use the :ref:`atomicSet <atomic_set>` or
:ref:`atomicSetArray <atomic_set_array>` strategies for such collections, which
will ensure that collections are updated in the same write operation as the
versioned parent document.

#[View]
-------

Required attribute to mark a PHP class as a view. Views are created from
aggregation pipelines, which are returned from a special repository method.
Views can be used like collections for any read operations. Result documents are
not managed and cannot be referenced using the :ref:`reference-many <reference_many>`
and :ref:`reference-one <reference_one>` mappings.

Required attributes:

-
   ``rootClass`` - this is the base collection that the view is created from
-
   ``repositoryClass`` - a repository class is required. This repository must
   implement the ``MongoDB\ODM\MongoDB\Repository\ViewRepository`` interface.

Optional attributes:

-
   ``db`` - By default, the document manager will use the MongoDB database
   defined in the configuration, but this option may be used to override the
   database for a particular document class.
-
   ``view`` - By default, the view name is derived from the document's class
   name, but this option may be used to override that behavior.

.. code-block:: php

    <?php

    #[View(
        db: 'documents',
        rootClass: User::class,
        repositoryClass: UserNameRepository::class,
    )]
    class UserName
    {
        //...
    }

    class UserNameRepository implements \Doctrine\ODM\MongoDB\Repository\ViewRepository
    {
        public function createViewAggregation(Builder $builder) : void
        {
            $builder->project()
                ->includeFields(['username']);
        }
    }

The ``createViewAggregation`` method can add any aggregation pipeline stage,
except for the ``$out`` and ``$merge`` stages. The pipeline is created for the
root class specified in the view mapping.

.. note::

    Views must be created before they can be queried. This can be done using the
    ``odm:schema:create`` command.

.. _BSON specification: http://bsonspec.org/spec.html
.. _DBRef: https://docs.mongodb.com/manual/reference/database-references/#dbrefs
.. _geoNear command: https://docs.mongodb.com/manual/reference/command/geoNear/
.. _MongoDB\BSON\ObjectId: https://www.php.net/manual/en/class.mongodb-bson-objectid.php
.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
