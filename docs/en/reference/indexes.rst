Indexes
=======

Working with indexes in the MongoDB ODM is pretty straight forward.
You can have multiple indexes, they can consist of multiple fields,
they can be unique and you can give them an order. In this chapter
we'll show you examples of indexes using attributes.

First here is an example where we put an index on a single
property:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document]
        class User
        {
            #[Id]
            public string $id;

            #[Field(type: 'string')]
            #[Index]
            public string $username;
        }

    .. code-block:: xml

        <field name="username" index="true" />

Index Options
-------------

You can customize the index with some additional options:

-
   **name** - The name of the index. This can be useful if you are
   indexing many keys and Mongo complains about the index name being
   too long.
-
   **background** - Create indexes in the background while other
   operations are taking place. By default, index creation happens
   synchronously. If you specify TRUE with this option, index creation
   will be asynchronous.
-
   **expireAfterSeconds** - If you specify this option then the associated
   document will be automatically removed when the provided time (in seconds)
   has passed. This option is bound to a number of limitations, which
   are documented at https://docs.mongodb.com/manual/tutorial/expire-data/.
-
   **order** - The order of the index (asc or desc).
-
   **unique** - Create a unique index.
-
   **sparse** - Create a sparse index. If a unique index is being created
   the sparse option will allow duplicate null entries, but the field must be
   unique otherwise.
-
   **partialFilterExpression** - Create a partial index. Partial indexes only
   index the documents in a collection that meet a specified filter expression.
   By indexing a subset of the documents in a collection, partial indexes have
   lower storage requirements and reduced performance costs for index creation
   and maintenance. This feature was introduced with MongoDB 3.2 and is not
   available on older versions.

Unique Index
------------

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document]
        class User
        {
            #[Id]
            public string $id;

            #[Field(type: 'string')]
            #[Index(unique: true, order: 'asc')]
            public string $username;
        }

    .. code-block:: xml

        <field field-name="username" index="true" unique="true" order="asc" />

For your convenience you can quickly specify a unique index with
``#[UniqueIndex]``:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;
        #[Document]
        class User
        {
            #[Id]
            public string $id;

            #[Field(type: 'string')]
            #[UniqueIndex(order: 'asc')]
            public string $username;
        }

    .. code-block:: xml

        <field field-name="username" unique="true" order="asc" />

If you want to specify an index that consists of multiple fields
you can specify them on the class doc block:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document]
        #[UniqueIndex(keys: ['accountId' => 'asc', 'username' => 'asc'])]
        class User
        {
            #[Id]
            public string $id;

            #[Field(type: 'int')]
            public int $accountId;

            #[Field(type: 'string')]
            public string $username;
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">

            <document name="Documents\User">
                <indexes>
                    <index>
                        <option name="unique" value="true" />
                        <key name="accountId" order="asc" />
                        <key name="username" order="asc" />
                    </index>
                </indexes>
            </document>
        </doctrine-mongo-mapping>

To specify multiple indexes you can repeat the ``#[Index]``
attribute:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[Index(keys: ['accountId' => 'asc'])]
        #[Index(keys: ['username' => 'asc'])]
        class User
        {
            #[Id]
            public string $id;

            #[ODM\Field(type: 'int')]
            public int $accountId;

            #[Field(type: 'string')]
            public string $username;
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">

            <document name="Documents\User">
                <indexes>
                    <index>
                        <key name="accountId" order="asc" />
                    </index>
                    <index>
                        <key name="username" order="asc" />
                    </index>
                </indexes>
            </document>
        </doctrine-mongo-mapping>

Embedded Indexes
----------------

You can specify indexes on embedded documents just like you do on normal documents. When Doctrine
creates the indexes for a document it will also create all the indexes from its mapped embedded
documents.

.. code-block:: php

    <?php

    namespace Documents;

    #[EmbeddedDocument]
    class Comment
    {
        #[Field(type: 'date')]
        #[Index]
        private \DateTime $date;

        // ...
    }

Now if we had a ``BlogPost`` document with the ``Comment`` document embedded many times:

.. code-block:: php

    <?php

    namespace Documents;

    use Doctrine\Common\Collections\Collection;

    #[Document]
    class BlogPost
    {
        // ...

        #[Field(type: 'string')]
        #[Index]
        private string $slug;

        /** @var Collection<Comment> */
        #[EmbedMany(targetDocument: Comment::class)]
        private Collection $comments;
    }

If we were to create the indexes with the ``SchemaManager``:

.. code-block:: php

    <?php

    $sm->ensureIndexes();

It will create the indexes from the ``BlogPost`` document but will also create the indexes that are
defined on the ``Comment`` embedded document. The following would be executed on the underlying MongoDB
database:

.. code-block:: javascript

    db.BlogPost.ensureIndexes({ 'slug' : 1, 'comments.date': 1 })

Also, for your convenience you can create the indexes for your mapped documents from the
:doc:`console <console-commands>`:

.. code-block:: console

    $ php mongodb.php odm:schema:create --index

.. note::

    If you are :ref:`mixing document types <embed_mixing_document_types>` for your
    embedded documents, ODM will not be able to create indexes for their fields
    unless you specify a discriminator map for the :ref:`embed-one <embed_one>`
    or :ref:`embed-many <embed_many>` relationship.

.. note::

    If the ``name`` option is specified on an index in an embedded document, it
    will be prefixed with the embedded field path before creating the index.
    This is necessary to avoid index name conflict when the same document is
    embedded multiple times in a single collection. Prefixing of the index name
    can cause errors due to excessive index name length. In this case, try
    shortening the index name or embedded field path.

Geospatial Indexing
-------------------

You can specify a geospatial index by just specifying the keys and
options structures manually:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[Index(keys: ['coordinates' => '2d'])]
        class Place
        {
            #[Id]
            public string $id;

            #[EmbedOne(targetDocument: Coordinates::class)]
            public ?Coordinates $coordinates;
        }

        #[EmbeddedDocument]
        class Coordinates
        {
            #[Field(type: 'float')]
            public float $latitude;

            #[Field(type: 'float')]
            public float $longitude;
        }

    .. code-block:: xml

        <indexes>
            <index>
                <key name="coordinates" order="2d" />
            </index>
        </indexes>

Partial indexes
---------------

You can create a partial index by adding a ``partialFilterExpression`` to any
index.

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[Index(keys: ['city' => 'asc'], partialFilterExpression: ['version' => ['$gt' => 1]])]
        class Place
        {
            #[Id]
            public string $id;

            #[Field(type: 'string')]
            public string $city;

            #[ODM\Field(type: 'int')]
            public int $version;
        }

    .. code-block:: xml

        <indexes>
            <index>
                <key name="city" order="asc" />
                <partial-filter-expression>
                    <field name="version" value="1" operator="gt" />
                </partial-filter-expression>
            </index>
        </indexes>

.. note::

    Partial indexes are only available with MongoDB 3.2 or newer. For more
    information on partial filter expressions, read the
    `official MongoDB documentation <https://docs.mongodb.com/manual/core/index-partial/>`_.
