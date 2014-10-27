Slave Okay Queries
==================

Documents
~~~~~~~~~

You can configure an entire document to send all reads to the slaves by using the ``slaveOkay`` flag:

.. code-block:: php

    <?php

    /** @Document(slaveOkay=true) */
    class User
    {
        /** @Id */
        private $id;
    }

Now all reads involving the ``User`` document will be sent to a slave.

Queries
~~~~~~~~~

If you want to instruct individual queries to read from a slave you can use the ``slaveOkay()`` method
on the query builder.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->slaveOkay(true);
    $query = $qb->getQuery();
    $users = $query->execute();

The data in the query above will be read from a slave. Even if you have a ``@ReferenceOne`` or 
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
