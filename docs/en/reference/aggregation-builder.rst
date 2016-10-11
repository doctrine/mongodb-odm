Aggregation builder
===================

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

- `$project <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$match <https://docs.mongodb.com/manual/reference/operator/aggregation/match/>`_
- `$group <https://docs.mongodb.com/manual/reference/operator/aggregation/group/>`_
- `$lookup <https://docs.mongodb.com/manual/reference/operator/aggregation/lookup/>`_
- `$unwind <https://docs.mongodb.com/manual/reference/operator/aggregation/unwind/>`_
- `$redact <https://docs.mongodb.com/manual/reference/operator/aggregation/redact/>`_
- `$limit <https://docs.mongodb.com/manual/reference/operator/aggregation/limit/>`_
- `$skip <https://docs.mongodb.com/manual/reference/operator/aggregation/skip/>`_
- `$sort <https://docs.mongodb.com/manual/reference/operator/aggregation/project/>`_
- `$sample <https://docs.mongodb.com/manual/reference/operator/aggregation/sample/>`_
- `$geoNear <https://docs.mongodb.com/manual/reference/operator/aggregation/geoNear/>`_
- `$out <https://docs.mongodb.com/manual/reference/operator/aggregation/out/>`_
- `$indexStats <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_

.. note::

    The ``$lookup``, ``$sample`` and ``$indexStats`` stages were added in MongoDB 3.2.

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
        ->lookup('user')
            ->alias('user')
        ->unwind('$user');

MongoDB will always return an array, even if the lookup only returned a single
document. Thus, when looking up one-to-one references the result must be flattened
using the ``$unwind`` operator.

.. note::

    Due to a limitation in MongoDB, the ``$lookup`` stage can only be used with
    references that are stored as ID (see example above). References stored with
    a DbRef object can't be used. To use references in a $lookup stage, store
    the references as ID. This is explained in the
    :doc:`Reference mapping <reference-mapping>` chapter..

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

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder
        ->redact()
            ->cond(
                $builder->expr()->gte('$$level', 5),
                '$$PRUNE',
                '$$DESCEND'
            )

$sort, $limit and $skip
~~~~~~~~~~~~~~~~~~~~~~~

The ``$sort``, ``$limit`` and ``$skip`` stages behave like the corresponding
query options, allowing you to control the order and subset of results returned
by the aggregation pipeline.

$sample
~~~~~~~

The sample stage can be used to randomly select a subset of documents in the
aggregation pipeline. It behaves like the ``$limit`` stage, but instead of
returning the first ``n`` documents it returns ``n`` random documents.

$geoNear
~~~~~~~~

The ``$geoNear`` stage finds and outputs documents in order of nearest to
farthest from a specified point.

.. code-block:: php

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


$indexStats
~~~~~~~~~~~

The ``$indexStats`` stage returns statistics regarding the use of each index for
the collection. More information can be found in the `official Documentation <https://docs.mongodb.com/manual/reference/operator/aggregation/indexStats/>`_
