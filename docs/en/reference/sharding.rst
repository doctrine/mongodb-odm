.. _sharding:

Sharding
========

MongoDB allows you to horizontally scale your database. In order to enable this,
Doctrine MongoDB ODM needs to know about your sharding setup. For basic information
about sharding, please refer to the `MongoDB docs <https://docs.mongodb.com/manual/sharding/>`_.

Once you have a `sharded cluster <https://docs.mongodb.com/manual/core/sharded-cluster-architectures-production/>`_,
you can enable sharding for a document. You can do this by defining a shard key in
the document as well as an appropriate index:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[Index(keys: ['username' => 'asc'])]
        #[ShardKey(keys: ['username' => 'asc'])]
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
                <shard-key>
                    <key name="username" order="asc"/>
                </shard-key>
                <indexes>
                    <index>
                        <key name="username" order="asc" />
                    </index>
                </indexes>
            </document>
        </doctrine-mongo-mapping>

.. note::
    When a shard key is defined for a document, Doctrine MongoDB ODM will no
    longer persist changes to the shard key as these fields become immutable in
    a sharded setup.

Once you've defined a shard key you need to enable sharding for the collection
where the document will be stored. To do this, use the ``odm:schema:shard``
command.

.. note::

    For performance reasons, sharding is not enabled during the
    ``odm:schema:create`` and ``odm:schema:update`` commmands.
