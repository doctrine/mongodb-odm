Upserting Documents
===================

Upserting documents in the MongoDB ODM is easy. All you really have to do
is specify an ID ahead of time and Doctrine will perform an ``update`` operation
with the ``upsert`` flag internally instead of a ``batchInsert``.

Example:

.. code-block:: php

    <?php

    $article = new Article();
    $article->setId($articleId);
    $article->incrementNumViews();
    $dm->persist($article);
    $dm->flush();

The above would result in an operation like the following:

.. code-block:: php

    <?php

    $articleCollection->update(
        array('_id' => new MongoId($articleId)),
        array('$inc' => array('numViews' => 1)),
        array('upsert' => true, 'safe' => true)
    );

The extra benefit is the fact that you don't have to fetch the ``$article`` in order
to append some new data to the document or change something. All you need is the
identifier.