Basic Mapping
=============

This chapter explains the basic mapping of objects and properties.
Mapping of references and embedded documents will be covered in the
next chapter "Reference Mapping".

Mapping Drivers
---------------

Doctrine provides several different ways for specifying object
document mapping metadata:

-  Docblock Annotations
-  XML
-  YAML
-  Raw PHP Code

.. note::

    If you're wondering which mapping driver gives the best
    performance, the answer is: None. Once the metadata of a class has
    been read from the source (annotations, xml or yaml) it is stored
    in an instance of the
    ``Doctrine\ODM\MongoDB\Mapping\ClassMetadata`` class and these
    instances are stored in the metadata cache. Therefore at the end of
    the day all drivers perform equally well. If you're not using a
    metadata cache (not recommended!) then the XML driver might have a
    slight edge in performance due to the powerful native XML support
    in PHP.

Introduction to Docblock Annotations
------------------------------------

You've probably used docblock annotations in some form already,
most likely to provide documentation metadata for a tool like
``PHPDocumentor`` (@author, @link, ...). Docblock annotations are a
tool to embed metadata inside the documentation section which can
then be processed by some tool. Doctrine generalizes the concept of
docblock annotations so that they can be used for any kind of
metadata and so that it is easy to define new docblock annotations.
In order to allow more involved annotation values and to reduce the
chances of clashes with other docblock annotations, the Doctrine
docblock annotations feature an alternative syntax that is heavily
inspired by the Annotation syntax introduced in Java 5.

The implementation of these enhanced docblock annotations is
located in the ``Doctrine\Common\Annotations`` namespace and
therefore part of the Common package. Doctrine docblock annotations
support namespaces and nested annotations among other things. The
Doctrine MongoDB ODM defines its own set of docblock annotations
for supplying object document mapping metadata.

.. note::

    If you're not comfortable with the concept of docblock
    annotations, don't worry, as mentioned earlier Doctrine 2 provides
    XML and YAML alternatives and you could easily implement your own
    favorite mechanism for defining ORM metadata.

Persistent classes
------------------

In order to mark a class for object-relational persistence it needs
to be designated as a document. This can be done through the
``@Document`` marker annotation.

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
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

    .. code-block:: yaml

        Documents\User:
          type: document

By default, the document will be persisted to a database named
doctrine and a collection with the same name as the class name. In
order to change that, you can use the ``db`` and ``collection``
option as follows:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document(db="my_db", collection="users") */
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

    .. code-block:: yaml

        Documents\User:
          type: document
          db: my_db
          collection: users

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
an MongoDB type. You can even write your own custom mapping types.

Here is a quick overview of the built-in mapping types:

-  ``bin``
-  ``bin_bytearray``
-  ``bin_custom``
-  ``bin_func``
-  ``bin_md5``
-  ``bin_uuid``
-  ``boolean``
-  ``collection``
-  ``custom_id``
-  ``date``
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

You can read more about the available MongoDB types on `php.net <http://us.php.net/manual/en/mongo.types.php>`_.

.. note::

    The Doctrine mapping types are used to convert the local PHP types to the MongoDB types
    when persisting so that your domain is not bound to MongoDB-specific types. For example a
    DateTime instance may be converted to MongoDate when you persist your documents, and vice
    versa during hydration.

Generally, the name of each built-in mapping type hints as to how the value will be converted.
This list explains some of the less obvious mapping types:

-  ``bin``: string to MongoBinData instance with a "generic" type (default)
-  ``bin_bytearray``: string to MongoBinData instance with a "byte array" type
-  ``bin_custom``: string to MongoBinData instance with a "custom" type
-  ``bin_func``: string to MongoBinData instance with a "function" type
-  ``bin_md5``: string to MongoBinData instance with a "md5" type
-  ``bin_uuid``: string to MongoBinData instance with a "uuid" type
-  ``collection``: numerically indexed array to MongoDB array
-  ``date``: DateTime to MongoDate
-  ``hash``: associative array to MongoDB object
-  ``id``: string to MongoId by default, but other formats are possible
-  ``timestamp``: string to MongoTimestamp
-  ``raw``: any type

.. note::
    
    If you are using the hash type, values within the associative array are 
    passed to MongoDB directly, without being prepared. Only formats suitable for
    the Mongo driver should be used. If your hash contains values which are not 
    suitable you should either use an embedded document or use formats provided
    by the MongoDB driver (e.g. ``\MongoDate`` instead of ``\DateTime``).

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
that serves as the identifier with the ``@Id`` marker annotation.
Here is an example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
        class User
        {
            /** @Id */
            private $id;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <field fieldName="id" id="true" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\User:
          fields:
            id:
              type: id
              id: true

You can configure custom ID strategies if you don't want to use the default MongoId.
The available strategies are:

- ``AUTO`` - Uses the native generated MongoId.
- ``ALNUM`` - Generates an alpha-numeric string (based on an incrementing value).
- ``CUSTOM`` - Defers generation to a AbstractIdGenerator implementation specified in the ``class`` option.
- ``INCREMENT`` - Uses another collection to auto increment an integer identifier.
- ``UUID`` - Generates a UUID identifier.
- ``NONE`` - Do not generate any identifier. ID must be manually set.

Here is an example how to manually set a string identifier for your documents:

.. configuration-block::

    .. code-block:: php

        <?php

        /** Document */
        class MyPersistentClass
        {
            /** @Id(strategy="NONE", type="string") */
            private $id;
    
            public function setId($id)
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
                <field name="id" id="true" strategy="NONE" type="string" />
            </document>
        </doctrine-mongo-mapping>
    
    .. code-block:: yaml

        MyPersistentClass:
          fields:
            id:
              type: string
              id: true
              strategy: NONE

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

    $document = $dm->find('MyPersistentClass', 'my_unique_identifier');

You can define your own ID generator by extending the
``Doctrine\ODM\MongoDB\Id\AbstractIdGenerator`` class and specifying the class
as an option for the ``CUSTOM`` strategy:

.. configuration-block::

    .. code-block:: php

        <?php

        /** Document */
        class MyPersistentClass
        {
            /** @Id(strategy="CUSTOM", type="string", options={"class"="Vendor\Specific\Generator"}) */
            private $id;

            public function setId($id)
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
                <field name="id" id="true" strategy="CUSTOM" type="string">
                    <id-generator-option name="class" value="Vendor\Specific\Generator" />
                </field>
            </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          fields:
            id:
              id: true
              strategy: CUSTOM
              type: string
              options:
                class: Vendor\Specific\Generator



Fields
~~~~~~

To mark a property for document persistence the ``@Field`` docblock
annotation can be used. This annotation usually requires at least 1
attribute to be set, the ``type``. The ``type`` attribute specifies
the Doctrine Mapping Type to use for the field. If the type is not
specified, 'string' is used as the default mapping type since it is
the most flexible.

Example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
        class User
        {
            // ...

            /** @Field(type="string") */
            private $username;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <field fieldName="id" id="true" />
                <field fieldName="username" type="string" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\User:
          fields:
            id:
              type: id
              id: true
            username:
              type: string

In that example we mapped the property ``id`` to the field ``id``
using the mapping type ``id`` and the property ``name`` is mapped
to the field ``name`` with the default mapping type ``string``. As
you can see, by default the mongo field names are assumed to be the
same as the property names. To specify a different name for the
field, you can use the ``name`` attribute of the Field annotation
as follows:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Field(name="db_name") */
        private $name;

    .. code-block:: xml

        <field fieldName="name" name="db_name" />

    -- code-block:: yaml

        name:
          name: db_name

Custom Mapping Types
--------------------

Doctrine allows you to create new mapping types. This can come in
handy when you're missing a specific mapping type or when you want
to replace the existing implementation of a mapping type.

In order to create a new mapping type you need to subclass
``Doctrine\ODM\MongoDB\Mapping\Types\Type`` and implement/override
the methods. Here is an example skeleton of such a custom type
class:

.. code-block:: php

    <?php

    namespace My\Project\Types;

    use Doctrine\ODM\MongoDB\Mapping\Types\Type;

    /**
     * My custom datatype.
     */
    class MyType extends Type
    {
        public function convertToPHPValue($value)
        {
            // Note: this function is only called when your custom type is used
            // as an identifier. For other cases, closureToPHP() will be called.
            return new \DateTime('@' . $value->sec);
        }

        public function closureToPHP()
        {
            // Return the string body of a PHP closure that will receive $value
            // and store the result of a conversion in a $return variable
            return '$return = new \DateTime($value);';
        }

        public function convertToDatabaseValue($value)
        {
            // This is called to convert a PHP value to its Mongo equivalent
            return new \MongoDate($value);
        }
    }

Restrictions to keep in mind:

- 
   If the value of the field is *NULL* the method
   ``convertToDatabaseValue()`` is not called.
- 
   The ``UnitOfWork`` never passes values to the database convert
   method that did not change in the request.

When you have implemented the type you still need to let Doctrine
know about it. This can be achieved through the
``Doctrine\ODM\MongoDB\Mapping\Types#registerType($name, $class)``
method.

Here is an example:

.. code-block:: php

    <?php

    // in bootstrapping code
    
    // ...
    
    use Doctrine\ODM\MongoDB\Types\Type;
    
    // ...
    
    // Register my type
    Type::addType('mytype', 'My\Project\Types\MyType');

As can be seen above, when registering the custom types in the
configuration you specify a unique name for the mapping type and
map that to the corresponding |FQCN|. Now you can use your new
type in your mapping like this:

.. configuration-block::

    .. code-block:: php

        <?php

        class MyPersistentClass
        {
            /** @Field(type="mytype") */
            private $field;
        }

    .. code-block:: xml

        <field fieldName="field" type="mytype" />

    .. code-block:: yaml

        field:
          type: mytype

Multiple Document Types in a Collection
---------------------------------------

You can easily store multiple types of documents in a single collection. This
requires specifying the same collection name, ``discriminatorField``, and
(optionally) ``discriminatorMap`` mapping options for each class that will share
the collection. Here is an example:

.. code-block:: php

    <?php

    /**
     * @Document(collection="my_documents")
     * @DiscriminatorField("type")
     * @DiscriminatorMap({"article"="Article", "album"="Album"})
     */
    class Article
    {
        // ...
    }
    
    /**
     * @Document(collection="my_documents")
     * @DiscriminatorField("type")
     * @DiscriminatorMap({"article"="Article", "album"="Album"})
     */
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

    $query = $dm->createQuery(array('Article', 'Album'));
    $documents = $query->execute();

The above will return a cursor that will allow you to iterate over all
``Article`` and ``Album`` documents in the collections.

.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
