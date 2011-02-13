Working with indexes in the MongoDB ODM is pretty straight forward.
You can have multiple indexes, they can consist of multiple fields,
they can be unique and you can give them an order. In this chapter
we'll show you examples of indexes using annotations.

First here is an example where we put an index on a single
property:

.. code-block:: php

    <?php
    /** @Document */
    class User
    {
        /** @Id */
        public $id;
    
        /** @String @Index */
        public $username;
    }

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
-  **order** - The order of the index (asc or desc).
-  **unique** - Create a unique index.

Unique Index
------------

.. code-block:: php

    <?php
    /** @Document */
    class User
    {
        /** @Id */
        public $id;
    
        /** @String @Index(unique=true, order="asc") */
        public $username;
    }

For your convenience you can quickly specify a unique index with
``@UniqueIndex``:

.. code-block:: php

    <?php
    /** @Document */
    class User
    {
        /** @Id */
        public $id;
    
        /** @String @UniqueIndex(order="asc") */
        public $username;
    }

If you want to specify an index that consists of multiple fields
you can specify them on the class doc block:

.. code-block:: php

    <?php
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

To specify multiple indexes you must use the ``@Indexes``
annotation:

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

Geospatial Indexing
-------------------

You can specify a geospatial index by just specifying the keys and
options structures manually:

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


