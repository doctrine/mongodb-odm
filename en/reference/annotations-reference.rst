Annotations Reference
=====================

In this chapter a reference of every Doctrine 2 ODM Annotation is
given with short explanations on their context and usage.

@AlsoLoad
---------

Specify an additional mongodb field to check for and load data from it if it exists.

.. code-block:: php

    <?php

    /** @Field @AlsoLoad("oldFieldName")*/
    private $fieldName;

The above `$fieldName` will be loaded from `fieldName` if it exists and will fallback to `oldFieldName`
if it does not exist.

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

Optional attributes:

-
    strategy - The strategy to use to persist the data. Possible values are `set` and `pushAll` and `pushAll` is the default.

Example:

.. code-block:: php

    <?php

    /** @Collection(strategy="pushAll") */
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

This annotation is a required annotation for the topmost/super
class of an inheritance hierarchy. It specifies the details of the
field which saves the name of the class, which the document is
actually instantiated as.

Required attributes:

- 
   fieldName - The field name of the discriminator. This name is also
   used during Array hydration as key to specify the class-name.

Example:

.. code-block:: php

    <?php

    /**
     * @Document
     * @DiscriminatorField(fieldName="type")
     */
    class SuperUser
    {
        // ...
    }

@DiscriminatorMap
-----------------

The discriminator map is a required annotation on the top-most/super
class in an inheritance hierarchy. It takes an array as only
argument which defines which class should be saved under which name
in the database. Keys are the database value and values are the
classes, either as fully- or as unqualified class names depending
if the classes are in the namespace or not.

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField(fieldName="discr")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        /**
         * @Field(type="string")
         */
        private $discr;
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

Required attributes:

-  targetDocument - A full class name of the target document.

Optional attributes:

- 
    discriminatorField - The field name to store the discriminator value in.
-
    discriminatorMap - Map of discriminator values to class names.
-
    strategy - The strategy to use to persist the reference. Possible values are `set` and `pushAll` and `pushAll` is the default.

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

Depending on the type of Document a value of `user` or `author` will be stored in a field named `type`
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

Required attributes:

-  targetDocument - A full class name of the target document.

Optional attributes:

- 
    discriminatorField - The field name to store the discriminator value in.
-
    discriminatorMap - Map of discriminator values to class names.
-
    strategy - The strategy to use to persist the reference. Possible values are `set` and `pushAll` and `pushAll` is the default.

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

Depending on the type of Document a value of `user` or `author` will be stored in a field named `type`
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
     * @Column(type="float")
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

The increment type is just like a normal field except that when you
update, it will use the $inc operator instead of $set:

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

It will increment the value by the difference between the new value
and the old value.

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

In an inheritance hierarchy you have to use this annotation on the
topmost/super class to define which strategy should be used for
inheritance. Currently SINGLE\_COLLECTION and
COLLECTION\_PER\_CLASS are supported.

This annotation has always been used in conjunction with the
@DiscriminatorMap and
@DiscriminatorField annotations.

Examples:

.. code-block:: php

    <?php

    /**
     * @Document
     * @InheritanceType("COLLECTION_PER_CLASS")
     * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
     */
    class Person
    {
        // ...
    }
    
    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
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

Marks a method on the document to be called as a @PostLoad event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PostLoad */
        public function postLoad()
        {
            // ...
        }
    }

@PostPersist
------------

Marks a method on the document to be called as a @PostPersist
event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PostPersist */
        public function postPersist()
        {
            // ...
        }
    }

@PostRemove
-----------

Marks a method on the document to be called as a @PostRemove event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PostRemove */
        public function postRemove()
        {
            // ...
        }
    }

@PostUpdate
-----------

Marks a method on the document to be called as a @PostUpdate event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PostUpdate */
        public function postUpdate()
        {
            // ...
        }
    }

@PreLoad
--------

Marks a method on the document to be called as a @PreLoad event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PreLoad */
        public function preLoad(array &$data)
        {
            // ...
        }
    }

@PrePersist
-----------

Marks a method on the document to be called as a @PrePersist event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PrePersist */
        public function prePersist()
        {
            // ...
        }
    }

@PreRemove
----------

Marks a method on the document to be called as a @PreRemove event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PreRemove */
        public function preRemove()
        {
            // ...
        }
    }

@PreUpdate
----------

Marks a method on the document to be called as a @PreUpdate event.

.. code-block:: php

    <?php

    /** @Document */
    class Article
    {
        // ...
    
        /** @PreUpdate */
        public function preUpdated()
        {
            // ...
        }
    }

@ReferenceMany
--------------

Defines that the annotated instance variable holds a collection of
referenced documents.

Required attributes:

-  targetDocument - A full class name of the target document.

Optional attributes:

-
    simple - Create simple references and only store a `MongoId` instead of a `DBRef`.
-
    cascade - Cascade Option
- 
    discriminatorField - The field name to store the discriminator value in.
-
    discriminatorMap - Map of discriminator values to class names.
-
    inversedBy - The field name of the inverse side. Only allowed on owning side.
-
    mappedBy - The field name of the owning side. Only allowed on the inverse side.
-
    repositoryMethod - The name of the repository method to call to to populate this reference.
-
    sort - The default sort for the query that loads the reference.
-
    criteria - Array of default criteria for the query that loads the reference.
-
    limit - Limit for the query that loads the reference.
-
    skip - Skip for the query that loads the reference.
-
    strategy - The strategy to use to persist the reference. Possible values are `set` and `pushAll` and `pushAll` is the default.

Example:

.. code-block:: php

    <?php

    /**
     * @ReferenceMany(
     *     strategy="set",
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

@ReferenceOne
-------------

Defines an instance variable holds a related document instance.

Required attributes:

-  targetDocument - A full class name of the target document.

Optional attributes:

-
    simple - Create simple references and only store a `MongoId` instead of a `DBRef`.
-
    cascade - Cascade Option
- 
    discriminatorField - The field name to store the discriminator value in.
-
    discriminatorMap - Map of discriminator values to class names.
-
    inversedBy - The field name of the inverse side. Only allowed on owning side.
-
    mappedBy - The field name of the owning side. Only allowed on the inverse side.
-
    repositoryMethod - The name of the repository method to call to to populate this reference.
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

