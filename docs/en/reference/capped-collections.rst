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

.. configuration-block::

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

            /** @Field(type="string") */
            public $name;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
            <document name="Documents\Category" collection="collname" capped-collection="true" capped-collection-size="100000" capped-collection-max="1000">
                <field fieldName="id" id="true" />
                <field fieldName="name" type="string" />
            </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\Category:
          type: document
          collection:
            name: collname
            capped: true
            size: 100000
            max: 1000
          fields:
            id:
              type: id
              id: true
            name:
              type: string

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
