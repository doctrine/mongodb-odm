Embedded Mapping
================

This chapter explains how embedded documents are mapped in
Doctrine.

.. _embed_one:

Embed One
---------

Embed a single document:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ...

            #[EmbedOne(targetDocument: Address::class)]
            private $address;

            // ...
        }

        #[EmbeddedDocument]
        class Address
        {
            #[Field(type: 'string')]
            private $street;

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

          <embedded-document name="Address">
                <field name="street" type="string" />
          </embedded-document>
        </doctrine-mongo-mapping>

.. _embed_many:

Embed Many
----------

Embed many documents:

.. configuration-block::

    .. code-block:: php

        <?php

        use Doctrine\Common\Collections\ArrayCollection;

        #[Document]
        class User
        {
            // ...

            #[EmbedMany(targetDocument: Phonenumber::class)]
            private $phoneNumbers;

            // ...
            public function __construct()
            {
                $this->phoneNumbers = new ArrayCollection();
            }
        }

        #[EmbeddedDocument]
        class PhoneNumber
        {
            #[Field(type: 'string')]
            private $number;

            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <embed-many field="phoneNumbers" target-document="PhoneNumber" />
          </document>

          <embedded-document name="PhoneNumber">
                <field name="number" type="string" />
          </embedded-document>
        </doctrine-mongo-mapping>

.. _embed_mixing_document_types:

Mixing Document Types
---------------------

If you want to store different types of embedded documents in the same field,
you can simply omit the ``targetDocument`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        use Doctrine\Common\Collections\ArrayCollection;

        #[Document]
        class User
        {
            // ..

            #[EmbedMany]
            private $tasks;

            // ...
            public function __construct()
            {
                $this->tasks = new ArrayCollection();
            }
        }

    .. code-block:: xml

        <embed-many field="tasks" />

Now the ``$tasks`` property can store any type of document! The class name will
be automatically stored in a field named ``_doctrine_class_name`` within
the embedded document. The field name can be customized with the
``discriminatorField`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        use Doctrine\Common\Collections\ArrayCollection;

        #[Document]
        class User
        {
            // ..

            #[EmbedMany(discriminatorField: 'type')]
            private $tasks;

            // ...
            public function __construct()
            {
                $this->tasks = new ArrayCollection();
            }
        }

    .. code-block:: xml

        <embed-many field="tasks">
            <discriminator-field name="type" />
        </embed-many>

You can also specify a discriminator map to avoid storing the |FQCN|
in each embedded document:

.. configuration-block::

    .. code-block:: php

        <?php

        use Doctrine\Common\Collections\ArrayCollection;

        #[Document]
        class User
        {
            // ..

            #[EmbedMany(
              discriminatorMap: [
                  'download" => DownloadTask::class,
                  'build' => BuildTask::class,
              ]
            )]
            private $tasks;

            // ...
            public function __construct()
            {
                $this->tasks = new ArrayCollection();
            }
        }

    .. code-block:: xml

        <embed-many field="tasks">
            <discriminator-map>
                <discriminator-mapping value="download" class="DownloadTask" />
                <discriminator-mapping value="build" class="BuildTask" />
            </discriminator-map>
        </embed-many>

If you have embedded documents without a discriminator value that need to be
treated correctly you can optionally specify a default value for the
discriminator:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ..

            #[EmbedMany(
                discriminatorMap: [
                  'download" => DownloadTask::class,
                  'build' => BuildTask::class,
                ],
                defaultDiscriminatorValue: 'download',
            )]
            private $tasks = [];

            // ...
        }

    .. code-block:: xml

        <embed-many field="tasks">
            <discriminator-map>
                <discriminator-mapping value="download" class="DownloadTask" />
                <discriminator-mapping value="build" class="BuildTask" />
            </discriminator-map>
            <default-discriminator-value value="download" />
        </embed-many>

Cascading Operations
--------------------

All operations on embedded documents are automatically cascaded.
This is because embedded documents are part of their parent
document and cannot exist without those by nature.

.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>

.. _embed_store_empty_array:

Storing Empty Arrays in Embedded Documents
-------------------------------------------

By default, when an embedded collection property is empty, Doctrine does not store any data for it in the database.
However, in some cases, you may want to explicitly store an empty array for such properties.
You can achieve this behavior by using the `storeEmptyArray` option for embedded collections.

.. configuration-block::

    .. code-block:: php
        <?php
        #[Document]
        class User
        {
            // ...
            #[EmbedMany(targetDocument: PhoneNumber::class, storeEmptyArray: true)]
            private $phoneNumbers = [];
            // ...
        }
    .. code-block:: xml
        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <embed-many field="phoneNumbers" target-document="PhoneNumber" store-empty-array="true" />
          </document>
          <embedded-document name="PhoneNumber">
                <field name="number" type="string" />
          </embedded-document>
        </doctrine-mongo-mapping>
Now, when the `$phoneNumbers` collection is empty, an empty array will be stored in the database for the `User`
document's embedded `phoneNumbers` collection, even if there are no actual embedded documents in the collection.
