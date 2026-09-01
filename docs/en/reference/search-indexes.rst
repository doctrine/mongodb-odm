.. _search_indexes:

Search Indexes
==============

In addition to standard :ref:`indexes <indexes>`, ODM allows you to define
search indexes for use with `MongoDB Atlas Search <https://www.mongodb.com/docs/atlas/atlas-search/>`__.
Search indexes may be queried using the `$search <https://www.mongodb.com/docs/atlas/atlas-search/aggregation-stages/search/>`__
and `$searchMeta <https://www.mongodb.com/docs/atlas/atlas-search/aggregation-stages/searchMeta/>`__
aggregation pipeline stages.

Search indexes have some notable differences from regular
:ref:`indexes <indexes>` in ODM. They may only be defined on document classes.
Definitions will not be incorporated from embedded documents. Additionally, ODM
will **NOT** translate field names in search index definitions. Database field
names must be used instead of mapped field names (i.e. PHP property names).

Search Index Options
--------------------

Search indexes are defined using a more complex syntax than regular
:ref:`indexes <indexes>`.

ODM supports the following search index options:

-
    ``name`` - Name of the search index to create, which must be unique to the
    collection. Defaults to ``"default"``.
-
    ``dynamic`` - Enables or disables dynamic field mapping for this index.
    If ``true``, the index will include all fields with
    `supported data types <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#std-label-bson-data-chart>`__.
    If ``false``, the ``fields`` argument must be specified. Defaults to ``false``.
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
    ``analyzer`` argument, or the `standard analyzer <https://www.mongodb.com/docs/atlas/atlas-search/analyzers/standard/>`__.
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

Additional documentation for defining search indexes may be found in
`search index definition <https://www.mongodb.com/docs/manual/reference/command/createSearchIndexes/#search-index-definition-syntax>`__
within the MongoDB manual.

Static Mapping
--------------

`Static mapping <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#static-mappings>`__
can be used to configure indexing of specific fields within a document.

The following example demonstrates how to define a search index using static
mapping.

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[SearchIndex(
          name: 'usernameAndAddresses',
          fields: [
            'username' => [
              ['type' => 'string'],
              ['type' => 'autocomplete'],
            ],
            'addresses' => ['type' => 'embeddedDocuments', 'dynamic' => true],
          ],
        )]
        class User
        {
            #[Id]
            private string $id;

            #[Field(type: 'string')]
            private string $username;

            #[EmbedMany(targetDocument: Address::class)]
            private ?Address $addresses;

            // ...
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">

            <document name="Documents\User">
                <search-indexes>
                    <search-index name="usernameAndAddresses">
                        <field name="username" type="string" />
                        <field name="username" type="autocomplete" />
                        <field name="addresses" type="embeddedDocuments" dynamic="true" />
                    </search-index>
                </search-indexes>

                <!-- ... -->
            </document>
        </doctrine-mongo-mapping>

The ``username`` field will indexed both as a string and for autocompletion.
Since the ``addresses`` field uses an :ref:`embed-many <embed_many>`
relationship, it must be indexed using the ``embeddedDocuments`` type; however,
embedded documents within the array are permitted to use dynamic mapping.

Dynamic Mapping
---------------

`Dynamic mapping <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#dynamic-mappings>`__
can be used to automatically index fields with
`supported data types <https://www.mongodb.com/docs/atlas/atlas-search/define-field-mappings/#std-label-bson-data-chart>`__
within a document. Dynamically mapped indexes occupy more disk space than
statically mapped indexes and may be less performant; however, they may be
useful if your schema changes  or for when experimenting with Atlas Search

.. note::

    Atlas Search does **NOT** dynamically index embedded documents contained
    within arrays (e.g. :ref:`embed-many <embed_many>` relationships). You must
    use static mappings with the `embeddedDocument <https://www.mongodb.com/docs/atlas/atlas-search/field-types/embedded-documents-type/>`__
    field type.

The following example demonstrates how to define a search index using dynamic
mapping:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[SearchIndex(dynamic: true)]
        class BlogPost
        {
            #[Id]
            private string $id;

            #[Field(type: 'string')]
            private string $title;

            #[Field(type: 'string')]
            private string $body;

            // ...
        }

    .. code-block:: xml

        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">

            <document name="Documents\BlogPost">
                <search-indexes>
                    <search-index dynamic="true" />
                </search-indexes>

                <!-- ... -->
            </document>
        </doctrine-mongo-mapping>

Creating and Waiting for Search Indexes
---------------------------------------

Search indexes are built asynchronously on the server: the create and update
commands submit index definitions and return immediately, before the indexes
are queryable. When you need the indexes to be ready before continuing (CI
pipelines, deployment scripts, integration test seeding), use the ``--wait``
option of ``odm:schema:create`` and ``odm:schema:update``:

.. code-block:: console

    $ php mongodb.php odm:schema:create --search-index --wait
    $ php mongodb.php odm:schema:update --wait=1minute
    $ php mongodb.php odm:schema:create --search-index --wait="30 seconds"
    $ php mongodb.php odm:schema:create --search-index --wait=5000

The option accepts a duration string parsable by ``strtotime()`` (for example
``30 seconds``, ``1minute``, ``1 hour``) or a positive integer number of
milliseconds. This is a maximum wait duration: the command returns as soon as
the indexes are ready, without waiting out the rest of the duration. When
``--wait`` is passed without a value, a default timeout of 5 minutes is used.
The command exits with an error if the indexes are not queryable within the
given time.

The wait is skipped automatically when no mapped class declares a search
index.

From PHP, you can also wait for search indexes to become queryable using the
:phpmethod:`SchemaManager::waitForSearchIndexes` method:

.. code-block:: php

    <?php

    $schemaManager->createSearchIndexes();
    $schemaManager->waitForSearchIndexes();

.. note::

    Waiting only reports when the index itself is queryable. When documents
    are inserted after the index is created, there is no reliable way to
    know when the background indexer has caught up with those inserts. If
    your workflow needs to wait for the index to reflect a specific set of
    documents, insert those documents **before** creating the search index,
    then wait for the index to become queryable.
