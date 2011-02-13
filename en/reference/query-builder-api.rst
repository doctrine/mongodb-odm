Query Builder API
=================

.. role:: math(raw)
   :format: html latex

Querying for documents with Doctrine is just as simple as if you
weren't using Doctrine at all. Of course you always have your
traditional ``find()`` and ``findOne()`` methods but you also have
a ``Query`` object with a fluent API for defining the query that
should be executed.

The ``Query`` object supports four types of queries:


-  `find <#find>`_
-  `update <#update>`_
-  `remove <#remove>`_
-  `group <#group>`_

This section will show examples for the different types of
queries.

Finding Documents
-----------------

You can use the traditional ``find()`` and ``findOne()`` methods to
find documents just like you would if you weren't using Doctrine.
The only difference is that the methods return objects instead of
arrays:

.. code-block:: php

    <?php
    $users = $dm->find('User', array('type' => 'admin'));

And you can use the ``findOne()`` method to find a single user:

.. code-block:: php

    <?php
    $user = $dm->findOne('User', array('username' => 'jwage'));

Creating Query Objects
----------------------

You can easily create a new ``Query`` object with the
``DocumentManager::createQuery()`` method:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User');

The first and only argument is optional, you can specify it later
with the ``from()`` method:

.. code-block:: php

    <?php
    $query = $dm->createQuery();
    
    // ...
    
    $query->from('User');

Find Queries
------------

Find queries are the default type of query and are how you retrieve
data from Mongo either as PHP objects or the raw arrays the PHP
Mongo extension returns.

Executing Queries
~~~~~~~~~~~~~~~~~

You can execute a query with the ``execute()`` method which
executes the query, iterators the cursor and returns an array of
results:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User');
    $users = $query->execute();

Getting Single Result
~~~~~~~~~~~~~~~~~~~~~

If you want to just get a single result you can use the
``Query#getSingleResult()`` method:

.. code-block:: php

    <?php
    $user = $dm->createQuery('User')
        ->field('username')->equals('jwage')
        ->getSingleResult();

Getting Query Cursor
~~~~~~~~~~~~~~~~~~~~

If you wish to get the cursor to iterate over the results instead
of returning everything as an array you can use the ``getCursor()``
method:

.. code-block:: php

    <?php
    $cursor = $query->execute();
    
    foreach ($cursor as $document) {
        // ...
    }

The advantage of iterating over the cursor is that all results are
not hydrated into memory and stored in an array so the overall
memory footprint is lower.

Selecting Fields
~~~~~~~~~~~~~~~~

You can limit the fields that are returned in the results by using
the ``select()`` method:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User')
        ->select('username', 'password');
    $users = $query->execute();

In the results only the data from the username and password will be
returned.

Selecting Distinct Values
~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes you may want to get an array of distinct values in a
collection. You can accomplish this using the ``distinct()``
method:

.. code-block:: php

    <?php
    $ages = $dm->createQuery('User')
        ->distinct('age')
        ->execute();

The above would give you an array of all the distinct user ages!

Disabling Hydration
~~~~~~~~~~~~~~~~~~~

For find queries the results by default are hydrated and you get
document objects back instead of arrays. You can disable this and
get the raw results directly back from mongo by using the
``hydrate(false)`` method:

.. code-block:: php

    <?php
    $users = $dm->createQuery('User')
        ->hydrate(false)
        ->execute();
    print_r($users);

Limiting Results
~~~~~~~~~~~~~~~~

You can limit results similar to how you would in MySQL with a
limit and offset by using the ``limit()`` and ``skip()`` method.

Here is an example where we get the third page of blog posts when
we show twenty at a time:

.. code-block:: php

    <?php
    $blogPosts = $dm->createQuery('BlogPost')
        ->limit(20)
        ->skip(40)
        ->execute();

Sorting Results
~~~~~~~~~~~~~~~

You can sort the results similar to how you would in MySQL with an
ORDER BY command by using the ``sort()`` method:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Article')
        ->sort('createdAt', 'desc');

If you want to an additional sort you can call ``sort()`` again:

.. code-block:: php

    <?php
    $query->sort('featured', 'desc');

Map Reduce
~~~~~~~~~~

You can also run map reduced find queries using the ``Query``
object:

.. code-block:: php

    <?php
    $query = $this->dm->createQuery('Event')
        ->field('type')->equals('sale')
        ->map('function() { emit(this.userId, 1); }')
        ->reduce("function(k, vals) {
            var sum = 0;
            for (var i in vals) {
                sum += vals[i]; 
            }
            return sum;
        }");
    $results = $query->execute();

    **NOTE** When you specify a ``map()`` and ``reduce()`` operation
    the results will not be hydrated and the raw results from the map
    reduce operation will be returned.


If you just want to reduce the results using a javascript function
you can just call the ``where()`` method:

.. code-block:: php

    <?php
    $query = $this->dm->createQuery('User')
        ->where("function() { return this.type == 'admin'; }");

You can read more about the
`:math:`$where operator](http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-JavascriptExpressionsand%7B%7B%24where%7D%7D) in the Mongo docs. ## Conditional Operators The conditional operators in Mongo are available to limit the returned results through a easy to use API. Doctrine abstracts this to a fluent object oriented interface with a fluent API. Here is a list of all the conditional operation methods you can use on the `Query` object. Click the method to see a practical example: * [where($`javascript) <#where>`_
\* `in(:math:`$values)](#in) * [notIn($`values) <#notIn>`_ \*
`notEqual(:math:`$value)](#notEqual) * [greaterThan($`value) <#greaterThan>`_
\*
`greaterThanOrEq(:math:`$value)](#greaterThanOrEq) * [lessThan($`value) <#lessThan>`_
\*
`lessThanOrEq(:math:`$value)](#lessThanOrEq) * [range($`start, :math:`$end)](#range) * [size($`size) <#size>`_
\* `exists(:math:`$bool)](#exists) * [type($`type) <#type>`_ \*
`all(:math:`$values)](#all) * [mod($`mod) <#mod>`_

Query for active administrator users:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User')
        ->field('type')->equals('admin')
        ->field('active')->equals(1);

Query for articles that have some tags:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Article')
        ->field('tags.name')->in(array('tag1', 'tag2'));

Read more about the
`$in operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24in>`_
in the Mongo docs

Query for articles that do not have some tags:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Article')
        ->field('tags.name')->notIn(array('tag3'));

Read more about the
`$nin operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24nin>`_
in the Mongo docs.

::

    
    
    <?php
    $query = $dm->createQuery('User')
        ->field('type')->notEqual('admin');

Read more about the
`$ne operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24ne>`_
in the Mongo docs.

Query for accounts with an amount due greater than 30:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Account')
        ->field('amount_due')->greaterThan(30);

Query for accounts with an amount due greater than or equal to 30:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Account')
        ->field('amount_due')->greaterThanOrEq(30);

Query for accounts with an amount due less than 30:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Account')
        ->field('amount_due')->lessThan(30);

Query for accounts with an amount due less than or equal to 30:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Account')
        ->field('amount_due')->lessThanOrEq(30);

Query for accounts with an amount due between 10 and 20:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Account')
        ->field('amount_due')->range(10, 20);

Read more about
`conditional operators <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperators%3A%3C%2C%3C%3D%2C%3E%2C%3E%3D>`_
in the Mongo docs.

Query for articles with no comments:

.. code-block:: php

    <?php
    $query = $dm->createQuery('Article')
        ->field('comments')->size(0);

Read more about the
`$size operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24size>`_
in the Mongo docs.

Query for users that have a login field before it was renamed to
username:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User')
        ->field('login')->exists(true);

Read more about the
`$exists operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24exists>`_
in the Mongo docs.

Query for users that have a type field that is of integer bson
type:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User')
        ->field('type')->type('integer');

Read more about the
`$type operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24type>`_
in the Mongo docs.

Query for users that are in all the specified Groups:

.. code-block:: php

    <?php
    $query = $dm->createQuery('User')
        ->field('groups')->all(array('Group 1', 'Group 2'));

Read more about the
`$all operator <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24all>`_
in the Mongo docs.

::

    
    
    <?php
    $query = $dm->createQuery('Transaction')
        ->field('field')->mod('field', array(10, 1));

Read more about the
`:math:`$mod operator](http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperator%3A%24mod) in the Mongo docs. ## Update Queries <a name="update"></a> Doctrine also supports executing atomic update queries using the `Query` object. You can use the conditional operations in combination with the ability to change document field values atomically. You have several modifier operations available to you that make it easy to update documents in Mongo: * [set($`name, $value, :math:`$atomic = true)](#set) * [setNewObj($`newObj) <#setNewObj>`_
\*
`inc($name, :math:`$value)](#inc) * [unsetField($`field) <#unsetField>`_
\*
`push($field, :math:`$value)](#push) * [pushAll($`field, array :math:`$valueArray)](#pushAll) * [addToSet($`field, :math:`$value)](#addToSet) * [addManyToSet($`field, array :math:`$values)](#addManyToSet) * [popFirst($`field) <#popFirst>`_
\*
`popLast(:math:`$field)](#popLast) * [pull($`field, :math:`$value)](#pull) * [pullAll($`field, array $valueArray) <#pullAll>`_

Modifier Operations
-------------------

Change a users password:

.. code-block:: php

    <?php
    $dm->createQuery('User')
        ->field('password')->set('newpassword')
        ->field('username')->equals('jwage')
        ->execute();

If you want to just set the values of an entirely new object you
can do so by passing false as the third argument of ``set()`` to
tell it the update is not an atomic one:

.. code-block:: php

    <?php
    $dm->createQuery('User')
        ->field('username')->set('jwage', false)
        ->field('password')->set('password', false)
        // ... set other remaining fields
        ->field('username')->equals('jwage')
        ->execute();

Read more about the
`$set modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24set>`_
in the Mongo docs.

You can set an entirely new object to update as well:

.. code-block:: php

    <?php
    $dm->createQuery('User')
        ->setNewObj(array(
            'username' => 'jwage',
            'password' => 'password',
            // ... other fields
        ))
        ->field('username')->equals('jwage')
        ->execute();

Increment the value of a document:

.. code-block:: php

    <?php
    $dm->createQuery('Package')
        ->field('id')->equals('theid')
        ->field('downloads')->inc(1)
        ->execute();

Read more about the
`$inc modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24inc>`_
in the Mongo docs.

Unset the login field from users where the login field still
exists:

.. code-block:: php

    <?php
    $dm->createQuery('User')
        ->field('login')->unsetField()->exists(true)
        ->execute();

Read more about the
`$unset modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24unset>`_
in the Mongo docs.

Append new tag to the tags array:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->push('tag5')
        ->field('id')->equals('theid')
        ->execute();

Read more about the
`$push modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24push>`_
in the Mongo docs.

Append new tags to the tags array:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->pushAll(array('tag6', 'tag7'))
        ->field('id')->equals('theid')
        ->execute();

Read more about the
`$pushAll modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24pushAll>`_
in the Mongo docs.

Add value to array only if its not in the array already:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->addToSet('tag1')
        ->field('id')->equals('theid')
        ->execute();

Read more about the
`$addToSet modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24addToSet>`_
in the Mongo docs.

Add many values to the array only if they do not exist in the array
already:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->addManyToSet(array('tag6', 'tag7'))
        ->field('id')->equals('theid')
        ->execute();

Read more about the
`$addManyToSet modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24addManyToSet>`_
in the Mongo docs.

Remove first element in an array:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->popFirst()
        ->field('id')->equals('theid')
        ->execute();

Remove last element in an array:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->popLast()
        ->field('id')->equals('theid')
        ->execute();

Read more about the
`$pop modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24pop>`_
in the Mongo docs.

Remove all occurrences of value from array:

.. code-block:: php

    <?php
    $dm->createQuery('Article')
        ->field('tags')->pull('tag1')
        ->execute();

Read more about the
`$pull modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24pull>`_
in the Mongo docs.

::

    
    
    <?php
    $dm->createQuery('Article')
        ->field('tags')->pullAll(array('tag1', 'tag2'))
        ->execute();

Read more about the
`$pullAll modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24pullAll>`_
in the Mongo docs.

Remove Queries
--------------

In addition to updating you can also issue queries to remove
documents from a collection. It works pretty much the same way as
everything else and you can use the conditional operations to
specify which documents you want to remove.

Here is an example where we remove users who have never logged in:

.. code-block:: php

    <?php
    $dm->createQuery('User')
        ->remove()
        ->field('num_logins')->equals(0)
        ->execute();

Group Queries
-------------

The last type of supported query is a group query. It performs an
operation similar to SQL's GROUP BY command.

.. code-block:: php

    <?php
    $result = $this->dm->createQuery('Documents\User')
        ->group(array(), array('count' => 0))
        ->reduce('function (obj, prev) { prev.count++; }')
        ->field('a')->greaterThan(1)
        ->execute();

This is the same as if we were to do the group with the raw PHP
code:

.. code-block:: php

    <?php
    $reduce = 'function (obj, prev) { prev.count++; }';
    $condition = array('a' => array( '$gt' => 1));
    $result = $collection->group(array(), array('count' => 0), $reduce, $condition);


