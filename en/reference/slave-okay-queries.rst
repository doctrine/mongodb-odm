Slave Okay Queries
==================

If you want to instruct your queries to read from a slave you can use the ``slaveOkay()`` method.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->slaveOkay(true);
    $query = $qb->getQuery();
    $users = $query->execute();

The data in the query above will be read from a slave. Even if you have a ``@ReferenceMany`` or 
``@ReferenceMany`` resulting from the query above it will be initialized and loaded from a slave.

.. code-block:: php

    <?php

    /** @Document */
    class User
    {
        /** @ReferenceMany(targetDocument="Account") */
        private $accounts;
    }

Now when you query and iterate over the accounts, they will be loaded from a slave:

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->slaveOkay(true);
    $query = $qb->getQuery();
    $users = $query->execute();

    foreach ($users as $user) {
        foreach ($user->getAccounts() as $account) {
            echo $account->getName();
        }
    }