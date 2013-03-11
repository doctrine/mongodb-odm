Eager Cursors
-------------

With a typical MongoDB cursor, it stays open during iteration and fetches
batches of documents as you iterate over the cursor. This isn't bad,
but sometimes you want to fetch all of the data eagerly. For example
when dealing with web applications, and you want to only show 50
documents from a collection you should fetch all the data in your
controller first before going on to the view.

Benefits:

- The cursor stays open for a much shorter period of time.

- Data retrieval and hydration are consolidated operations.

- Doctrine has the ability to retry the cursor when exceptions during interaction with mongodb are encountered.

Example:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->eagerCursor(true);
    $query = $qb->getQuery();
    $users = $query->execute(); // returns instance of Doctrine\MongoDB\ODM\EagerCursor

At this point all data is loaded from the database and cursors to MongoDB
have been closed but hydration of the data in to objects has not begun. Once
insertion starts the data will be hydrated in to PHP objects.

Example:

.. code-block:: php

    <?php

    foreach ($users as $user) {
        echo $user->getUsername()."\n";
    }

Not all documents are converted to objects at once, the hydration is still done
one document at a time during iteration. The only change is that all data is retrieved
first.
