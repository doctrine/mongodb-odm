.. Heavily inspired by Doctrine 2 ORM documentation

Transactions and Concurrency
============================

Transactions
------------

As per the `documentation <https://docs.mongodb.com/manual/core/write-operations-atomicity/#atomicity-and-transactions>`_, MongoDB
write operations are "atomic on the level of a single document".

Even when updating multiple documents with a single write operation,
though the modification of each document is atomic,
the operation as a whole is not, and other operations may interleave.

Simply put, quoting the `FAQ <https://docs.mongodb.com/manual/faq/fundamentals/#does-mongodb-support-transactions>`_: "MongoDB does not support multi-document transactions".

Neither does Doctrine MongoDB ODM.

Limitation
~~~~~~~~~~
At the moment, Doctrine MongoDB ODM does not provide any native strategy to emulate multi-document transactions.

Workaround
~~~~~~~~~~
To work around this limitation, one could perform `two phase commits <https://docs.mongodb.com/manual/tutorial/perform-two-phase-commits/>`_.

Concurrency
-----------

Doctrine MongoDB ODM offers support for Pessimistic- and Optimistic-locking
strategies natively. This allows to take very fine-grained control
over what kind of locking is required for your Documents in your
application.

Optimistic Locking
~~~~~~~~~~~~~~~~~~

Approach
^^^^^^^^

Doctrine has integrated support for automatic optimistic locking
via a ``version`` field. Any document that should be
protected against concurrent modifications during long-running
business transactions gets a ``version`` field that is either a simple
number (mapping type: ``int``) or a date (mapping type:
``date``).
When changes are persisted at the end of a long-running conversation,
the version of the document is added to the query. If no document has been updated by the operation,
a ``LockException`` is thrown, indicating that the document has already been modified by another query.

Document configuration
^^^^^^^^^^^^^^^^^^^^^^

You designate a version field in a document as follows. In this
example we'll use an integer (``int`` type).

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Version @Field(type="int") */
        private $version;

    .. code-block:: xml

        <field fieldName="version" version="true" type="int" />

    .. code-block:: yaml

        version:
          type: int
          version: true


Alternatively ``date`` type can be used:

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Version @Field(type="date") */
        private $version;

    .. code-block:: xml

        <field fieldName="version" version="true" type="date" />

    .. code-block:: yaml

        version:
          type: date
          version: true

Choosing the field type
"""""""""""""""""""""""

When using ``date`` type in a highly concurrent environment, multiple documents could be created with the same version
and create a conflict.
This cause of conflict can be avoided by using ``int`` type.

Usage
"""""

When a version conflict is encountered during
``DocumentManager#flush()``, a ``LockException`` is thrown.
This exception can be caught and handled. Potential responses to a
``LockException`` are to present the conflict to the user or
to refresh or reload objects and then retry the update.

With PHP promoting a share-nothing architecture, the time between
showing an update form and actually modifying the document can in the
worst scenario be as long as your applications session timeout. If
changes happen to the document in that time frame you want to know
directly when retrieving the document that you will hit a locking exception.

You can verify the version of a document during a request either when calling ``DocumentManager#find()``:

.. code-block:: php

    <?php
    use Doctrine\ODM\MongoDB\LockMode;
    use Doctrine\ODM\MongoDB\LockException;
    use Doctrine\ODM\MongoDB\DocumentManager;

    $theDocumentId = 1;
    $expectedVersion = 184;

    /* @var $dm DocumentManager */

    try {
        $document = $dm->find('User', $theDocumentId, LockMode::OPTIMISTIC, $expectedVersion);

        // do the work

        $dm->flush();
    } catch(LockException $e) {
        echo "Sorry, but someone else has already changed this document. Please apply the changes again!";
    }

Or you can use ``DocumentManager#lock()`` to find out:

.. code-block:: php

    <?php
    use Doctrine\ODM\MongoDB\LockMode;
    use Doctrine\ODM\MongoDB\LockException;
    use Doctrine\ODM\MongoDB\DocumentManager;

    $theDocumentId = 1;
    $expectedVersion = 184;

    /* @var $dm DocumentManager */

    $document = $dm->find('User', $theDocumentId);

    try {
        // assert version
        $dm->lock($document, LockMode::OPTIMISTIC, $expectedVersion);

    } catch(LockException $e) {
        echo "Sorry, but someone else has already changed this document. Please apply the changes again!";
    }

Important Implementation Notes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can easily get the optimistic locking workflow wrong if you
compare the wrong versions.

Workflow
""""""""

Say you have Alice and Bob editing a
hypothetical blog post:

-  Alice reads the headline of the blog post being "Foo", at
   optimistic lock version 1 (GET Request)
-  Bob reads the headline of the blog post being "Foo", at
   optimistic lock version 1 (GET Request)
-  Bob updates the headline to "Bar", upgrading the optimistic lock
   version to 2 (POST Request of a Form)
-  Alice updates the headline to "Baz", ... (POST Request of a
   Form)

Now at the last stage of this scenario the blog post has to be read
again from the database before Alice's headline can be applied. At
this point you will want to check if the blog post is still at
version 1 (which it is not in this scenario).

Using optimistic locking correctly, you *have* to add the version
as an additional hidden field (or into the SESSION for more
safety). Otherwise you cannot verify the version is still the one
being originally read from the database when Alice performed her
GET request for the blog post. If this happens you might see lost
updates you wanted to prevent with Optimistic Locking.

Example code
""""""""""""

The form (GET Request):

.. code-block:: php

    <?php
    use Doctrine\ODM\MongoDB\DocumentManager;

    /* @var $dm DocumentManager */

    $post = $dm->find('BlogPost', 123456);

    echo '<input type="hidden" name="id" value="' . $post->getId() . '" />';
    echo '<input type="hidden" name="version" value="' . $post->getCurrentVersion() . '" />';

And the change headline action (POST Request):

.. code-block:: php

    <?php
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\LockMode;

    /* @var $dm DocumentManager */

    $postId = (int)$_POST['id'];
    $postVersion = (int)$_POST['version'];

    $post = $dm->find('BlogPost', $postId, LockMode::OPTIMISTIC, $postVersion);

Pessimistic Locking
~~~~~~~~~~~~~~~~~~~

Doctrine MongoDB ODM supports Pessimistic Locking.
There is no native MongoDB support for pessimistic locking.
The Doctrine implementation uses a ``lock`` field, that you have to configure if you wish to use pessimistic locking.

Document configuration
^^^^^^^^^^^^^^^^^^^^^^

For pessimistic locking to work, a lock field must be configured.
The lock field must be of type ``int``.
You designate a lock field in a document as follows.

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Lock @Field(type="int") */
        private $lock;

    .. code-block:: xml

        <field fieldName="lock" lock="true" type="int" />

    .. code-block:: yaml

        lock:
          type: int
          lock: true

Lock modes
^^^^^^^^^^

Doctrine MongoDB ODM currently supports two pessimistic lock modes:

-  Pessimistic Write
   (``\Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_WRITE``), locks the
   underlying document for concurrent Read and Write operations.
-  Pessimistic Read (``\Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_READ``),
   locks other concurrent requests that attempt to update or lock documents
   in write mode.

Usage
^^^^^

You can use pessimistic locks in two different scenarios:

1. Using
   ``DocumentManager#find($className, $id, \Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_WRITE)``
   or
   ``DocumentManager#find($className, $id, \Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_READ)``
2. Using
   ``DocumentManager#lock($document, \Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_WRITE)``
   or
   ``DocumentManager#lock($document, \Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_READ)``
