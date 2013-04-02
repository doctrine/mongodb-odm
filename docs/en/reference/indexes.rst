Indexes
=======

Working with indexes in the MongoDB ODM is pretty straight forward.
You can have multiple indexes, they can consist of multiple fields,
they can be unique and you can give them an order. In this chapter
we'll show you examples of indexes using annotations.

First here is an example where we put an index on a single
property:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
        class User
        {
            /** @Id */
            public $id;
    
            /** @String @Index */
            public $username;
        }

    .. code-block:: xml

        <field name="username" index="true" />

    .. code-block:: yaml
    
        fields:
          username:
            index: true


Index Options
-------------

You can customize the index with some additional options:

- 
   **name** - The name of the index. This can be useful if you are
   indexing many keys and Mongo complains about the index name being
   too long.
- 
   **dropDups** - If a unique index is being created and duplicate
   values exist, drop all but one duplicate value.
- 
   **background** - Create indexes in the background while other
   operations are taking place. By default, index creation happens
   synchronously. If you specify TRUE with this option, index creation
   will be asynchronous.
- 
   **safe** - You can specify a boolean value for checking if the
   index creation succeeded. The driver will throw a
   MongoCursorException if index creation failed.
-
   **expireAfterSeconds** - If you specify this option then the associated 
   document will be automatically removed when the provided time (in seconds) 
   has passed. This option is bound to a number of limitations, which 
   are documented at http://docs.mongodb.org/manual/tutorial/expire-data/.
-
   **order** - The order of the index (asc or desc).
-
   **unique** - Create a unique index.
-
   **sparse** - Create a sparse index. If a unique index is being created
   the sparse option will allow duplicate null entries, but the field must be
   unique otherwise.

Unique Index
------------

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
        class User
        {
            /** @Id */
            public $id;
    
            /** @String @Index(unique=true, order="asc") */
            public $username;
        }

    .. code-block:: xml

        <field fieldName="username" index="true" unique="true" order="asc" />

    .. code-block:: yaml

        fields:
          username:
            index: true
            unique: true
            order: true

For your convenience you can quickly specify a unique index with
``@UniqueIndex``:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document */
        class User
        {
            /** @Id */
            public $id;
    
            /** @String @UniqueIndex(order="asc") */
            public $username;
        }

    .. code-block:: xml

        <field fieldName="username" unique="true" order="asc" />

    .. code-block:: yaml

        fields:
          username:
            unique: true
            order: true

If you want to specify an index that consists of multiple fields
you can specify them on the class doc block:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /**
         * @Document
         * @UniqueIndex(keys={"accountId"="asc", "username"="asc"})
         */
        class User
        {
            /** @Id */
            public $id;
    
            /** @Integer */
            public $accountId;
    
            /** @String */
            public $username;
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

    .. code-block:: yaml

        Documents\User:
          indexes:
            usernameacctid:
              options:
                unique: true
              keys:
                accountId:
                  order: asc
                username:
                  order: asc

To specify multiple indexes you must use the ``@Indexes``
annotation:

.. configuration-block::

    .. code-block:: php

        <?php

        /**
         * @Document
         * @Indexes({
         *   @Index(keys={"accountId"="asc"}),
         *   @Index(keys={"username"="asc"}) 
         * })
         */
        class User
        {
            /** @Id */
            public $id;
    
            /** @Integer */
            public $accountId;
    
            /** @String */
            public $username;
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

    .. code-block:: yaml

        Documents\User:
          indexes:
            accountId:
              keys:
                accountId:
                  order: asc
            username:
              keys:
                username:
                  order: asc

Embedded Indexes
----------------

You can specify indexes on embedded documents just like you do on normal documents. When Doctrine
creates the indexes for a document it will also create all the indexes from its mapped embedded
documents.

.. code-block:: php

    <?php

    namespace Documents;
    
    /** @EmbeddedDocument */
    class Comment
    {
        /** @Date @Index */
        private $date;

        // ...
    }

Now if we had a ``BlogPost`` document with the ``Comment`` document embedded many times:

.. code-block:: php

    <?php

    namespace Documents;

    /** @Document */
    class BlogPost
    {
        // ...

        /** @Field(type="string") @Index */
        private $slug;

        /** @EmbedMany(targetDocument="Comment") */
        private $comments;
    }

If we were to create the indexes with the ``SchemaManager``:

.. code-block:: php

    <?php

    $sm->ensureIndexes();

It will create the indexes from the ``BlogPost`` document but will also create the indexes that are
defined on the ``Comment`` embedded document. The following would be executed on the underlying MongoDB
database:

..

    db.BlogPost.ensureIndexes({ 'slug' : 1, 'comments.date': 1 })

Also, for your convenience you can create the indexes for your mapped documents from the
:doc:`console <console-commands>`:

    $ php mongodb.php mongodb:schema:create --index

Geospatial Indexing
-------------------

You can specify a geospatial index by just specifying the keys and
options structures manually:

.. configuration-block::

    .. code-block:: php

        <?php

        /**
         * @Document
         * @Index(keys={"coordinates"="2d"})
         */
        class Place
        {
            /** @Id */
            public $id;
    
            /** @EmbedOne(targetDocument="Coordinates") */
            public $coordinates;
        }
    
        /** @EmbeddedDocument */
        class Coordinates
        {
            /** @Float */
            public $latitude;
    
            /** @Float */
            public $longitude;
        }

    .. code-block:: xml

        <indexes>
            <index>
                <key name="coordinates" value="2d" />
            </index>
        </indexes>

    .. code-block:: yaml

        indexes:
          coordinates:
            keys:
              coordinates: 2d

Requiring Indexes
-----------------

Sometimes you may want to require indexes for all your queries to ensure you don't let stray unindexed queries
make it to the database and cause performance problems.


.. configuration-block::

    .. code-block:: php

        <?php

        /**
         * @Document(requireIndexes=true)
         */
        class Place
        {
            /** @Id */
            public $id;
    
            /** @String @Index */
            public $city;
        }

    .. code-block:: xml

        // Documents.Place.dcm.xml

        <?xml version="1.0" encoding="UTF-8"?>
        
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">
        
            <document name="Documents\Place" require-indexes="true">
                <field fieldName="id" id="true" />
                <field fieldName="city" type="string" />
                <indexes>
                    <index>
                        <key name="city">
                    </index>
                </indexes>
            </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        # Documents.Place.dcm.yml

        Documents\Place:
          fields:
            id:
              id: true
            city:
              type: string
          indexes:
            index1:
              keys:
                city: asc

When you run queries it will check that it is indexed and throw an exception if it is not indexed:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Documents\Place')
        ->field('city')->equals('Nashville');
    $query = $qb->getQuery();
    $places = $query->execute();

When you execute the query it will throw an exception if `city` was not indexed in the database. You can control
whether or not an exception will be thrown by using the `requireIndexes()` method:

.. code-block:: php

    <?php

    $qb->requireIndexes(false);

You can also check if the query is indexed and with the `isIndexed()` method and use it to display your
own notification when a query is unindexed:

.. code-block:: php

    <?php

    $query = $qb->getQuery();
    if (!$query->isIndexed()) {
        $notifier->addError('Cannot execute queries that are not indexed.');
    }

If you don't want to require indexes for all queries you can set leave `requireIndexes` as false and control
it on a per query basis:

.. code-block:: php

    <?php

    $qb->requireIndexes(true);
    $query = $qb->getQuery();
    $results = $query->execute();