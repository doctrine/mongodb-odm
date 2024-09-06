Aggregation builder
===================

The aggregation framework provides an easy way to process records and return
computed results. The aggregation builder helps to build complex aggregation
pipelines.

Creating an Aggregation Builder
-------------------------------

You can create a new ``Aggregation\Builder`` object with the
``DocumentManager::createAggregationBuilder()`` method:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\User::class);

The first argument indicates the document for which you want to create the
builder.

Adding pipeline stages
----------------------

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
``DateTime`` objects into ``MongoDB\Driver\BSON\UTCDateTime`` objects.

Nesting expressions
-------------------

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
                    ->month('$purchaseDate')
                    ->field('year')
                    ->year('$purchaseDate')
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
---------------------------------

When you are done building your pipeline, you can build an ``Aggregation``
object using the ``getAggregation()`` method. The returning instance can yield a
single result or return an iterator containing all results.

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\User::class);
    $result = $builder->getAggregation();

If you instead want to look at the built aggregation pipeline, call the
``Builder::getPipeline()`` method.

Hydration
---------

By default, aggregation results are returned as PHP arrays. This is because the
result of an aggregation pipeline may look completely different from the source
document. In order to get hydrated aggregation results, you first have to map
a ``QueryResultDocument``. These are written like regular mapped documents, but
they can't be persisted to the database.

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[QueryResultDocument]
        class UserPurchases
        {
            #[ReferenceOne(targetDocument: User::class, name: '_id')]
            private User $user;

            #[Field(type: 'int')]
            private int $numPurchases;

            #[Field(type: 'float')]
            private float $amount;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
            <query-result-document name="Documents\UserPurchases">
                <field field-name="numPurchases" type="int" />
                <field field-name="amount" type="float" />
                <reference-one field="user" target-document="Documents\User" name="_id" />
            </query-result-document>
        </doctrine-mongo-mapping>

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

Disabling Result Caching
------------------------

Due to MongoDB cursors not being rewindable, ODM uses a caching iterator when
returning results from aggregation pipelines. This cache allows you to iterate a
result cursor multiple times without re-executing the original aggregation
pipeline. However, in long-running processes or when handling a large number of
results, this can lead to high memory usage. To disable this result cache, you
can tell the query builder to not return a caching iterator:

.. code-block:: php

    <?php

    $builder = $dm->createAggregationBuilder(\Documents\Orders::class);
    $builder->rewindable(false);

When setting this option to ``false``, attempting a second iteration will result
in an exception. Note that calling ``getAggregation()`` will always yield a
fresh aggregation instance that can be re-executed.
