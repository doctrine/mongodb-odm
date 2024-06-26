Geospatial Queries
==================

You can execute some special queries when using geospatial indexes
like checking for documents within a rectangle or circle.

Mapping
-------

First, setup some documents like the following:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        #[Index(keys: ['coordinates' => '2d'])]
        class City
        {
            #[Id]
            public string $id;

            #[Field(type: 'string')]
            public string $name;

            #[EmbedOne(targetDocument: Coordinates::class)]
            public ?Coordinates $coordinates;
        }

        #[EmbeddedDocument]
        class Coordinates
        {
            #[Field(type: 'float')]
            public float $x;

            #[Field(type: 'float')]
            public float $y;
        }

    .. code-block:: xml

        <indexes>
            <index>
                <key name="coordinates" order="2d" />
            </index>
        </indexes>

Near Query
----------

Now you can execute queries against these documents like the
following. Check for the 10 nearest cities to a given longitude
and latitude with the ``near($longitude, $latitude)`` method:

.. code-block:: php

    <?php

    $cities = $this->dm->createQuery(City::class)
        ->field('coordinates')->near(-120, 40)
        ->execute();

.. _geonear:
