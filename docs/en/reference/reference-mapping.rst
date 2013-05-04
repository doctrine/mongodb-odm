Reference Mapping
=================

This chapter explains how references between documents are mapped
with Doctrine.

Collections
-----------

In all the examples of many-valued references in this manual we
will make use of a ``Collection`` interface and a corresponding
default implementation ``ArrayCollection`` that are defined in the
``Doctrine\Common\Collections`` namespace. Why do we need that?
Doesn't that couple my domain model to Doctrine? Unfortunately, PHP
arrays, while being great for many things, do not make up for good
collections of business objects, especially not in the context of
an ODM. The reason is that plain PHP arrays can not be
transparently extended / instrumented in PHP code, which is
necessary for a lot of advanced ODM features. The classes /
interfaces that come closest to an OO collection are ArrayAccess
and ArrayObject but until instances of these types can be used in
all places where a plain array can be used (something that may
happen in PHP6) their usability is fairly limited. You "can"
type-hint on ``ArrayAccess`` instead of ``Collection``, since the
Collection interface extends ``ArrayAccess``, but this will
severely limit you in the way you can work with the collection,
because the ``ArrayAccess`` API is (intentionally) very primitive
and more importantly because you can not pass this collection to
all the useful PHP array functions, which makes it very hard to
work with.

**CAUTION** The Collection interface and ArrayCollection class,
like everything else in the Doctrine namespace, are neither part of
the ODM, it is a plain PHP class that has no outside dependencies
apart from dependencies on PHP itself (and the SPL). Therefore
using this class in your domain classes and elsewhere does not
introduce a coupling to the persistence layer. The Collection
class, like everything else in the Common namespace, is not part of
the persistence layer. You could even copy that class over to your
project if you want to remove Doctrine from your project and all
your domain classes will work the same as before.

Reference One
-------------

Reference one document:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

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

Reference Many
--------------

Reference many documents:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

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

If you want to store different types of documents in references you
can simply omit the ``targetDocument`` option:

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

Now the ``$favorites`` property can store a reference to any type
of document! The class name will be automatically added for you in
a field named ``_doctrine_class_name``.

You can also specify a discriminator map to avoid storing the fully
qualified class name with each reference:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

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

If you want to store the discriminator value in a field other than
``_doctrine_class_name`` you can use the ``discriminatorField``
option:

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

Simple References
-----------------

By default all references are stored as a ``DBRef`` with the traditional ``$id``,
``$db`` and ``$ref`` fields but if you want you can configure your references
to be simple and only store a ``MongoId``.

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

Now when you create a new reference to a Profile only a ``MongoId`` instance
will be stored in the ``profile`` field.

Benefits:

- Smaller amount of storage used.
- Performance and simple indexing.

Cascading Operations
--------------------

By default Doctrine will not cascade any ``UnitOfWork`` operations
to referenced documents so if wish to have this functionality you
must explicitly enable it:

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

-  **all** - cascade on all operations by default.
-  **detach** - cascade detach operation to referenced documents.
-  **merge** - cascade merge operation to referenced documents.
-  **refresh** - cascade refresh operation to referenced documents.
-  **remove** - cascade remove operation to referenced documents.
-  **persist** - cascade persist operation to referenced documents.