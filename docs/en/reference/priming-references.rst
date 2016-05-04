Priming References
==================

Priming references allows you to consolidate database queries when working with
:ref:`one <reference_one>` and :ref:`many <reference_many>` reference mappings.
This is useful for avoiding the
`n+1 problem <http://stackoverflow.com/q/97197/162228>`_ in your application.

Query Builder
-------------

Consider the following abbreviated model:

.. code-block:: php

    <?php

    /** @Document */
    class User
    {
        /** @ReferenceMany(targetDocument="Account") */
        private $accounts;
    }

We would like to query for 100 users and then iterate over their referenced
accounts.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->limit(100);
    $query = $qb->getQuery();
    $users = $query->execute();

    foreach ($users as $user) {
        /* PersistentCollection::initialize() will be invoked when we begin
         * iterating through the user's accounts. Any accounts not already
         * managed by the unit of work will need to be queried.
         */
        foreach ($user->getAccounts() as $account) {
            // ...
        }
    }

In this example, ODM would query the database once for the result set of users
and then, for each user, issue a separate query to load any accounts that are
not already being managed by the unit of work. This could result in as many as
100 additional database queries!

If we expect to iterate through all users and their accounts, we could optimize
this process by loading all of the referenced accounts with one query. The query
builder's ``prime()`` method allows us to do just that.

.. code-block:: php

    <?php

    $qb = $dm->createQueryBuilder('User')
        ->field('accounts')->prime(true)
        ->limit(100);
    $query = $qb->getQuery();

    /* After querying for the users, ODM will collect the IDs of all referenced
     * accounts and load them with a single additional query.
     */
    $users = $query->execute();

    foreach ($users as $user) {
        /* Accounts have already been loaded, so iterating through accounts will
         * not query an additional query.
         */
        foreach ($user->getAccounts() as $account) {
            
        }
    }

In this case, priming will allow us to load all users and referenced accounts in
two database queries. If the accounts had used an
:ref:`inhertiance mapping <inheritance_mapping>`, priming might require several
queries (one per discriminated class name).

.. note::

    Priming is also compatible with :ref:`simple references <storing_references>`
    and discriminated references. When priming discriminated references, ODM
    will issue one query per distinct class among the referenced document(s).

.. note::

    Hydration must be enabled in the query builder for priming to work properly.
    Disabling hydration will cause the DBRef to be returned for a referenced
    document instead of the hydrated document object.

Primer Callback
---------------

Passing ``true`` to ``prime()`` instructs ODM to load the referenced document(s)
on its own; however, we can also pass a custom callable (e.g. Closure instance)
to ``prime()``, which allows more control over the priming query.

As an example, we can look at the default callable, which is found in the
``ReferencePrimer`` class.

.. code-block:: php

    <?php

    function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) {
        $qb = $dm->createQueryBuilder($class->name)
            ->field($class->identifier)->in($ids);

        if ( ! empty($hints[Query::HINT_SLAVE_OKAY])) {
            $qb->slaveOkay(true);
        }

        if ( ! empty($hints[Query::HINT_READ_PREFERENCE])) {
            $qb->setReadPreference(
                $hints[Query::HINT_READ_PREFERENCE],
                $hints[Query::HINT_READ_PREFERENCE_TAGS]
            );
        }

        $qb->getQuery()->toArray();
    };

Firstly, the callable is passed the ``DocumentManager`` of the main query. This
is necessary to create the query used for priming, and ensures that the results
will become managed in the same scope. The ``ClassMetadata`` argument provides
mapping information for the referenced class as well as its name, which is used
to create the query builder. An array of identifiers follows, which is used to
query for the documents to be primed. Lastly, the ``UnitOfWork`` hints from the
original query are provided so that the priming query can apply them as well.
