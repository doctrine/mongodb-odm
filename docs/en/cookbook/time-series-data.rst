Storing Time Series Data
========================

.. note::

    Support for mapping time series data was added in ODM 2.10.

`time-series data <https://www.mongodb.com/docs/manual/core/timeseries-collections/>`__
is a sequence of data points in which insights are gained by analyzing changes
over time.

Time series data is generally composed of these components:

-
    Time when the data point was recorded

-
    Metadata, which is a label, tag, or other data that identifies a data series
    and rarely changes

-
    Measurements, which are the data points tracked at increments in time.

A time series document always contains a time value, and one or more measurement
fields. Metadata is optional, but cannot be added to a time series collection
after creating it. When using an embedded document for metadata, fields can be
added to this document after creating the collection.

.. note::

    Support for time series collections was added in MongoDB 5.0. Attempting to
    use this functionality on older server versions will result in an error on
    schema creation.

Creating The Model
------------------

For this example, we'll be storing data from multiple sensors measuring
temperature and humidity. Other examples for time series include stock data,
price information, website visitors, or vehicle telemetry (speed, position,
etc.).

First, we define the model for our data:

.. code-block:: php

    <?php

    use DateTimeImmutable;
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use MongoDB\BSON\ObjectId;

    #[ODM\Document]
    readonly class Measurement
    {
        #[ODM\Id]
        public string $id;

        public function __construct(
            #[ODM\Field(type: 'date_immutable')]
            public DateTimeImmutable $time,
            #[ODM\Field(type: 'int')]
            public int $sensorId,
            #[ODM\Field(type: 'float')]
            public float $temperature,
            #[ODM\Field(type: 'float')]
            public float $humidity,
        ) {
            $this->id = (string) new ObjectId();
        }
    }

Note that we defined the entire model as readonly. While we could theoretically
change values in the document, in this example we'll assume that the data will
not change.

Now we can mark the document as a time series document. To do so, we use the
``TimeSeries`` attribute, configuring appropriate values for the time and
metadata field, which in our case stores the ID of the sensor reporting the
measurement:

.. code-block:: php

    <?php

    // ...

    #[ODM\Document]
    #[ODM\TimeSeries(timeField: 'time', metaField: 'sensorId')]
    readonly class Measurement
    {
        // ...
    }

Once we create the schema, we can store our measurements in this time series
collection and let MongoDB optimize the storage for faster queries:

.. code-block:: php

    <?php

    $measurement = new Measurement(
        time: new DateTimeImmutable(),
        sensorId: $sensorId,
        temperature: $temperature,
        humidity: $humidity,
    );

    $documentManager->persist($measurement);
    $documentManager->flush();

Note that other functionality such as querying, using aggregation pipelines, or
removing data works the same as with other collections.

Considerations
--------------

With the mapping above, data is stored with a granularity of seconds. Depending
on how often measurements come in, we can reduce the granularity to minutes or
hours. This changes how the data is stored internally by changing the bucket
size. This affects storage requirements and query performance.

For example, with the default ``seconds`` granularity, each bucket groups
documents for one hour. If each sensor only reports data every few minutes, we'd
do well to configure ``minute`` granularity. This reduces the
number of buckets created, reducing storage and making queries more efficient.
However, if we were to choose ``hours`` for granularity, readings for a whole
month would be grouped into one bucket, resulting in slower queries as more
entries have to be traversed when reading data.

More details on granularity and other consideration scan be found in the
`MongoDB documentation <https://www.mongodb.com/docs/manual/core/timeseries/timeseries-considerations/>`__.
