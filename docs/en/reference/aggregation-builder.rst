Aggregation builder
===================

.. note::
    This feature is introduced in version 1.2

The aggregation framework provides an easy way to process records and return
computed results. The aggregation builder helps to build complex aggregation
pipelines.

Creating an Aggregation Builder
-------------------------------

You can easily create a new ``Aggregation\Builder`` object with the
``DocumentManager::createAggregationBuilder()`` method:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\User::class);

The first argument indicates the document for which you want to create the
builder.

Adding pipeline stages
~~~~~~~~~~~~~~~~~~~~~~

To add a pipeline stage to the builder, call the corresponding method on the
builder object:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('purchaseDate')
            ->gte($from)
            ->lt($to)
            ->field('user')
            ->references($user)
        ->group()
            ->field('id')
            ->expression('$user')
            ->field('numPurchases')
            ->sum(1)
            ->field('amount')
            ->sum('$amount');

Just like the query builder, the aggregation builder takes care of converting
``DateTime`` objects into ``MongoDate`` objects.

Nesting expressions
~~~~~~~~~~~~~~~~~~~

You can create more complex aggregation stages by using the ``expr()`` method in
the aggregation builder.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->match()
            ->field('purchaseDate')
            ->gte($from)
            ->lt($to)
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

This aggregation would group all purchases by their month and year by projecting
those values into an embedded object for the ``id`` field. For example:

.. code-block:: json

    {
        _id: {
            month: 1,
            year: 2016
        },
        numPurchases: 1,
        amount: 27.89
    }

Executing an aggregation pipeline
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can execute a pipeline using the ``execute()`` method. This will run the
aggregation pipeline and return a cursor for you to iterate over the results:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\User::class);
    $result = $builder->execute();

If you instead want to look at the built aggregation pipeline, call the
``Builder::getPipeline()`` method.

Hydration
~~~~~~~~~

By default, aggregation results are returned as PHP arrays. This is because the
result of an aggregation pipeline may look completely different from the source
document. In order to get hydrated aggregation results, you first have to map
a ``QueryResultDocument``. These are written like regular mapped documents, but
they can't be persisted to the database.

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @QueryResultDocument */
        class UserPurchases
        {
            /** @ReferenceOne(targetDocument="User", name="_id") */
            private $user;

            /** @Field(type="int") */
            private $numPurchases;

            /** @Field(type="float") */
            private $amount;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
            <query-result-document name="Documents\UserPurchases">
                <field fieldName="numPurchases" type="int" />
                <field fieldName="amount" type="float" />
                <reference-one field="user" target-document="Documents\User" name="_id" />
            </query-result-document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\User:
          type: queryResultDocument
          fields:
            user:
              name: _id
              targetDocument: Documents\User
            numPurchases:
              type: int
            amount:
              type: float

Once you have mapped the document, use the ``hydrate()`` method to tell the
aggregation builder about this document:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->hydrate(\Documents\UserPurchases::class)
        ->match()
            ->field('purchaseDate')
            ->gte($from)
            ->lt($to)
            ->field('user')
            ->references($user)
        ->group()
            ->field('id')
            ->expression('$user')
            ->field('numPurchases')
            ->sum(1)
            ->field('amount')
            ->sum('$amount');

When you run the queries, all results will be returned as instances of the
specified document.

.. note::

    Query result documents can use all features regular documents can use: you
    can map embedded documents, define references, and even use discriminators
    to get different result documents according to the aggregation result.

Aggregation pipeline stages
---------------------------

MongoDB provides the following aggregation pipeline stages:

- `$addFields <https://docs.mongodb.com/manual/reference/operator/aggregation/addFields/>`_
- `$bucket <https://docs.mongodb.com/manual/reference/operator/aggregation/bucket/>`_
- `$bucketAuto <https://docs.mongodb.com/manual/reference/operator/aggregation/bucketAuto/>`_
- `$collStats <https://docs.mongodb.com/manual/reference/operator/aggregation/collStats/>`_
- `$count <https://docs.mongodb.com/manual/reference/operator/aggregation/count/>`_
- `$facet <https://docs.mongodb.com/manual/reference/operator/aggregation/facet/>`_
- `$geoNear <https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/>`_
- `$graphLookup <https://docs.mongodb.com/manual/reference/operator/aggregation/graphLookup/>`_
- `$group <https://docs.mongodb.com/manual/reference/operator/aggregation/group/>`_
- `$indexStats <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_
- `$limit <https://docs.mongodb.com/manual/reference/operator/aggregation/limit/>`_
- `$lookup <https://docs.mongodb.com/manual/reference/operator/aggregation/lookup/>`_
- `$match <https://docs.mongodb.com/manual/reference/operator/aggregation/match/>`_
- `$out <https://docs.mongodb.com/manual/reference/operator/aggregation/out/>`_
- `$project <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$redact <https://docs.mongodb.com/manual/reference/operator/aggregation/redact/>`_
- `$replaceRoot <https://docs.mongodb.com/manual/reference/operator/aggregation/replaceRoot/>`_
- `$sample <https://docs.mongodb.com/manual/reference/operator/aggregation/sample/>`_
- `$skip <https://docs.mongodb.com/manual/reference/operator/aggregation/skip/>`_
- `$sort <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$sortByCount <https://docs.mongodb.com/manual/reference/operator/aggregation/sortByCount/>`_
- `$unwind <https://docs.mongodb.com/manual/reference/operator/aggregation/unwind/>`_

.. note::

    The ``$lookup``, ``$sample`` and ``$indexStats`` stages were added in MongoDB
    3.2. The ``$addFields``, ``$bucket``, ``$bucketAuto``, ``$sortByCount``,
    ``$replaceRoot``, ``$facet``, ``$graphLookup``, ``$coun`` and ``$collStats``
    stages were added in MongoDB 3.4.

$addFields
~~~~~~~~~~

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

$bucket
~~~~~~~

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
~~~~~~~~~~~

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
~~~~~~~~~~

The ``$collStats`` stage returns statistics regarding a collection or view.

$count
~~~~~~

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

$facet
~~~~~~

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

$geoNear
~~~~~~~~

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
~~~~~~~~~~~~

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
~~~~~~

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
~~~~~~~~~~~

The ``$indexStats`` stage returns statistics regarding the use of each index for
the collection. More information can be found in the `official Documentation <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_

$lookup
~~~~~~~

.. note::

    The ``$lookup`` stage was introduced in MongoDB 3.2. Using it on older servers
    will result in an error.

The ``$lookup`` stage is used to fetch documents from different collections in
pipeline stages. Take the following relationship for example:

.. code-block:: php

    <?php

    /**
     * @ReferenceMany(
     *     targetDocument="Documents\Item",
     *     cascade="all",
     *     storeAs="id"
     * )
     */
    private $items;

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->lookup('items')
            ->alias('items');

The resulting array will contain all matched item documents in an array. This has
to be considered when looking up one-to-one relationships:

.. code-block:: php

    <?php

    /**
     * @ReferenceOne(
     *     targetDocument="Documents\Item",
     *     cascade="all",
     *     storeAs="id"
     * )
     */
    private $items;

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

$match
~~~~~~

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
            ->excludeIdField()
            ->includeFields(['purchaseDate', 'user'])
            ->field('purchaseYear')
            ->year('$purchaseDate')
        ->match()
            ->field('purchaseYear')
            ->equals(2016);

$out
~~~~

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
    any unique index constraints, including those on the ``id`` field.

$project
~~~~~~~~

The ``$project`` stage lets you reshape the current document or define a completely
new one:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->project()
            ->excludeIdField()
            ->includeFields(['purchaseDate', 'user'])
            ->field('purchaseYear')
            ->year('$purchaseDate');

$redact
~~~~~~~

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
            )

$replaceRoot
~~~~~~~~~~~~

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

$sample
~~~~~~~

The sample stage can be used to randomly select a subset of documents in the
aggregation pipeline. It behaves like the ``$limit`` stage, but instead of
returning the first ``n`` documents it returns ``n`` random documents.

$sort, $limit and $skip
~~~~~~~~~~~~~~~~~~~~~~~

The ``$sort``, ``$limit`` and ``$skip`` stages behave like the corresponding
query options, allowing you to control the order and subset of results returned
by the aggregation pipeline.

$sortByCount
~~~~~~~~~~~~

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

$unwind
~~~~~~~

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
