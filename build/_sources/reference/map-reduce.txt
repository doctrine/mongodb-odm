Map Reduce
==========

The Doctrine MongoDB Object Document Mapper fully supports the map
reduce functionality and improves the user friendliness of it as
well.

.. note::

    From MongoDB.org:

    Map/reduce in MongoDB is useful for batch manipulation of data and
    aggregation operations. It is similar in spirit to using something
    like Hadoop with all input coming from a collection and output
    going to a collection. Often, in a situation where you would have
    used GROUP BY in SQL, map/reduce is the right tool in MongoDB.

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
    
        /** @String */
        private $type;
    
        /** @Date */
        private $date;
    
        /** @String */
        private $description;
    
        // getters and setters
    }
    
    /** @Document */
    class User
    {
        // ...
    }

We may have a situation where we want to run a query that tells us
how many sales events each user has had. We can easily use the map
reduce functionality of MongoDB which is tightly integrated with
the ``Query`` API. Here is a simple map reduce example:

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
    $users = $query->execute();
    foreach ($users as $user) {
        echo "{$user['_id']} had {$user['value']} sale(s).\n";
    }

When using map reduce with Doctrine, the results are not hydrated
into objects and the raw results are returned directly from
MongoDB.

Here is the exact same example we provided above except done with
raw PHP code without the use of Doctrine:

.. code-block:: php

    <?php

    $db = $mongo->selectDB('my_db');
    
    $map = new MongoCode('function() { emit(this.user.$id, 1); }');
    $reduce = new MongoCode('function(k, vals) {
        var sum = 0;
        for (var i in vals) {
            sum += vals[i]; 
        }
        return sum;
    }');
    
    $sales = $db->command(array(
        'mapreduce' => 'events', 
        'map' => $map,
        'reduce' => $reduce,
        'query' => array('type' => 'sale')));
    
    $users = $db->selectCollection($sales['result'])->find();
    
    foreach ($users as $user) {
        echo "{$user['_id']} had {$user['value']} sale(s).\n";
    }

Your reduce function could return any type of variables, if you
rewrite reduce as follows:

.. code-block:: php

    <?php

    //...

    $reduce = new MongoCode('function(k, vals) {
        var sum = 0;
        for (var i in vals) {
            sum += vals[i]; 
        }
        return { user_id: k, sum: sum };
    }');
    //...
    foreach ($users as $user) {
        echo "{$user['value']['user_id']} had {$user['value']['sum']} sale(s).\n";
    }