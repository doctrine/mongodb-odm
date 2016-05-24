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

            /** @Distance */
            public $distance;
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

GeoNear Command
---------------

You can also execute the `geoNear command`_ using the query builder's
``geoNear()`` method. Additional builder methods can be used to set options for
this command (e.g. ``distanceMultipler()``, ``maxDistance()``, ``spherical()``).
Unlike ``near()``, which uses a query operator, ``geoNear()`` does not require
the location field to be specified in the builder, as MongoDB will use the
single geospatial index for the collection. Documents will be returned in order
of nearest to farthest.

.. code-block:: php

    <?php

    $cities = $this->dm->createQuery('City')
        ->geoNear(-120, 40)
        ->spherical(true)
        // Convert radians to kilometers (use 3963.192 for miles)
        ->distanceMultiplier(6378.137)
        ->execute();

If the model has a property mapped with :ref:`@Distance <annotation_distance>`,
that field will be set with the calculated distance between the document and the
query coordinates.

.. code-block:: php

    <?php

    foreach ($cities as $city) {
        printf("%s is %f kilometers away.\n", $city->name, $city->distance);
    }

.. _`geoNear command`: https://docs.mongodb.com/manual/reference/command/geoNear/

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
