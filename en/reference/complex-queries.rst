Now that we've gone over the Doctrine MongoDB Query API thoroughly,
this section will show you some more examples that combine some of
the features together to make more complex queries.

Find Queries
------------

Find articles with a date range:

::

    <?php
    $dm->createQuery('Article')
        ->field('createdAt')
        ->range(
            new \MongoDate(strtotime('1985-09-01')),
            new \MongoDate(strtotime('1985-09-04'))
        );

Update Queries
--------------

Remove a field from a document if it is null:

::

    <?php
    $dm->createQuery()
        ->update('User')
        ->field('signature')
        ->type('null')
        ->unsetField();


