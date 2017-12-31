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

        /**
         * @Document
         * @Index(keys={"coordinates"="2d"})
         */
        class City
        {
            /** @Id */
            public $id;

            /** @Field(type="string") */
            public $name;

            /** @EmbedOne(targetDocument="Coordinates") */
            public $coordinates;
        }

        /** @EmbeddedDocument */
        class Coordinates
        {
            /** @Field(type="float") */
            public $x;

            /** @Field(type="float") */
            public $y;
        }

    .. code-block:: xml

        <indexes>
            <index>
                <key name="coordinates" order="2d" />
            </index>
        </indexes>

    .. code-block:: yaml

        indexes:
          coordinates:
            keys:
              coordinates: 2d

Near Query
----------

Now you can execute queries against these documents like the
following. Check for the 10 nearest cities to a given longitude
and latitude with the ``near($longitude, $latitude)`` method:

.. code-block:: php

    <?php

    $cities = $this->dm->createQuery('City')
        ->field('coordinates')->near(-120, 40)
        ->execute();

.. _geonear:

Within Box
----------

You can also query for cities within a given rectangle using the
``withinBox($x1, $y1, $x2, $y2)`` method:

.. code-block:: php

    <?php

    $cities = $this->dm->createQuery('City')
        ->field('coordinates')->withinBox(41, 41, 72, 72)
        ->execute();

Within Center
-------------

In addition to boxes you can check for cities within a circle using
the ``withinCenter($x, $y, $radius)`` method:

.. code-block:: php

    <?php

    $cities = $this->dm->createQuery('City')
        ->field('coordinates')->withinCenter(50, 50, 20)
        ->execute();
