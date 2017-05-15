.. Heavily inspired by Doctrine 2 ORM documentation

Transactions and Concurrency
============================

Transactions
------------

As per the `documentation <https://docs.mongodb.com/manual/core/write-operations-atomicity/#atomicity-and-transactions>`_, MongoDB
write operations are "atomic on the level of a single document".

Even when updating multiple documents within a single write operation,
though the modification of each document is atomic,
the operation as a whole is not and other operations may interleave.

As stated in the `FAQ <https://docs.mongodb.com/manual/faq/fundamentals/#does-mongodb-support-transactions>`_,
"MongoDB does not support multi-document transactions" and neither does Doctrine MongoDB ODM.

Limitation
~~~~~~~~~~
At the moment, Doctrine MongoDB ODM does not provide any native strategy to emulate multi-document transactions.

Workaround
~~~~~~~~~~
To work around this limitation, one can utilize `two phase commits <https://docs.mongodb.com/manual/tutorial/perform-two-phase-commits/>`_.

Concurrency
-----------

Doctrine MongoDB ODM offers native support for pessimistic and optimistic locking strategies.
This allows for very fine-grained control over what kind of locking is required for documents in your application.

.. _transactions_and_concurrency_optimistic_locking:

Optimistic Locking
~~~~~~~~~~~~~~~~~~

Approach
^^^^^^^^

Doctrine has integrated support for automatic optimistic locking
via a ``version`` field. Any document that should be
protected against concurrent modifications during long-running
business transactions gets a ``version`` field that is either a simple
number (mapping type: ``int``) or a date (mapping type: ``date``).
When changes to the document are persisted,
the expected version and version increment are incorporated into the update criteria and modifiers, respectively.
If this results in no document being modified by the update (i.e. expected version did not match),
a ``LockException`` is thrown, which indicates that the document was already modified by another query.

.. note::

    | Versioning can only be used on *root* (top-level) documents.

Document Configuration
^^^^^^^^^^^^^^^^^^^^^^

The following example designates a version field using the ``int`` type:

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


Alternatively, the ``date`` type may be used:

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

Choosing the Field Type
"""""""""""""""""""""""

When using the ``date`` type in a high-concurrency environment, it is still possible to create multiple documents
with the same version and cause a conflict. This can be avoided by using the ``int`` type.

Usage
"""""

When a version conflict is encountered during
``DocumentManager#flush()``, a ``LockException`` is thrown.
This exception can be caught and handled. Potential responses to a
``LockException`` are to present the conflict to the user or
to refresh or reload objects and then retry the update.

With PHP promoting a share-nothing architecture,
the worst case scenario for a delay between rendering an update form (with existing document data)
and modifying the document after a form submission may be your application's session timeout.
If the document is changed within that time frame by some other request,
it may be preferable to encounter a ``LockException`` when retrieving the document instead of executing the update.

You can specify the expected version of a document during a query with ``DocumentManager#find()``:

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

Alternatively, an expected version may be specified for an existing document with ``DocumentManager#lock()``:

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

At the last stage of this scenario the blog post has to be read
again from the database before Alice's headline can be applied. At
this point you will want to check if the blog post is still at
version 1 (which it is not in this scenario).

In order to correctly utilize optimistic locking, you *must* add the version as hidden form field or,
for more security, session attribute.
Otherwise, you cannot verify that the version at the time of update is the same as what was originally read
from the database when Alice performed her original GET request for the blog post.
Without correlating the version across form submissions, the application could lose updates.

Example Code
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

.. _transactions_and_concurrency_pessimistic_locking:

Pessimistic Locking
~~~~~~~~~~~~~~~~~~~

Doctrine MongoDB ODM also supports pessimistic locking via a configurable ``lock`` field.
This functionality is implemented entirely by Doctrine; MongoDB has no native support for pessimistic locking.

Document Configuration
^^^^^^^^^^^^^^^^^^^^^^

Pessimistic locking requires a document to designate a lock field using the ``int`` type:

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

Lock Modes
^^^^^^^^^^

Doctrine MongoDB ODM currently supports two pessimistic lock modes:

-  Pessimistic Write
   (``\Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_WRITE``): locks the
   underlying document for concurrent read and write operations.
-  Pessimistic Read (``\Doctrine\ODM\MongoDB\LockMode::PESSIMISTIC_READ``):
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

.. warning::

    | A few things could go wrong:
    |
    | If a request fails to complete (e.g. unhandled exception), you may end up with stale locks.
      Said locks would need to be manually released or you would need to devise a strategy to automatically do so.
      One way to mitigate stale locks after an application error would be to gracefully catch the exception
      and ensure that relevant documents are unlocked before the request ends.
    |
    | `Deadlock <https://en.wikipedia.org/wiki/Deadlock>`_ situations are also possible.
      Suppose process P1 needs resource R1 and has locked resource R2
      and that another process P2 has locked resource R1 but also needs resource R2.
      If both processes continue waiting for the respective resources, the application will be stuck.
      When loading a document, Doctrine can immediately throw an exception if it is already locked.
      A deadlock could be created by endlessly retrying attempts to acquire the lock.
      One can avoid a possible deadlock by designating a maximum number of retry attempts
      and automatically releasing any active locks with the request ends,
      thereby allowing a process to end gracefully while another completes its task.
