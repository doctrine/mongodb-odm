Basic Mapping
=============

This chapter explains the basic mapping of objects and properties.
Mapping of references and embedded documents will be covered in the
next chapter "Reference Mapping".

Mapping Drivers
---------------

Doctrine provides several different ways for specifying object
document mapping metadata:

-  `Attributes <annotations-reference>`_
-  `XML <xml-mapping>`_
-  Raw PHP Code

.. note::

    If you're wondering which mapping driver gives the best performance, the
    answer is: None. Once the metadata of a class has been read from the source
    (Attributes or XML) it is stored in an instance of the
    ``Doctrine\ODM\MongoDB\Mapping\ClassMetadata`` class and these instances are
    stored in the metadata cache. Therefore all drivers perform equally well at
    runtime.

Introduction to Attributes
--------------------------

`PHP attributes <https://www.php.net/language.attributes.overview>`_
are a PHP 8+ feature that provides a native way to add metadata to classes,
methods, properties, and other language constructs. They replace doctrine
annotations by offering a standardized approach to metadata, eliminating
the need for the separate parsing library required by annotations.

In this documentation we follow the `PER Coding Style <https://www.php-fig.org/per/coding-style/#12-attributes>`_
for attributes. We use named arguments for attributes as they argument names
or attribute classes constructors are covered by Doctrine Backward-Compatibility promise.

.. note::

    Doctrine Annotations are deprecated. You can migrate to PHP Attributes
    automatically `using Rector <https://getrector.com/blog/how-to-upgrade-annotations-to-attributes>`_.

Persistent classes
------------------

In order to mark a class for object-relational persistence it needs
to be designated as a document. This can be done through the
``#[Document]`` marker attribute.

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;

        #[Document]
        class User
        {
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
            <document name="Documents\User">
            </document>
        </doctrine-mongo-mapping>

By default, the document will be persisted to a database named
doctrine and a collection with the same name as the class name. In
order to change that, you can use the ``db`` and ``collection``
option as follows:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;

        #[Document(db: 'my_db', collection: 'users')]
        class User
        {
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
            <document name="Documents\User" db="my_db" collection="users">
            </document>
        </doctrine-mongo-mapping>

Now instances of ``Documents\User`` will be persisted into a
collection named ``users`` in the database ``my_db``.

If you want to omit the db attribute you can configure the default db
to use with the ``setDefaultDB`` method:

.. code-block:: php

    <?php

    $config->setDefaultDB('my_db');

.. _doctrine_mapping_types:

Doctrine Mapping Types
----------------------

A Doctrine Mapping Type defines the mapping between a PHP type and
a MongoDB type. You can even :doc:`write your own custom mapping types <custom-mapping-types>`.

Here is a quick overview of the built-in mapping types:

-  ``bin``
-  ``bin_bytearray``
-  ``bin_custom``
-  ``bin_func``
-  ``bin_md5``
-  ``bin_uuid``
-  ``bool``
-  ``collection``
-  ``custom_id``
-  ``date``
-  ``date_immutable``
-  ``decimal128``
-  ``file``
-  ``float``
-  ``hash``
-  ``id``
-  ``int``
-  ``key``
-  ``object_id``
-  ``raw``
-  ``string``
-  ``timestamp``

You can read more about the available MongoDB types on `php.net <https://www.php.net/mongodb.bson>`_.

.. note::

    The Doctrine mapping types are used to convert the local PHP types to the MongoDB types
    when persisting so that your domain is not bound to MongoDB-specific types. For example a
    DateTime instance may be converted to ``MongoDB\BSON\UTCDateTime`` when you persist your
    documents, and vice versa during hydration.

Generally, the name of each built-in mapping type hints as to how the value will be converted.
This list explains some of the less obvious mapping types:

-  ``bin``: string to MongoDB\BSON\Binary instance with a "generic" type (default)
-  ``bin_bytearray``: string to MongoDB\BSON\Binary instance with a "byte array" type
-  ``bin_custom``: string to MongoDB\BSON\Binary instance with a "custom" type
-  ``bin_func``: string to MongoDB\BSON\Binary instance with a "function" type
-  ``bin_md5``: string to MongoDB\BSON\Binary instance with a "md5" type
-  ``bin_uuid``: string to MongoDB\BSON\Binary instance with a "uuid" type
-  ``collection``: numerically indexed array to MongoDB array
-  ``date``: DateTime to ``MongoDB\BSON\UTCDateTime``
-  ``date_immutable``: DateTimeImmutable to ``MongoDB\BSON\UTCDateTime``
-  ``decimal128``: string to ``MongoDB\BSON\Decimal128``, requires ``ext-bcmath``
-  ``hash``: associative array to MongoDB object
-  ``id``: string to ObjectId by default, but other formats are possible
-  ``timestamp``: string to ``MongoDB\BSON\Timestamp``
-  ``raw``: any type

.. note::

    If you are using the hash type, values within the associative array are
    passed to MongoDB directly, without being prepared. Only formats suitable for
    the Mongo driver should be used. If your hash contains values which are not
    suitable you should either use an embedded document or use formats provided
    by the MongoDB driver (e.g. ``\MongoDB\BSON\UTCDateTime`` instead of ``\DateTime``).

.. _reference-php-mapping-types:

PHP Types Mapping
_________________

.. note::
    Doctrine will skip type autoconfiguration if settings are provided explicitly.

Since version 2.4 Doctrine can determine usable defaults from property types
on document classes. Doctrine will map PHP types to ``type`` attribute as
follows:

- ``DateTime``: ``date``
- ``DateTimeImmutable``: ``date_immutable``
- ``array``: ``hash``
- ``bool``: ``bool``
- ``float``: ``float``
- ``int``: ``int``
- ``string``: ``string``

Doctrine can also autoconfigure any backed ``enum`` it encounters: ``type``
will be set to ``string`` or ``int``, depending on the enum's backing type,
and ``enumType`` to the enum's |FQCN|.

.. note::
    Nullable type does not imply ``nullable`` mapping option. You need to manually
    set ``nullable=true`` to have ``null`` values saved to the database.

Additionally Doctrine can determine ``collectionClass`` for ``ReferenceMany`` and
``EmbedMany`` collections from property's type.

Property Mapping
----------------

After a class has been marked as a document it can specify
mappings for its instance fields. Here we will only look at simple
fields that hold scalar values like strings, numbers, etc.
References to other objects and embedded objects are covered in the
chapter "Reference Mapping".

.. _basic_mapping_identifiers:

Identifiers
~~~~~~~~~~~

Every document class needs an identifier. You designate the field
that serves as the identifier with the ``#[Id]`` marker attribute.
Here is an example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
        use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

        #[Document]
        class User
        {
            #[Id]
            public string $id;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
            <id />
          </document>
        </doctrine-mongo-mapping>

You can configure custom ID strategies if you don't want to use the default
object ID. The available strategies are:

- ``AUTO`` - Uses the native generated ObjectId.
- ``ALNUM`` - Generates an alpha-numeric string (based on an incrementing value).
- ``CUSTOM`` - Defers generation to an implementation of ``IdGenerator`` specified in the ``class`` option.
- ``INCREMENT`` - Uses another collection to auto increment an integer identifier.
- ``UUID`` - Generates a UUID identifier.
- ``NONE`` - Do not generate any identifier. ID must be manually set.

Here is an example how to manually set a string identifier for your documents:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
        use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

        #[Document]
        class MyPersistentClass
        {
            #[Id(strategy: 'NONE', type: 'string')]
            public string $id;

            //...
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                                                    http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="MyPersistentClass">
                <id strategy="NONE" type="string" />
            </document>
        </doctrine-mongo-mapping>

When using the ``NONE`` strategy you will have to explicitly set an id before persisting the document:

.. code-block:: php

    <?php

    //...

    $document = new MyPersistentClass();
    $document->setId('my_unique_identifier');
    $dm->persist($document);
    $dm->flush();

Now you can retrieve the document later:

.. code-block:: php

    <?php

    //...

    $document = $dm->find(MyPersistentClass::class, 'my_unique_identifier');

You can define your own ID generator by implementing the
``Doctrine\ODM\MongoDB\Id\IdGenerator`` interface:

.. code-block:: php

    <?php

    namespace Vendor\Specific;

    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Id\IdGenerator;

    class Generator implements IdGenerator
    {
        public function generate(DocumentManager $dm, object $document)
        {
            // Your own logic here
            return 'my_generated_id';
        }
    }

Then specify the ``class`` option for the ``CUSTOM`` strategy:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class MyPersistentClass
        {
            #[Id(strategy: 'CUSTOM', type: 'string', options: ['class' => \Vendor\Specific\Generator::class])]
            private string $id;

            public function setId(string $id): void
            {
                $this->id = $id;
            }

            //...
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                                                    http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="MyPersistentClass">
                <id strategy="CUSTOM" type="string">
                    <generator-option name="class" value="Vendor\Specific\Generator" />
                </id>
            </document>
        </doctrine-mongo-mapping>

Fields
~~~~~~

To mark a property for document persistence the ``#[Field]`` docblock
attribute can be used. This attribute usually requires at least 1
attribute to be set, the ``type``. The ``type`` attribute specifies
the Doctrine Mapping Type to use for the field. If the type is not
specified, 'string' is used as the default mapping type since it is
the most flexible.

Example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
        use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;

        #[Document]
        class User
        {
            // ...

            #[Field(type: 'string')]
            public string $username;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <id />
                <field field-name="username" type="string" />
          </document>
        </doctrine-mongo-mapping>

In that example we mapped the property ``id`` to the field ``id``
using the mapping type ``id`` and the property ``name`` is mapped
to the field ``name`` with the default mapping type ``string``. As
you can see, by default the mongo field names are assumed to be the
same as the property names. To specify a different name for the
field, you can use the ``name`` attribute of the Field attribute
as follows:

.. configuration-block::

    .. code-block:: php

        <?php

        class User
        {
            #[Field(name: 'db_name')]
            public string $name;
        }

    .. code-block:: xml

        <field field-name="name" name="db_name" />

Multiple Document Types in a Collection
---------------------------------------

You can easily store multiple types of documents in a single collection. This
requires specifying the same collection name, ``discriminatorField``, and
(optionally) ``discriminatorMap`` mapping options for each class that will share
the collection. Here is an example:

.. code-block:: php

    <?php

    #[Document(collection: 'my_documents')]
    #[DiscriminatorField('type')]
    #[DiscriminatorMap(['article' => Article::class, 'album' => Album::class])]
    class Article
    {
        // ...
    }

    #[Document(collection: 'my_documents')]
    #[DiscriminatorField('type')]
    #[DiscriminatorMap(['article' => Article::class, 'album' => Album::class])]
    class Album
    {
        // ...
    }

All instances of ``Article`` and ``Album`` will be stored in the
``my_documents`` collection. You can query for the documents of a particular
class just like you normally would and the results will automatically be limited
based on the discriminator value for that class.

If you wish to query for multiple types of documents from the collection, you
may pass an array of document class names when creating a query builder:

.. code-block:: php

    <?php

    $query = $dm->createQuery([Article::class, Album::class]);
    $documents = $query->execute();

The above will return a cursor that will allow you to iterate over all
``Article`` and ``Album`` documents in the collections.

.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
