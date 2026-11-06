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
        ['_id' => new MongoDB\BSON\ObjectId($articleId)],
        ['$inc' => ['numViews' => 1]],
        ['upsert' => true, 'safe' => true]
    );

The extra benefit is the fact that you don't have to fetch the ``$article`` in order
to append some new data to the document or change something. All you need is the
identifier.

.. note::

    Identifiers must uniquely map to document object instances. When you persist a
    new document whose identifier is already used by a different managed document,
    the identity map keeps the first instance. A deprecation is triggered, and an
    exception is thrown if ``Configuration::setRejectIdCollisionInIdentityMap(true)``
    is enabled. Detach the managed document or clear the document manager before
    persisting a new instance with the same identifier.
