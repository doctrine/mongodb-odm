Find and Modify
===============

.. note::

    From MongoDB.org:

    MongoDB supports a "find, modify, and return" command. This command
    can be used to atomically modify a document (at most one) and
    return it. Note that, by default, the document returned will not
    include the modifications made on the update.

Doctrine fully integrates the find and modify functionality to the
query builder object so you can easily run these types of queries!

Update
------

For example you can update a job and return it:

.. code-block:: php

    <?php

    $job = $dm->createQueryBuilder('Job')
        // Find the job
        ->findAndUpdate()
        ->field('in_progress')->set(true)
        ->field('in_progress')->equals(false)
        ->sort('priority', 'desc')
    
        // Update found job
        ->field('started')->set(new \MongoDate())
        ->getQuery()
        ->execute();

If you want to update a job and return the new document you can
call the ``returnNew()`` method.

Here is an example where we return the new updated job document:

.. code-block:: php

    <?php
    $job = $dm->createQueryBuilder('Job')
        // Find the job
        ->findAndUpdate()
        ->returnNew()
        ->field('in_progress')->equals(false)
        ->sort('priority', 'desc')
    
        // Update found job
        ->field('started')->set(new \MongoDate())
        ->field('in_progress')->set(true)
        ->getQuery()
        ->execute();

The returned ``$job`` will be a managed ``Job`` instance with the
``started`` and ``in_progress`` fields updated.

Remove
------

You can also remove a document and return it:

.. code-block:: php

    <?php

    $job = $dm->createQueryBuilder('Job')
        ->findAndRemove()
        ->sort('priority', 'desc')
        ->getQuery()
        ->execute();

You can read more about the find and modify functionality on the
`MongoDB website <https://docs.mongodb.com/manual/reference/method/db.collection.findAndModify/>`_.

.. note::

    If you don't need to return the document, you can use just run a normal update which can
    affect multiple documents, as well.
