Map Reduce
==========

The Doctrine MongoDB ODM fully supports the `map reduce`_ functionality via its
:doc:`Query Builder API <query-builder-api>`.

.. note::

    From the MongoDB manual:

    Map-reduce is a data processing paradigm for condensing large volumes of
    data into useful aggregated results. In MongoDB, map-reduce operations use
    custom JavaScript functions to map, or associate, values to a key. If a key
    has multiple values mapped to it, the operation reduces the values for the
    key to a single object.

Imagine a situation where you had an application with a document
named ``Event`` and it was related to a ``User`` document:

.. code-block:: php

    <?php

    namespace Documents;
    
    /** @Document */
    class Event
    {
        /** @Id */
        private $id;
    
        /** @ReferenceOne(targetDocument="Documents\User") */
        private $user;
    
        /** @Field(type="string") */
        private $type;
    
        /** @Field(type="date") */
        private $date;
    
        /** @Field(type="string") */
        private $description;
    
        // getters and setters
    }
    
    /** @Document */
    class User
    {
        // ...
    }

We may have a situation where we want to run a query that tells us how many
sales events each user has had. We can easily use the map reduce functionality
of MongoDB via the ODM's query builder. Here is a simple map reduce example:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Documents\User')
        ->field('type')
        ->equals('sale')
        ->map('function() { emit(this.user.$id, 1); }')
        ->reduce('function(k, vals) {
            var sum = 0;
            for (var i in vals) {
                sum += vals[i];
            }
            return sum;
        }');
    $query = $qb->getQuery();
    $results = $query->execute();

    foreach ($results as $user) {
        printf("User %s had %d sale(s).\n", $user['_id'], $user['value']);
    }

.. note::

    The query builder also has a ``finalize()`` method, which may be used to
    specify a `finalize function`_ to be executed after the reduce step.

When using map reduce with Doctrine, the results are not hydrated into objects.
Instead, the raw results are returned directly from MongoDB.

The preceding example is equivalent to executing the following command via the
PHP driver directly:

.. code-block:: php

    <?php

    $db = $mongoClient->selectDB('my_db');

    $map = new MongoCode('function() { emit(this.user.$id, 1); }');
    $reduce = new MongoCode('function(k, vals) {
        var sum = 0;
        for (var i in vals) {
            sum += vals[i]; 
        }
        return sum;
    }');

    $result = $db->command(array(
        'mapreduce' => 'events', 
        'map' => $map,
        'reduce' => $reduce,
        'query' => array('type' => 'sale'),
    ));

    foreach ($result['results'] as $user) {
        printf("User %s had %d sale(s).\n", $user['_id'], $user['value']);
    }

.. _`map reduce`: https://docs.mongodb.com/manual/core/map-reduce/
.. _`finalize function`: https://docs.mongodb.com/master/reference/command/mapReduce/#mapreduce-finalize-cmd
