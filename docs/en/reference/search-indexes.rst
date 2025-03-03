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
