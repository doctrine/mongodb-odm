Working with Objects
====================

Understanding
-------------

In this chapter we will help you understand the ``DocumentManager``
and the ``UnitOfWork``. A Unit of Work is similar to an
object-level transaction. A new Unit of Work is implicitly started
when a DocumentManager is initially created or after
``DocumentManager#flush()`` has been invoked. A Unit of Work is
committed (and a new one started) by invoking
``DocumentManager#flush()``.

A Unit of Work can be manually closed by calling
``DocumentManager#close()``. Any changes to objects within this
Unit of Work that have not yet been persisted are lost.

The size of a Unit of Work
~~~~~~~~~~~~~~~~~~~~~~~~~~

The size of a Unit of Work mainly refers to the number of managed
documents at a particular point in time.

The cost of flush()
~~~~~~~~~~~~~~~~~~~

How costly a flush operation is in terms of performance mainly
depends on the size. You can get the size of your Unit of Work as
follows:

.. code-block:: php

    <?php

    $uowSize = $dm->getUnitOfWork()->size();

The size represents the number of managed documents in the Unit of
Work. This size affects the performance of flush() operations due
to change tracking and, of course, memory consumption, so you may
want to check it from time to time during development.

.. caution::

    Do not invoke ``flush`` after every change to a
    document or every single invocation of persist/remove/merge/...
    This is an anti-pattern and unnecessarily reduces the performance
    of your application. Instead, form units of work that operate on
    your objects and call ``flush`` when you are done. While serving a
    single HTTP request there should be usually no need for invoking
    ``flush`` more than 0-2 times.

Direct access to a Unit of Work
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can get direct access to the Unit of Work by calling
``DocumentManager#getUnitOfWork()``. This will return the
UnitOfWork instance the DocumentManager is currently using.

.. code-block:: php

    <?php

    $uow = $dm->getUnitOfWork();

.. note::

    Directly manipulating a UnitOfWork is not recommended.
    When working directly with the UnitOfWork API, respect methods
    marked as INTERNAL by not using them and carefully read the API
    documentation.

Persisting documents
--------------------

A document can be made persistent by passing it to the
``DocumentManager#persist($document)`` method. By applying the
persist operation on some document, that document becomes MANAGED,
which means that its persistence is from now on managed by an
DocumentManager. As a result the persistent state of such a
document will subsequently be properly synchronized with the
database when ``DocumentManager#flush()`` is invoked.

.. caution::

    Invoking the ``persist`` method on a document does NOT
    cause an immediate insert to be issued on the database. Doctrine
    applies a strategy called "transactional write-behind", which means
    that it will delay most operations until
    ``DocumentManager#flush()`` is invoked which will then issue all
    necessary queries to synchronize your objects with the database in
    the most efficient way.

Example:

.. code-block:: php

    <?php

    $user = new User();
    $user->setUsername('jwage');
    $user->setPassword('changeme');
    $dm->persist($user);
    $dm->flush();

.. caution::

    The \_id of a document is guaranteed to be available
    after the next successful flush operation that involves the
    document in question. You can not rely on a generated identifier to
    be available directly after invoking ``persist``.

The semantics of the persist operation, applied on a document X,
are as follows:

- 
   If X is a new document, it becomes managed. The document X will be
   entered into the database as a result of the flush operation.
- 
   If X is a preexisting managed document, it is ignored by the
   persist operation. However, the persist operation is cascaded to
   documents referenced by X, if the relationships from X to these
   other documents are mapped with cascade=PERSIST or cascade=ALL.
-  If X is a removed document, it becomes managed.
-  If X is a detached document, the behavior is undefined.

.. caution::

    Do not pass detached documents to the persist operation.

Flushing single documents
-------------------------

You can flush a single document by passing the document object to the
``flush`` method.

Example:

.. code-block:: php

    <?php

    $user = $dm->getRepository('User')->find($userId);
    // ...
    $user->setPassword('changeme');
    $dm->flush($user);

.. _flush_options:

Flush Options
-------------

When committing your documents you can specify an array of options to the
``flush`` method. With it you can send options to the underlying database
like ``safe``, ``fsync``, etc.

Example:

.. code-block:: php

    <?php

    $user = $dm->getRepository('User')->find($userId);
    // ...
    $user->setPassword('changeme');
    $dm->flush(null, array('safe' => true, 'fsync' => true));

You can configure the default flush options on your ``Configuration`` object
if you want to set them globally for all flushes.

Example:

.. code-block:: php

    <?php

    $config->setDefaultCommitOptions(array(
        'safe' => true,
        'fsync' => true
    ));

.. note::

    Safe is set to true by default for all writes when using the ODM.

Removing documents
------------------

A document can be removed from persistent storage by passing it to
the ``DocumentManager#remove($document)`` method. By applying the
``remove`` operation on some document, that document becomes
REMOVED, which means that its persistent state will be deleted once
``DocumentManager#flush()`` is invoked. The in-memory state of a
document is unaffected by the ``remove`` operation.

.. caution::

    Just like ``persist``, invoking ``remove`` on a
    document does NOT cause an immediate query to be issued on the
    database. The document will be removed on the next invocation of
    ``DocumentManager#flush()`` that involves that document.

Example:

.. code-block:: php

    <?php

    $dm->remove($user);
    $dm->flush();

The semantics of the remove operation, applied to a document X are
as follows:

- 
   If X is a new document, it is ignored by the remove operation.
   However, the remove operation is cascaded to documents referenced
   by X, if the relationship from X to these other documents is mapped
   with cascade=REMOVE or cascade=ALL.
- 
   If X is a managed document, the remove operation causes it to
   become removed. The remove operation is cascaded to documents
   referenced by X, if the relationships from X to these other
   documents is mapped with cascade=REMOVE or cascade=ALL.
- 
   If X is a detached document, an InvalidArgumentException will be
   thrown.
- 
   If X is a removed document, it is ignored by the remove operation.
- 
   A removed document X will be removed from the database as a result
   of the flush operation.

Detaching documents
-------------------

A document is detached from a DocumentManager and thus no longer
managed by invoking the ``DocumentManager#detach($document)``
method on it or by cascading the detach operation to it. Changes
made to the detached document, if any (including removal of the
document), will not be synchronized to the database after the
document has been detached.

Doctrine will not hold on to any references to a detached
document.

Example:

.. code-block:: php

    <?php

    $dm->detach($document);

The semantics of the detach operation, applied to a document X are
as follows:


- 
   If X is a managed document, the detach operation causes it to
   become detached. The detach operation is cascaded to documents
   referenced by X, if the relationships from X to these other
   documents is mapped with cascade=DETACH or cascade=ALL. Documents
   which previously referenced X will continue to reference X.
- 
   If X is a new or detached document, it is ignored by the detach
   operation.
- 
   If X is a removed document, the detach operation is cascaded to
   documents referenced by X, if the relationships from X to these
   other documents is mapped with cascade=DETACH or
   cascade=ALL/Documents which previously referenced X will continue
   to reference X.

There are several situations in which a document is detached
automatically without invoking the ``detach`` method:


- 
   When ``DocumentManager#clear()`` is invoked, all documents that are
   currently managed by the DocumentManager instance become detached.
- 
   When serializing a document. The document retrieved upon subsequent
   unserialization will be detached (This is the case for all
   documents that are serialized and stored in some cache).

The ``detach`` operation is usually not as frequently needed and
used as ``persist`` and ``remove``.

Merging documents
-----------------

Merging documents refers to the merging of (usually detached)
documents into the context of a DocumentManager so that they
become managed again. To merge the state of a document into an
DocumentManager use the ``DocumentManager#merge($document)``
method. The state of the passed document will be merged into a
managed copy of this document and this copy will subsequently be
returned.

Example:

.. code-block:: php

    <?php

    $detachedDocument = unserialize($serializedDocument); // some detached document
    $document = $dm->merge($detachedDocument);
    // $document now refers to the fully managed copy returned by the merge operation.
    // The DocumentManager $dm now manages the persistence of $document as usual.

    The semantics of the merge operation, applied to a document X, are
    as follows:

- 
   If X is a detached document, the state of X is copied onto a
   pre-existing managed document instance X' of the same iddocument or
   a new managed copy X' of X is created.
- 
   If X is a new document instance, an InvalidArgumentException will
   be thrown.
- 
   If X is a removed document instance, an InvalidArgumentException
   will be thrown.
- 
   If X is a managed document, it is ignored by the merge operation,
   however, the merge operation is cascaded to documents referenced by
   relationships from X if these relationships have been mapped with
   the cascade element value MERGE or ALL.
- 
   For all documents Y referenced by relationships from X having the
   cascade element value MERGE or ALL, Y is merged recursively as Y'.
   For all such Y referenced by X, X' is set to reference Y'. (Note
   that if X is managed then X is the same object as X'.)
- 
   If X is a document merged to X', with a reference to another
   document Y, where cascade=MERGE or cascade=ALL is not specified,
   then navigation of the same association from X' yields a reference
   to a managed object Y' with the same persistent iddocument as Y.

The ``merge`` operation is usually not as frequently needed and
used as ``persist`` and ``remove``. The most common scenario for
the ``merge`` operation is to reattach documents to an
DocumentManager that come from some cache (and are therefore
detached) and you want to modify and persist such a document.

.. note::

    If you load some detached documents from a cache and you
    do not need to persist or delete them or otherwise make use of them
    without the need for persistence services there is no need to use
    ``merge``. I.e. you can simply pass detached objects from a cache
    directly to the view.

References
----------

References between documents and embedded documents are represented
just like in regular object-oriented PHP, with references to other
objects or collections of objects.

Establishing References
-----------------------

Establishing a reference to another document is straight forward:

Here is an example where we add a new comment to an article:

.. code-block:: php

    <?php

    $comment = new Comment();
    // ...
    
    $article->getComments()->add($comment);

Or you can set a single reference:

.. code-block:: php

    <?php

    $address = new Address();
    // ...
    
    $user->setAddress($address);

Removing References
-------------------

Removing an association between two documents is similarly
straight-forward. There are two strategies to do so, by key and by
element. Here are some examples:

.. code-block:: php

    <?php

    $article->getComments()->removeElement($comment);
    $article->getComments()->remove($ithComment);

Or you can remove a single reference:

.. code-block:: php

    <?php

    $user->setAddress(null);

When working with collections, keep in mind that a Collection is
essentially an ordered map (just like a PHP array). That is why the
``remove`` operation accepts an index/key. ``removeElement`` is a
separate method that has O(n) complexity, where n is the size of
the map.

Transitive persistence
----------------------

Persisting, removing, detaching and merging individual documents
can become pretty cumbersome, especially when a larger object graph
with collections is involved. Therefore Doctrine provides a
mechanism for transitive persistence through cascading of these
operations. Each reference to another document or a collection of
documents can be configured to automatically cascade certain
operations. By default, no operations are cascaded.

The following cascade options exist:


- 
   persist : Cascades persist operations to the associated documents.
-  remove : Cascades remove operations to the associated documents.
-  merge : Cascades merge operations to the associated documents.
-  detach : Cascades detach operations to the associated documents.
- 
   all : Cascades persist, remove, merge and detach operations to
   associated documents.

The following example shows an association to a number of
addresses. If persist() or remove() is invoked on any User
document, it will be cascaded to all associated Address documents
in the $addresses collection.

.. code-block:: php

    <?php

    class User 
    {
        //...
        /**
         * @ReferenceMany(targetDocument="Address", cascade={"persist", "remove"})
         */
        private $addresses;
        //...
    }

Even though automatic cascading is convenient it should be used
with care. Do not blindly apply cascade=all to all associations as
it will unnecessarily degrade the performance of your application.

Querying
--------

Doctrine provides the following ways, in increasing level of power
and flexibility, to query for persistent objects. You should always
start with the simplest one that suits your needs.

By Primary Key
~~~~~~~~~~~~~~

The most basic way to query for a persistent object is by its
identifier / primary key using the
``DocumentManager#find($documentName, $id)`` method. Here is an
example:

.. code-block:: php

    <?php

    $user = $dm->find('User', $id);

The return value is either the found document instance or null if
no instance could be found with the given identifier.

Essentially, ``DocumentManager#find()`` is just a shortcut for the
following:

.. code-block:: php

    <?php

    $user = $dm->getRepository('User')->find($id);

``DocumentManager#getRepository($documentName)`` returns a
repository object which provides many ways to retrieve documents of
the specified type. By default, the repository instance is of type
``Doctrine\ODM\MongoDB\DocumentRepository``. You can also use
custom repository classes.

By Simple Conditions
~~~~~~~~~~~~~~~~~~~~

To query for one or more documents based on several conditions that
form a logical conjunction, use the ``findBy`` and ``findOneBy``
methods on a repository as follows:

.. code-block:: php

    <?php

    // All users that are 20 years old
    $users = $dm->getRepository('User')->findBy(array('age' => 20));
    
    // All users that are 20 years old and have a surname of 'Miller'
    $users = $dm->getRepository('User')->findBy(array('age' => 20, 'surname' => 'Miller'));
    
    // A single user by its nickname
    $user = $dm->getRepository('User')->findOneBy(array('nickname' => 'romanb'));

A DocumentRepository also provides a mechanism for more concise
calls through its use of ``__call``. Thus, the following two
examples are equivalent:

.. code-block:: php

    <?php

    // A single user by its nickname
    $user = $dm->getRepository('User')->findOneBy(array('nickname' => 'romanb'));
    
    // A single user by its nickname (__call magic)
    $user = $dm->getRepository('User')->findOneByNickname('romanb');

By Lazy Loading
~~~~~~~~~~~~~~~

Whenever you have a managed document instance at hand, you can
traverse and use any associations of that document as if they were
in-memory already. Doctrine will automatically load the associated
objects on demand through the concept of lazy-loading.

By Query Builder Objects
~~~~~~~~~~~~~~~~

The most powerful and flexible method to query for persistent
objects is the Query\Builder object. The Query\Builder object enables you to query
for persistent objects with a fluent object oriented interface.

You can create a query using
``DocumentManager#createQueryBuilder($documentName = null)``. Here is a
simple example:

.. code-block:: php

    <?php

    // All users with an age between 20 and 30 (inclusive).
    $qb = $dm->createQueryBuilder('User')
        ->field('age')->range(20, 30);
    $q = $qb->getQuery()
    $users = $q->execute();

By Reference
~~~~~~~~~~~~~~~~

To query documents with a ReferenceOne association to another document, use the ``references($document)`` expression:

.. code-block:: php

    <?php

    $group = $dm->find('Group', $id);
    $usersWithGroup = $dm->createQueryBuilder('User')
        ->field('group')->references($group)
        ->getQuery()->execute();

To find documents with a ReferenceMany association that includes a certain document, use the ``includesReferenceTo($document)`` expression:

.. code-block:: php

    <?php

    $users = $dm->createQueryBuilder('User')
        ->field('groups')->includesReferenceTo($group)
        ->getQuery()->execute();

Custom Repositories
~~~~~~~~~~~~~~~~~~~

By default the DocumentManager returns a default implementation of
``Doctrine\ODM\MongoDB\DocumentRepository`` when you call
``DocumentManager#getRepository($documentClass)``. You can override
this behavior by specifying the class name of your own Document
Repository in the Annotation, XML or YAML metadata. In large
applications that require lots of specialized DQL queries using a
custom repository is one recommended way of grouping these queries
in a central location.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\DocumentRepository;
    
    /**
     * @Document(repositoryClass="UserRepository")
     */
    class User
    {
    
    }
    
    class UserRepository extends DocumentRepository
    {
        public function getAllAdminUsers()
        {
            return $this->createQueryBuilder()
                ->field('status')->equals('admin')
                ->getQuery()->execute();
        }
    }

You can access your repository now by calling:

.. code-block:: php

    <?php

    $admins = $dm->getRepository('User')->getAllAdminUsers();
