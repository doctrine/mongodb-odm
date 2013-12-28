Embedded Mapping
================

This chapter explains how embedded documents are mapped in
Doctrine.

Embed One
---------

Embed a single document:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ...

            /** @EmbedOne(targetDocument="Address") */
            private $address;

            // ...
        }

        /** @EmbeddedDocument */
        class Address
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <embed-one field="address" target-document="Address" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        User:
          type: document
          embedOne:
            address:
              targetDocument: Address

        Address:
          type: embeddedDocument
          
.. _embed_many:

Embed Many
----------

Embed many documents:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ...

            /** @EmbedMany(targetDocument="Phonenumber") */
            private $phonenumbers = array();

            // ...
        }

        /** @EmbeddedDocument */
        class Phonenumber
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <embed-many field="phonenumbers" target-document="Phonenumber" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        User:
          type: document
          embedMany:
            phonenumbers:
              targetDocument: Phonenumber

        Phonenumber:
          type: embeddedDocument
          
Mixing Document Types
---------------------

If you want to store different types of embedded documents in the same field,
you can simply omit the ``targetDocument`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /** @EmbedMany */
            private $tasks = array();

            // ...
        }

    .. code-block:: xml

        <embed-many field="tasks" />

    .. code-block:: yaml

        embedMany:
          tasks: ~

Now the ``$tasks`` property can store any type of document! The class name will
be automatically stored  stored in a field named ``_doctrine_class_name`` within
the embedded document. The field name can be customized with the
``discriminatorField`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /**
             * @EmbedMany(discriminatorField="type")
             */
            private $tasks = array();

            // ...
        }

    .. code-block:: xml

        <embed-many fieldName="tasks">
            <discriminator-field name="type" />
        </embed-many>

    .. code-block:: yaml

        embedMany:
          tasks:
            discriminatorField: type

You can also specify a discriminator map to avoid storing the fully qualified
class name in each embedded document:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document */
        class User
        {
            // ..

            /**
             * @EmbedMany(
             *   discriminatorMap={
             *     "download"="DownloadTask",
             *     "build"="BuildTask"
             *   }
             * )
             */
            private $tasks = array();

            // ...
        }

    .. code-block:: xml

        <embed-many fieldName="tasks">
            <discriminator-map>
                <discriminator-mapping value="download" class="DownloadTask" />
                <discriminator-mapping value="build" class="BuildTask" />
            </discriminator-map>
        </embed-many>

    .. code-block:: yaml

        embedMany:
          tasks:
            discriminatorMap:
              download: DownloadTask
              build: BuildTask

Cascading Operations
--------------------

All operations on embedded documents are automatically cascaded.
This is because embedded documents are part of their parent
document and cannot exist without those by nature.
