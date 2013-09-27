Capped Collections
==================

Capped collections are fixed sized collections that have a very
high performance auto-LRU age-out feature (age out is based on
insertion order).

In addition, capped collections automatically, with high
performance, maintain insertion order for the objects in the
collection; this is very powerful for certain use cases such as
logging.

Mapping
-------

You can configure the collection in the ``collection`` attribute of
the ``@Document`` annotation:

.. code-block:: php

    <?php

    /**
     * @Document(collection={
     *   "name"="collname",
     *   "capped"=true,
     *   "size"=100000,
     *   "max"=1000
     * })
     */
    class Category
    {
        /** @Id */
        public $id;
    
        /** @String */
        public $name;
    }

Creating
--------

Remember that you must manually create the collections. If you let
MongoDB create the collection lazily the first time it is selected,
it will not be created with the capped configuration. You can
create the collection for a document with the ``SchemaManager``
that can be acquired from your ``DocumentManager`` instance:

.. code-block:: php

    <?php

    $documentManager->getSchemaManager()->createDocumentCollection('Category');

You can drop the collection too if it already exists:

.. code-block:: php

    <?php

    $documentManager->getSchemaManager()->dropDocumentCollection('Category');
