Reference Mapping
=================

This chapter explains how references between documents are mapped with Doctrine.

Collections
-----------

Examples of many-valued references in this manual make use of a ``Collection``
interface and a corresponding ``ArrayCollection`` implementation, which are
defined in the ``Doctrine\Common\Collections`` namespace. These classes have no
dependencies on ODM, and can therefore be used within your domain model and
elsewhere without introducing coupling to the persistence layer.

ODM also provides a ``PersistentCollection`` implementation of ``Collection``,
which incorporates change-tracking functionality; however, this class is
constructed internally during hydration. As a developer, you should develop with
the ``Collection`` interface in mind so that your code can operate with any
implementation.

Why are these classes used over PHP arrays? Native arrays cannot be
transparently extended in PHP, which is necessary for many advanced features
provided by the ODM. Although PHP does provide various interfaces that allow
objects to operate like arrays (e.g. ``Traversable``, ``Countable``,
``ArrayAccess``), and even a concrete implementation in ``ArrayObject``, these
objects cannot always be used everywhere that a native array is accepted.
Doctrine's ``Collection`` interface and ``ArrayCollection`` implementation are
conceptually very similar to ``ArrayObject``, with some slight differences and
improvements.

.. _reference_one:

Reference One
-------------

Reference one document:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class Product
        {
            // ...

            /**
             * @ReferenceOne(targetDocument="Shipping")
             */
            private $shipping;

            // ...
        }

        /** @Document */
        class Shipping
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Product">
                <reference-one field="shipping" target-document="Documents\Shipping" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        User:
          type: document
          referenceOne:
            shipping:
              targetDocument: Documents\Shipping

.. _reference_many:

Reference Many
--------------

Reference many documents:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ...

            /**
             * @ReferenceMany(targetDocument="Account")
             */
            private $accounts = array();

            // ...
        }

        /** @Document */
        class Account
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Product">
                <reference-many field="accounts" target-document="Documents\Account" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        User:
          type: document
          referenceMany:
            accounts:
              targetDocument: Documents\Account

Mixing Document Types
---------------------

If you want to store different types of documents in references, you can simply
omit the ``targetDocument`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /** @ReferenceMany */
            private $favorites = array();

            // ...
        }

    .. code-block:: xml

        <field fieldName="favorites" />

    .. code-block:: yaml

        referenceMany:
            favorites: ~

Now the ``$favorites`` property can store a reference to any type of document!
The class name will be automatically stored in a field named
``_doctrine_class_name`` within the `DBRef`_ object. The field name can be
customized with the ``discriminatorField`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /**
             * @ReferenceMany(discriminatorField="type")
             */
            private $favorites = array();

            // ...
        }

    .. code-block:: xml

        <reference-many fieldName="favorites">
            <discriminator-field name="type" />
        </reference-many>

    .. code-block:: yaml

        referenceMany:
          favorites:
            discriminatorField: type

You can also specify a discriminator map to avoid storing the fully qualified
class name with each reference:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /**
             * @ReferenceMany(
             *   discriminatorMap={
             *     "album"="Album",
             *     "song"="Song"
             *   }
             * )
             */
            private $favorites = array();

            // ...
        }

    .. code-block:: xml

        <reference-many fieldName="favorites">
            <discriminator-map>
                <discriminator-mapping value="album" class="Documents\Album" />
                <discriminator-mapping value="song" class="Documents\Song" />
            </discriminator-map>
        </reference-many>

    .. code-block:: yaml

        referenceMany:
          favorites:
            discriminatorMap:
              album: Documents\Album
              song: Documents\Song

.. _simple_references:

Simple References
-----------------

By default all references are stored as a `DBRef`_ object with the traditional
``$ref``, ``$id``, and ``$db`` fields (in that order). For references to
documents of a single collection, storing the collection and database names for
each reference may be redundant. You can use simple references to store the
referenced document's identifier (e.g. ``MongoId``) instead of a `DBRef`_.

Example:

.. configuration-block::

    .. code-block:: php

        <?php

        /**
         * @ReferenceOne(targetDocument="Profile", simple=true)
         */
        private $profile;

    .. code-block:: xml

        <reference-one target-document="Documents\Profile", simple="true" />

    .. code-block:: yaml

        referenceOne:
          profile:
            simple: true

Now, the ``profile`` field will only store the ``MongoId`` of the referenced
Profile document.

Simple references reduce the amount of storage used, both for the document
itself and any indexes on the reference field; however, simple references cannot
be used with discriminators, since there is no `DBRef`_ object in which to store
a discriminator value.

Cascading Operations
--------------------

By default, Doctrine will not cascade any ``UnitOfWork`` operations to
referenced documents. You must explicitly enable this functionality:

.. configuration-block::

    .. code-block:: php

        <?php

        /**
         * @ReferenceOne(targetDocument="Profile", cascade={"persist"})
         */
        private $profile;

    .. code-block:: xml

        <reference-one target-document="Documents\Profile">
            <cascade>
                <persist/>
            </cascade>
        </reference-one>

    .. code-block:: yaml

        referenceOne:
          profile:
            cascade: [persist]

The valid values are:

-  **all** - cascade all operations by default.
-  **detach** - cascade detach operation to referenced documents.
-  **merge** - cascade merge operation to referenced documents.
-  **refresh** - cascade refresh operation to referenced documents.
-  **remove** - cascade remove operation to referenced documents.
-  **persist** - cascade persist operation to referenced documents.

.. _`DBRef`: http://docs.mongodb.org/manual/reference/database-references/#dbref
