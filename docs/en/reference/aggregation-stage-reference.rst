Aggregation pipeline stages
===========================

Doctrine MongoDB ODM provides integration for the following aggregation pipeline stages:

- `$addFields <https://docs.mongodb.com/manual/reference/operator/aggregation/addFields/>`_
- `$bucket <https://docs.mongodb.com/manual/reference/operator/aggregation/bucket/>`_
- `$bucketAuto <https://docs.mongodb.com/manual/reference/operator/aggregation/bucketAuto/>`_
- `$collStats <https://docs.mongodb.com/manual/reference/operator/aggregation/collStats/>`_
- `$count <https://docs.mongodb.com/manual/reference/operator/aggregation/count/>`_
- `$densify <https://docs.mongodb.com/manual/reference/operator/aggregation/densify/>`_
- `$facet <https://docs.mongodb.com/manual/reference/operator/aggregation/facet/>`_
- `$fill <https://docs.mongodb.com/manual/reference/operator/aggregation/fill/>`_
- `$geoNear <https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/>`_
- `$graphLookup <https://docs.mongodb.com/manual/reference/operator/aggregation/graphLookup/>`_
- `$group <https://docs.mongodb.com/manual/reference/operator/aggregation/group/>`_
- `$indexStats <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_
- `$limit <https://docs.mongodb.com/manual/reference/operator/aggregation/limit/>`_
- `$lookup <https://docs.mongodb.com/manual/reference/operator/aggregation/lookup/>`_
- `$match <https://docs.mongodb.com/manual/reference/operator/aggregation/match/>`_
- `$merge <https://docs.mongodb.com/manual/reference/operator/aggregation/merge/>`_
- `$out <https://docs.mongodb.com/manual/reference/operator/aggregation/out/>`_
- `$project <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$redact <https://docs.mongodb.com/manual/reference/operator/aggregation/redact/>`_
- `$replaceRoot <https://docs.mongodb.com/manual/reference/operator/aggregation/replaceRoot/>`_
- `$replaceWith <https://docs.mongodb.com/manual/reference/operator/aggregation/replaceWith/>`_
- `$sample <https://docs.mongodb.com/manual/reference/operator/aggregation/sample/>`_
- `$search <https://www.mongodb.com/docs/atlas/atlas-search/query-syntax/#-search>`_
- `$set <https://docs.mongodb.com/manual/reference/operator/aggregation/set/>`_
- `$setWindowFields <https://docs.mongodb.com/manual/reference/operator/aggregation/setWindowFields/>`_
- `$skip <https://docs.mongodb.com/manual/reference/operator/aggregation/skip/>`_
- `$sort <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$sortByCount <https://docs.mongodb.com/manual/reference/operator/aggregation/sortByCount/>`_
- `$unionWith <https://docs.mongodb.com/manual/reference/operator/aggregation/unionWith/>`_
- `$unset <https://docs.mongodb.com/manual/reference/operator/aggregation/unset/>`_
- `$unwind <https://docs.mongodb.com/manual/reference/operator/aggregation/unwind/>`_

.. note::

    Support for ``$densify``, ``$fill``, ``$merge``, ``$replaceWith``,
    ``$search``, ``$set``, ``$setWindowFields``, ``$unionWith``, and ``$unset``
    was added in Doctrine MongoDB ODM 2.6. Please consult the MongoDB
    documentation to ensure that the pipeline stage is available in the MongoDB
    version you are using.

$addFields
----------

Adds new fields to documents. ``$addFields`` outputs documents that contain all
existing fields from the input documents and newly added fields.

The ``$addFields`` stage is equivalent to a ``$project`` stage that explicitly
specifies all existing fields in the input documents and adds the new fields.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->addFields()
            ->field('purchaseYear')
            ->year('$purchaseDate');

You can also pass expressions as arrays:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->addFields()
            ->field('purchaseYear')
            ->expression(['$year' => '$purchaseDate'])
            ->field('multiply')
            ->expression(['$multiply' => ['$price', 2 ] ]);

This allows usage of any expression operators introduced by MongoDB, even
if Doctrine ODM does not yet wrap it with convenience methods.

You can see all available expression operators at MongoDB documentation
`here <https://docs.mongodb.com/manual/reference/operator/aggregation/>`_.

$bucket
-------

Categorizes incoming documents into groups, called buckets, based on a specified
expression and bucket boundaries.

Each bucket is represented as a document in the output. The document for each
bucket contains an _id field, whose value specifies the inclusive lower bound of
the bucket and a count field that contains the number of documents in the bucket.
The count field is included by default when the output is not specified.

``$bucket`` only produces output documents for buckets that contain at least one
input document.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->bucket()
            ->groupBy('$itemCount')
            ->boundaries(1, 2, 3, 4, 5, '5+')
            ->defaultBucket('5+')
            ->output()
                ->field('lowestValue')
                ->min('$value')
                ->field('highestValue')
                ->max('$value')
    ;

$bucketAuto
-----------

Similar to ``$bucket``, except that boundaries are automatically determined in
an attempt to evenly distribute the documents into the specified number of
buckets.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->bucketAuto()
            ->groupBy('$itemCount')
            ->buckets(5)
            ->output()
                ->field('lowestValue')
                ->min('$value')
                ->field('highestValue')
                ->max('$value')
    ;

$collStats
----------

The ``$collStats`` stage returns statistics regarding a collection or view.

$count
------

Returns a document that contains a count of the number of documents input to the
stage.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('itemCount')
            ->eq(1)
        ->count('numSingleItemOrders')
    ;

The example above returns a single document with the ``numSingleItemOrders``
containing the number of orders found.

$densify
--------

Creates new documents in a sequence of documents where certain values in a
field are missing. You can use ``$densify`` to fill gaps in time series data,
add missing values between groups of data, or to populate your data with a
specified range of values. Taking the partition example from the
`$densify documentation <https://www.mongodb.com/docs/manual/reference/operator/aggregation/densify/#densifiction-with-partitions>`_,
this is how you would create the pipeline from the example with the aggregation
builder:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Coffee::class);
    $builder
        ->densify()
            ->field('altitude')
            ->partitionByFields('variety')
            ->range('full', 200)
    ;

$facet
------

Processes multiple aggregation pipelines within a single stage on the same set
of input documents. Each sub-pipeline has its own field in the output document
where its results are stored as an array of documents.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->facet()
            ->field('groupedByItemCount')
            ->pipeline(
                $dm->createAggregationBuilder(\Documents\Orders::class)->group()
                    ->field('id')
                    ->expression('$itemCount')
                    ->field('lowestValue')
                    ->min('$value')
                    ->field('highestValue')
                    ->max('$value')
                    ->field('totalValue')
                    ->sum('$value')
                    ->field('averageValue')
                    ->avg('$value')
            )
            ->field('groupedByYear')
            ->pipeline(
                $dm->createAggregationBuilder(\Documents\Orders::class)->group()
                    ->field('id')
                    ->year('purchaseDate')
                    ->field('lowestValue')
                    ->min('$value')
                    ->field('highestValue')
                    ->max('$value')
                    ->field('totalValue')
                    ->sum('$value')
                    ->field('averageValue')
                    ->avg('$value')
            )
    ;

$fill
-----

The ``$fill`` stage populates ``null`` and missing field values within documents.
You can use ``$fill`` to populate missing data points in a sequence based on
surrounding values, or with a fixed value.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\StockPrice::class);
    $builder
        ->fill()
            ->sortBy('time', 1)
            ->output()
                ->field('price')->linear()
    ;

For each field in the output, you can use ``linear`` to use linear interpolation
based on the surrounding values, ``locf`` to carry forward the last observed
value, or ``value`` to specify an expression that returns the value for the field:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\StockPrice::class);
    $builder
        ->fill()
            ->sortBy('time', 1)
            ->output()
                ->field('interpolated')->linear()
                ->field('lastValue')->locf()
                ->field('fixedValue')->value('foo')
                ->field('computedValue')->value(
                    $builder->expr()->multiply('$someField', 5),
                )
    ;

$geoNear
--------

The ``$geoNear`` stage finds and outputs documents in order of nearest to
farthest from a specified point.

.. code-block:: php

    <?php

    $builder = $this->dm->createAggregationBuilder(\Documents\City::class);
    $builder
        ->geoNear(120, 40)
        ->spherical(true)
        ->distanceField('distance')
        // Convert radians to kilometers (use 3963.192 for miles)
        ->distanceMultiplier(6378.137);

.. note::

    The ``$geoNear`` stage must be the first stage in the pipeline and the
    collection must contain a single geospatial index. You must include the
    ``distanceField`` option for the stage to work.

$graphLookup
------------

Performs a recursive search on a collection, with options for restricting the
search by recursion depth and query filter. The ``$graphLookup`` stage can be
used to resolve association graphs and flatten them into a single list.

.. code-block:: php

    <?php

    $builder = $this->dm->createAggregationBuilder(\Documents\Traveller::class);
    $builder
        ->graphLookup('nearestAirport')
            ->connectFromField('connections')
            ->maxDepth(2)
            ->depthField('numConnections')
            ->alias('destinations');

.. note::

    The target document of the reference used in ``connectFromField`` must be
    the very same document. The aggregation builder will throw an exception if
    you try to resolve a different document.

.. note::

    Due to a limitation in MongoDB, the ``$graphLookup`` stage can not be used
    with references that are stored as DBRef. To use references in a
    ``$graphLookup`` stage, store the reference as ID or ``ref``. This is
    explained in the :doc:`Reference mapping <reference-mapping>` chapter.

.. _aggregation_builder_group:

$group
------

The ``$group`` stage is used to do calculations based on previously matched
documents:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('user')
            ->references($user)
        ->group()
            ->field('id')
            ->expression(
                $builder->expr()
                    ->field('month')
                    ->month('purchaseDate')
                    ->field('year')
                    ->year('purchaseDate')
            )
            ->field('numPurchases')
            ->sum(1)
            ->field('amount')
            ->sum('$amount');

$indexStats
-----------

The ``$indexStats`` stage returns statistics regarding the use of each index for
the collection. More information can be found in the `official Documentation <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_

$lookup
-------

.. note::

    The ``$lookup`` stage was introduced in MongoDB 3.2. Using it on older servers
    will result in an error.

The ``$lookup`` stage is used to fetch documents from different collections in
pipeline stages. Take the following relationship for example:

.. code-block:: php

    <?php

    namespace Documents;

    class Orders
    {
        /** @var Collection<Item> */
        #[ReferenceMany(
            targetDocument: Item::class,
            cascade: 'all',
            storeAs: 'id',
        )]
        private Collection $items;
    }

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->lookup('items')
            ->alias('items');

In MongoDB 3.2, the resulting array will be empty for a one-to-many relationship,
you need to unwind your field at first and use a group stage afterwards.

The resulting array will contain all matched item documents in an array. This has
to be considered when looking up one-to-one relationships:

.. code-block:: php

    <?php

    namespace Documents;

    class Orders
    {
        #[ReferenceOne(
            targetDocument: Item::class,
            cascade: 'all',
            storeAs: 'id',
        )]
        private Item $items;
    }

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->lookup('user')
            ->alias('user')
        ->unwind('$user');

MongoDB will always return an array, even if the lookup only returned a single
document. Thus, when looking up one-to-one references the result must be flattened
using the ``$unwind`` operator.

Looking up  a reference nested in an embedded document (like ``->lookup('embedDoc.refDocs')``)
is not supported. You'll need to make your lookup as if your Reference was not mapped
See below for more.

.. note::

    Due to a limitation in MongoDB, the ``$lookup`` stage can not be used with
    references that are stored as DBRef. To use references in a ``$lookup``
    stage, store the reference as ID or ``ref``. This is explained in the
    :doc:`Reference mapping <reference-mapping>` chapter.

You can also configure your lookup manually if you don't have it mapped in your
document:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->lookup('unmappedCollection')
            ->localField('_id')
            ->foreignField('userId')
            ->alias('items');

To learn how to load references in embedded documents using the ``$lookup``
stage, see the :doc:`Loading references with Lookup cookbook <../cookbook/lookup-reference>`.

$match
------

The ``$match`` stage lets you filter documents according to certain criteria. It
works just like the query builder:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('purchaseDate')
            ->gte($from)
            ->lt($to)
            ->field('user')
            ->references($user);

You can also use fields defined in previous stages:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->project()
            ->excludeFields(['_id'])
            ->includeFields(['purchaseDate', 'user'])
            ->field('purchaseYear')
            ->year('$purchaseDate')
        ->match()
            ->field('purchaseYear')
            ->equals(2016);

$merge
------

The ``$merge`` stage is used to write the results of an aggregation pipeline to
a collection. Unlike the ``$out`` stage, this stage does not replace the entire
output collection, but lets you define how to handle conflicts or missing data
in the output collection. ``$merge`` must be the last stage in an aggregation
pipeline.

The following pipeline uses the ``$merge`` pipeline stage to aggregate orders
that were created after the last aggregation run (tracked separately in the
``$lastAggregateRunAt`` variable) and updates the ``monthlyOrderStats``
collection to account for latest data.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('purchaseDate')->gte($lastAggregateRunAt)
        ->group()
            ->field('_id')
            ->expression(
                $builder->expr()
                    ->field('month')
                    ->month('purchaseDate')
                    ->field('year')
                    ->year('purchaseDate')
            )
            ->field('count')->countDocuments()
            ->field('totalAmount')->sum('$amount')
        ->set()
            ->field('year')->value('$_id.year')
            ->field('month')->value('$_id.month')
        ->unset('_id')
        ->merge()
            ->into('monthlyOrderStats')
            ->on('year', 'month')
            ->whenMatched('replace')
            ->whenNotMatched('insert')
    ;

The ``on`` builder method tells the ``merge`` stage which fields to use to match
documents in the output collection. The output collection needs to have a unique
index on the fields specified in the ``on`` method. The ``whenMatched`` and
``whenNotMatched`` methods define how to handle conflicts or missing data in the
output collection. For more information on the available options, see the
MongoDB documentation.

$out
----

The ``$out`` stage is used to store the result of the aggregation pipeline in a
collection instead of returning an iterable cursor of results. This must be the
last stage in an aggregation pipeline.

If the collection specified by the ``$out`` operation already exists, then upon
completion of the aggregation, the existing collection is atomically replaced.
Any indexes that existed on the collection are left intact. If the aggregation
fails, the ``$out`` operation does not remove the data from an existing
collection.

.. note::

    The aggregation pipeline will fail to complete if the result would violate
    any unique index constraints, including those on the ``_id`` field.

$project
--------

The ``$project`` stage lets you reshape the current document or define a completely
new one:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->project()
            ->excludeFields(['_id'])
            ->includeFields(['purchaseDate', 'user'])
            ->field('purchaseYear')
            ->year('$purchaseDate');

$redact
-------

The redact stage can be used to restrict the contents of the documents based on
information stored in the documents themselves. You can read more about the
``$redact`` stage in the `MongoDB documentation <https://docs.mongodb.com/manual/reference/operator/aggregation/redact/>`_.

The following example taken from the official documentation checks the ``level``
field on all document levels and evaluates it to grant or deny access:

.. code-block:: json

    {
        _id: 1,
        level: 1,
        acct_id: "xyz123",
        cc: {
            level: 5,
            type: "yy",
            num: 000000000000,
            exp_date: ISODate("2015-11-01T00:00:00.000Z"),
            billing_addr: {
                level: 5,
                addr1: "123 ABC Street",
                city: "Some City"
            },
            shipping_addr: [
                {
                    level: 3,
                    addr1: "987 XYZ Ave",
                    city: "Some City"
                },
                {
                    level: 3,
                    addr1: "PO Box 0123",
                    city: "Some City"
                }
            ]
        },
        status: "A"
    }

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->redact()
            ->cond(
                $builder->expr()->gte('$$level', 5),
                '$$PRUNE',
                '$$DESCEND'
            );

$replaceRoot
------------

Promotes a specified document to the top level and replaces all other fields.
The operation replaces all existing fields in the input document, including the
``_id`` field. You can promote an existing embedded document to the top level,
or create a new document for promotion.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->replaceRoot('$embeddedField');

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->replaceRoot()
            ->field('averagePricePerItem')
            ->divide('$value', '$itemCount');

$replaceWith
------------

Replaces the input document with the specified document. This stage is an alias
for the ``$replaceRoot`` stage.

$sample
-------

The sample stage can be used to randomly select a subset of documents in the
aggregation pipeline. It behaves like the ``$limit`` stage, but instead of
returning the first ``n`` documents it returns ``n`` random documents.

$search
-------

The ``$search`` stage performs a full-text search on the specified field or
fields which must be covered by an Atlas Search index. This stage is only
available when using MongoDB Atlas. ``$search`` must be the first stage in the
aggregation pipeline.

The following example documents basic usage of the ``$search`` stage. Due to the
number of available operators, please refer to the
`MongoDB documentation <https://www.mongodb.com/docs/atlas/atlas-search/query-syntax/#-search>`_
for a reference of all available operators.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\BlogPosts::class);
    $builder
        ->search()
            ->text()
                ->query('MongoDB', 'ODM', 'Aggregation')
                ->fields('title', 'content')
    ;

$set
----

Adds new fields to documents. The ``$set`` stage is an alias for the
``$addFields`` stage.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->set()
            ->field('purchaseYear')
            ->year('$purchaseDate');

$setWindowFields
----------------

The ``$setWindowFields`` performs operations on a specified span of documents in
a collection and returns the results based on the chosen window operator. For
example, ``$setWindowFields`` can be used to calculate the difference in a value
between two documents in a collection.

The following example uses the ``$setWindowFields`` stage to obtain a cumulative
sales quantity for each year:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\InfectionNumbers::class);
    $builder
        ->setWindowFields()
            ->partitionBy($builder->expr()->year('$purchaseDate'))
            ->sortBy('purchaseDate', 1)
            ->output()
                ->field('cumulativeQuantityForYear')
                    ->sum('$quantity')
                    ->window(['unbounded', 'current'])
    ;

$sort, $limit and $skip
-----------------------

The ``$sort``, ``$limit`` and ``$skip`` stages behave like the corresponding
query options, allowing you to control the order and subset of results returned
by the aggregation pipeline.

$sortByCount
------------

Groups incoming documents based on the value of a specified expression, then
computes the count of documents in each distinct group.

Each output document contains two fields: an _id field containing the distinct
grouping value, and a count field containing the number of documents belonging
to that grouping or category.

The documents are sorted by count in descending order.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder->sortByCount('$items');

The example above is equivalent to the following pipeline:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->group()
            ->field('_id')
            ->expression('$items')
            ->field('count')
            ->sum(1)
        ->sort(['count' => -1])
    ;

$unionWith
----------

``$unionWith`` combines the results of two or more pipelines into a single
result set. The stage outputs the combined result set (including duplicates) to
the next stage.

.. code-block:: php

    <?php

    // Create a pipeline to apply within the union
    $unionBuilder = $dm->createAggregationBuilder(\Documents\Warehouse::class);
    $unionBuilder
        ->project()
            ->excludeFields(['_id'])
            ->includeFields(['location']);

    $builder = $dm->createAggregationBuilder(\Documents\Supplier::class);
    $builder
        ->project()
            ->excludeFields(['_id'])
            ->includeFields(['location'])
        ->unionWith(\Documents\Warehouse::class)
            // Directly filter documents from the unioned collection
            ->pipeline($unionBuilder)
    ;

$unset
------

Removes fields from documents. The ``$unset`` stage is an alias for the
``$project`` stage that removes fields.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->unset('customer', 'shippingAddress.street', 'billingAddress.street')
    ;

The above example is equivalent to the following pipeline using ``$project``:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->project()
            ->excludeFields(['customer', 'shippingAddress.street', 'billingAddress.street'])
    ;


$unwind
-------

The ``$unwind`` stage flattens an array in a document, returning a copy for each
item. Take this sample document:

.. code-block:: json

    {
        _id: {
            month: 1,
            year: 2016
        },
        purchaseDates: [
            '2016-01-07',
            '2016-03-10',
            '2016-06-25'
        ]
    }

To flatten the ``purchaseDates`` array, we would apply the following pipeline
stage:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\User::class);
    $builder->unwind('$purchaseDates');

The stage would return three documents, each containing a single purchase date:

.. code-block:: json

    {
        _id: {
            month: 1,
            year: 2016
        },
        purchaseDates: '2016-01-07'
    },
    {
        _id: {
            month: 1,
            year: 2016
        },
        purchaseDates: '2016-03-10'
    },
    {
        _id: {
            month: 1,
            year: 2016
        },
        purchaseDates: '2016-06-25'
    }
