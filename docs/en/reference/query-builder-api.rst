Query Builder API
=================

.. role:: math(raw)
   :format: html latex

Querying for documents with Doctrine is just as simple as if you
weren't using Doctrine at all. Of course you always have your
traditional ``find()`` and ``findOne()`` methods but you also have
a ``Query`` object with a fluent API for defining the query that
should be executed.

The ``Query`` object supports several types of queries

- FIND
- FIND_AND_UPDATE
- FIND_AND_REMOVE
- INSERT
- UPDATE
- REMOVE
- GROUP
- MAP_REDUCE
- DISTINCT_FIELD
- GEO_LOCATION

This section will show examples for the different types of queries.

Finding Documents
-----------------

You have a few different ways to find documents. You can use the ``find()`` method
to find a document by its identifier:

.. code-block:: php

    <?php

    $users = $dm->find('User', $id);

The ``find()`` method is just a convenience shortcut method to:

.. code-block:: php

    <?php

    $user = $dm->getRepository('User')->find($id);

.. note::

    The ``find()`` method checks the local in memory identity map for the document
    before querying the database for the document.

On the ``DocumentRepository`` you have a few other methods for finding documents:

- ``findBy`` - find documents by an array of criteria
- ``findOneBy`` - find one document by an array of criteria

.. code-block:: php

    <?php

    $users = $dm->getRepository('User')->findBy(array('type' => 'employee'));
    $user = $dm->getRepository('User')->findOneBy(array('username' => 'jwage'));

Creating a Query Builder
------------------------

You can easily create a new ``Query\Builder`` object with the
``DocumentManager::createQueryBuilder()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User');

The first and only argument is optional, you can specify it later
with the ``find()``, ``update()`` or ``remove()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder();
    
    // ...
    
    $qb->find('User');

Executing Queries
~~~~~~~~~~~~~~~~~

You can execute a query by getting a ``Query`` through the ``getQuery()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User');
    $query = $qb->getQuery();

Now you can ``execute()`` that query and it will return a cursor for you to iterator over the results:

.. code-block:: php

    <?php

    $users = $query->execute();

Debugging Queries
~~~~~~~~~~~~~~~~~

While building not complicated queries is really simple sometimes it might be hard to wrap your head
around more sophisticated queries that involves building separate expressions to work properly. If
you are not sure if your the query constructed with Builder is in fact correct you may want to ``debug()`` it

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User');
    $query = $qb->getQuery();
    $debug = $query->debug();

At this point your query is *prepared* - that means ODM done all its job in renaming fields to match their
database name, added discriminator fields, applied filters, created correct references and all other things
you employ ODM to. The array returned by ``->debug()`` is what is passed to the underlying driver for the
query to be performed.

Eager Cursors
~~~~~~~~~~~~~

You can configure queries to return an eager cursor instead of a normal mongodb cursor using the ``Builder#eagerCursor()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->eagerCursor(true);
    $query = $qb->getQuery();
    $cursor = $query->execute(); // instanceof Doctrine\ODM\MongoDB\EagerCursor

Iterating over the ``$cursor`` will fetch all the data in a short and small cursor all at once and will hydrate
one document at a time in to an object as you iterate:

.. code-block:: php

    <?php

    foreach ($cursor as $user) { // queries for all users and data is held internally
        // each User object is hydrated from the data one at a time.
    }

Getting Single Result
~~~~~~~~~~~~~~~~~~~~~

If you want to just get a single result you can use the ``Query#getSingleResult()`` method:

.. code-block:: php

    <?php

    $user = $dm->createQueryBuilder('User')
        ->field('username')->equals('jwage')
        ->getQuery()
        ->getSingleResult();

Selecting Fields
~~~~~~~~~~~~~~~~

You can limit the fields that are returned in the results by using
the ``select()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->select('username', 'password');
    $query = $qb->getQuery();
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

    $ages = $dm->createQueryBuilder('User')
        ->distinct('age')
        ->getQuery()
        ->execute();

The above would give you an ``ArrayCollection`` of all the distinct user ages!

.. note::

    MongoDB's `distinct command <https://docs.mongodb.com/manual/reference/command/distinct/>`_
    does not support sorting, so you cannot combine ``distinct()`` with
    ``sort()``. If you would like to sort the results of a distinct query, you
    will need to do so in PHP after executing the query.

Refreshing Documents
~~~~~~~~~~~~~~~~~~~~

When a query (e.g. geoNear, find) returns one or more hydrated documents whose
identifiers are already in the identity map, ODM returns the managed document
instances for those results. In this case, a managed document's data may differ
from whatever was just returned by the database query.

The query builder's ``refresh()`` method may be used to instruct ODM to override
the managed document with data from the query result. This is comparable to
calling ``DocumentManager::refresh()`` for a managed document. The document's
changeset will be reset in the process.

.. code-block:: php

    <?php

    $user = $dm->createQueryBuilder('User')
        ->field('username')->equals('jwage')
        ->refresh()
        ->getQuery()
        ->getSingleResult();

    // Jon's user will have the latest data, even if it was already managed

Refreshing is not applicable if hydration is disabled.

Fetching Documents as Read-Only
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Similar to ``refresh()``, ``readOnly()`` instructs ODM to not only hydrate the
latest data but also to create new document's instance (i.e. if found document
would be already managed by Doctrine, new instance will be returned) and not
register it in ``UnitOfWork``.

This technique can prove especially useful when using ``select()`` with no intent
to update fetched documents.

.. code-block:: php

    <?php

    $user = $dm->createQueryBuilder('User')
        ->field('username')->equals('malarzm')
        ->readOnly()
        ->getQuery()
        ->getSingleResult();

    // Maciej's user will have the latest data, and will not be the same object
    // as the one that was already managed (if it was)

Read-Only is not applicable if hydration is disabled.

.. note::

    Read-only mode is not deep, i.e. any references (be it owning or inverse) of
    fetched WILL be managed by Doctrine. This is a shortcoming of current
    implementation, may change in future and will not be considered a BC break
    (will be treated as a feature instead).

.. note::

    To manage a document previously fetched in read-only mode, always use the
    `merge` method of the DocumentManager. Using `persist` in these cases can
    have unwanted side effects.

Disabling Hydration
~~~~~~~~~~~~~~~~~~~

For find queries the results by default are hydrated and you get
document objects back instead of arrays. You can disable this and
get the raw results directly back from mongo by using the
``hydrate(false)`` method:

.. code-block:: php

    <?php

    $users = $dm->createQueryBuilder('User')
        ->hydrate(false)
        ->getQuery()
        ->execute();

    print_r($users);

Limiting Results
~~~~~~~~~~~~~~~~

You can limit results similar to how you would in a relational
database with a limit and offset by using the ``limit()`` and
``skip()`` method.

Here is an example where we get the third page of blog posts when
we show twenty at a time:

.. code-block:: php

    <?php

    $blogPosts = $dm->createQueryBuilder('BlogPost')
        ->limit(20)
        ->skip(40)
        ->getQuery()
        ->execute();

Sorting Results
~~~~~~~~~~~~~~~

You can sort the results by using the ``sort()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Article')
        ->sort('createdAt', 'desc');

If you want to an additional sort you can call ``sort()`` again. The calls are stacked and ordered
in the order you call the method:

.. code-block:: php

    <?php

    $query->sort('featured', 'desc');

Map Reduce
~~~~~~~~~~

You can also run map reduced find queries using the ``Query``
object:

.. code-block:: php

    <?php

    $qb = $this->dm->createQueryBuilder('Event')
        ->field('type')->equals('sale')
        ->map('function() { emit(this.userId, 1); }')
        ->reduce("function(k, vals) {
            var sum = 0;
            for (var i in vals) {
                sum += vals[i]; 
            }
            return sum;
        }");
    $query = $qb->getQuery();
    $results = $query->execute();

.. note::

    When you specify a ``map()`` and ``reduce()`` operation
    the results will not be hydrated and the raw results from the map
    reduce operation will be returned.

If you just want to reduce the results using a javascript function
you can just call the ``where()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->where("function() { return this.type == 'admin'; }");

You can read more about the `$where operator <https://docs.mongodb.com/manual/reference/operator/query/where/>`_ in the Mongo docs.

Conditional Operators
~~~~~~~~~~~~~~~~~~~~~

The conditional operators in Mongo are available to limit the returned results through a easy to use API. Doctrine abstracts this to a fluent object oriented interface with a fluent API. Here is a list of all the conditional operation methods you can use on the `Query\Builder` object.

* ``where($javascript)``
* ``in($values)``
* ``notIn($values)``
* ``equals($value)``
* ``notEqual($value)``
* ``gt($value)``
* ``gte($value)``
* ``lt($value)``
* ``lte($value)``
* ``range($start, $end)``
* ``size($size)``
* ``exists($bool)``
* ``type($type)``
* ``all($values)``
* ``mod($mod)``
* ``addOr($expr)``
* ``references($document)``
* ``includesReferenceTo($document)``

Query for active administrator users:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('type')->equals('admin')
        ->field('active')->equals(true);

Query for articles that have some tags:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Article')
        ->field('tags.name')->in(array('tag1', 'tag2'));

Read more about the
`$in operator <https://docs.mongodb.com/manual/reference/operator/query/in/>`_
in the Mongo docs

Query for articles that do not have some tags:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Article')
        ->field('tags.name')->notIn(array('tag3'));

Read more about the
`$nin operator <https://docs.mongodb.com/manual/reference/operator/query/nin/>`_
in the Mongo docs.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('type')->notEqual('admin');

Read more about the
`$ne operator <https://docs.mongodb.com/manual/reference/operator/query/ne/>`_
in the Mongo docs.

Query for accounts with an amount due greater than 30:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Account')
        ->field('amount_due')->gt(30);

Query for accounts with an amount due greater than or equal to 30:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Account')
        ->field('amount_due')->gte(30);

Query for accounts with an amount due less than 30:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Account')
        ->field('amount_due')->lt(30);

Query for accounts with an amount due less than or equal to 30:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Account')
        ->field('amount_due')->lte(30);

Query for accounts with an amount due between 10 and 20:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Account')
        ->field('amount_due')->range(10, 20);

Read more about
`conditional operators <http://www.mongodb.org/display/DOCS/Advanced+Queries#AdvancedQueries-ConditionalOperators%3A%3C%2C%3C%3D%2C%3E%2C%3E%3D>`_
in the Mongo docs.

Query for articles with no comments:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('Article')
        ->field('comments')->size(0);

Read more about the
`$size operator <https://docs.mongodb.com/manual/reference/operator/query/size/>`_
in the Mongo docs.

Query for users that have a login field before it was renamed to
username:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('login')->exists(true);

Read more about the
`$exists operator <https://docs.mongodb.com/manual/reference/operator/query/exists/>`_
in the Mongo docs.

Query for users that have a type field that is of integer bson
type:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('type')->type('integer');

Read more about the
`$type operator <https://docs.mongodb.com/manual/reference/operator/query/type/>`_
in the Mongo docs.

Query for users that are in all the specified Groups:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('groups')->all(array('Group 1', 'Group 2'));

Read more about the
`$all operator <https://docs.mongodb.com/manual/reference/operator/query/all/>`_
in the Mongo docs.

.. code-block:: php
    
    <?php

    $qb = $dm->createQueryBuilder('Transaction')
        ->field('field')->mod('field', array(10, 1));

Read more about the
`$mod operator <https://docs.mongodb.com/manual/reference/operator/query/mod/>`_ in the Mongo docs.

Query for users who have subscribed or are in a trial.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User');
    $qb->addOr($qb->expr()->field('subscriber')->equals(true));
    $qb->addOr($qb->expr()->field('inTrial')->equals(true));
    
Read more about the 
`$or operator <https://docs.mongodb.com/manual/reference/operator/query/or/>`_ in the Mongo docs.

The ``references()`` method may be used to query the owning side of a
:ref:`@ReferenceOne <annotations_reference_reference_one>` relationship. In the
following example, we query for all articles written by a particular user.

.. code-block:: php

    <?php

    // Suppose $user has already been fetched from the database
    $qb = $dm->createQueryBuilder('Article')
        ->field('user')->references($user);

The ``includesReferenceTo()`` method may be used to query the owning side of a
:ref:`@ReferenceMany <annotations_reference_reference_many>` relationship. In
the following example, we query for the user(s) that have access to a particular
account.

.. code-block:: php

    <?php

    // Suppose $account has already been fetched from the database
    $qb = $dm->createQueryBuilder('User')
        ->field('accounts')->includesReferenceTo($account);

Text Search
~~~~~~~~~~~

You can use the
`$text operator <https://docs.mongodb.com/manual/reference/operator/query/text/>`_
to run a text search against a field with a text index. To do so, create a
document with a text index:

.. code-block:: php

        <?php

        /**
         * @Document
         * @Index(keys={"description"="text"})
         */
        class Document
        {
            /** @Id */
            public $id;

            /** @Field(type="string") */
            public $description;

            /** @Field(type="float") @NotSaved */
            public $score;
        }

You can then run queries using the text operator:

.. code-block:: php

    <?php

    // Run a text search against the index
    $qb = $dm->createQueryBuilder('Document')
        ->text('words you are looking for');

To fetch the calculated score for the text search, use the ``selectMeta()``
method:

.. code-block:: php

    <?php

    // Run a text search against the index
    $qb = $dm->createQueryBuilder('Document')
        ->selectMeta('score', 'textScore')
        ->text('words you are looking for');

You can also change the language used for stemming using the ``language()``
method:

.. code-block:: php

    <?php

    // Run a text search against the index
    $qb = $dm->createQueryBuilder('Document')
        ->language('it')
        ->text('parole che stai cercando');


Update Queries
~~~~~~~~~~~~~~

Doctrine also supports executing atomic update queries using the `Query\Builder`
object. You can use the conditional operations in combination with the ability to
change document field values atomically. Additionally if you are modifying a field
that is a reference you can pass managed document to the Builder and let ODM build
``DBRef`` object for you.

You have several modifier operations
available to you that make it easy to update documents in Mongo:

* ``set($name, $value, $atomic = true)``
* ``setNewObj($newObj)``
* ``inc($name, $value)``
* ``unsetField($field)``
* ``push($field, $value)``
* ``pushAll($field, array $valueArray)``
* ``addToSet($field, $value)``
* ``addManyToSet($field, array $values)``
* ``popFirst($field)``
* ``popLast($field)``
* ``pull($field, $value)``
* ``pullAll($field, array $valueArray)``

Updating multiple documents
---------------------------

By default only one document is updated. You need to pass ``true`` to the ``multiple()`` method to update all documents matched by the query.

.. code-block:: php

    <?php

    $dm->createQueryBuilder('User')
        ->update()
        ->multiple(true)
        ->field('someField')->set('newValue')
        ->field('username')->equals('sgoettschkes')
        ->getQuery()
        ->execute();

Modifier Operations
-------------------

Change a users password:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('User')
        ->update()
        ->field('password')->set('newpassword')
        ->field('username')->equals('jwage')
        ->getQuery()
        ->execute();

If you want to just set the values of an entirely new object you
can do so by passing false as the third argument of ``set()`` to
tell it the update is not an atomic one:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('User')
        ->update()
        ->field('username')->set('jwage', false)
        ->field('password')->set('password', false)
        // ... set other remaining fields
        ->field('username')->equals('jwage')
        ->getQuery()
        ->execute();

Read more about the
`$set modifier <https://docs.mongodb.com/manual/reference/operator/update/set/>`_
in the Mongo docs.

You can set an entirely new object to update as well:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('User')
        ->setNewObj(array(
            'username' => 'jwage',
            'password' => 'password',
            // ... other fields
        ))
        ->field('username')->equals('jwage')
        ->getQuery()
        ->execute();

Increment the value of a document:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Package')
        ->field('id')->equals('theid')
        ->field('downloads')->inc(1)
        ->getQuery()
        ->execute();

Read more about the
`$inc modifier <https://docs.mongodb.com/manual/reference/operator/update/inc/>`_
in the Mongo docs.

Unset the login field from users where the login field still
exists:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('User')
        ->update()
        ->field('login')->unsetField()->exists(true)
        ->getQuery()
        ->execute();

Read more about the
`$unset modifier <https://docs.mongodb.com/manual/reference/operator/update/unset/>`_
in the Mongo docs.

Append new tag to the tags array:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->push('tag5')
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Read more about the
`$push modifier <https://docs.mongodb.com/manual/reference/operator/update/push/>`_
in the Mongo docs.

Append new tags to the tags array:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->pushAll(array('tag6', 'tag7'))
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Read more about the
`$pushAll modifier <https://docs.mongodb.com/manual/reference/operator/update/pushAll/>`_
in the Mongo docs.

Add value to array only if its not in the array already:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->addToSet('tag1')
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Read more about the
`$addToSet modifier <https://docs.mongodb.com/manual/reference/operator/update/addToSet/>`_
in the Mongo docs.

Add many values to the array only if they do not exist in the array
already:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->addManyToSet(array('tag6', 'tag7'))
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Read more about the
`$addManyToSet modifier <http://www.mongodb.org/display/DOCS/Updating#Updating-%24addManyToSet>`_
in the Mongo docs.

Remove first element in an array:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->popFirst()
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Remove last element in an array:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->popLast()
        ->field('id')->equals('theid')
        ->getQuery()
        ->execute();

Read more about the
`$pop modifier <https://docs.mongodb.com/manual/reference/operator/update/pop/>`_
in the Mongo docs.

Remove all occurrences of value from array:

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->pull('tag1')
        ->getQuery()
        ->execute();

Read more about the
`$pull modifier <https://docs.mongodb.com/manual/reference/operator/update/pull/>`_
in the Mongo docs.

.. code-block:: php

    <?php

    $dm->createQueryBuilder('Article')
        ->update()
        ->field('tags')->pullAll(array('tag1', 'tag2'))
        ->getQuery()
        ->execute();

Read more about the
`$pullAll modifier <https://docs.mongodb.com/manual/reference/operator/update/pullAll/>`_
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

    $dm->createQueryBuilder('User')
        ->remove()
        ->field('num_logins')->equals(0)
        ->getQuery()
        ->execute();

Group Queries
-------------

The last type of supported query is a group query. It performs an
operation similar to SQL's GROUP BY command.

.. code-block:: php

    <?php

    $result = $this->dm->createQueryBuilder('Documents\User')
        ->group(array(), array('count' => 0))
        ->reduce('function (obj, prev) { prev.count++; }')
        ->field('a')->gt(1)
        ->getQuery()
        ->execute();

This is the same as if we were to do the group with the raw PHP
code:

.. code-block:: php

    <?php

    $reduce = 'function (obj, prev) { prev.count++; }';
    $condition = array('a' => array( '$gt' => 1));
    $result = $collection->group(array(), array('count' => 0), $reduce, $condition);
