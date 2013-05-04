Priming References
==================

Often when using Doctrine MongoDB ODM you need to prime references in resulted documents
to avoid making many trips to the database to get ``@ReferenceOne`` and ``@ReferenceMany``
documents. Doctrine offers the ability to prime references and group the queries to avoid
the infamous n+1 problem.

Here is an example:

.. code-block:: php

    <?php

    /** @Document */
    class User
    {
        /** @ReferenceMany(targetDocument="Account") */
        private $accounts;
    }

If you run a query to find users and you want to load each users accounts, that means one
additional query per user.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->limit(100);
    $query = $qb->getQuery();
    $users = $query->execute();

Iterating over the ``$users`` and getting the accounts will hit the database and load the users accounts:

.. code-block:: php

    <?php

    foreach ($users as $user) {
        // another query to load accounts for $user
        foreach ($user->getAccounts() as $account) {
            
        }
    }

So in this example we have 100 users so that means 1 query to load the users and 100 for all the accounts.
We can improve this by using the ``prime()`` method:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('accounts')->prime(true)
        ->limit(100);
    $query = $qb->getQuery();

    // 2 larger queries are performed eagerly to fetch users and accounts
    $users = $query->execute();

    // no additional queries are executed when iterating
    foreach ($users as $user) {
        foreach ($user->getAccounts() as $account) {
            
        }
    }

Now we only have 2 queries instead of 101.