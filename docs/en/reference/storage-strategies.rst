.. _storage_strategies:

Storage Strategies
==================

Doctrine MongoDB ODM implements several different strategies for persisting changes
to mapped fields. These strategies apply to the following mapping types:

- :ref:`int`
- :ref:`float`
- :ref:`embed_many`
- :ref:`reference_many`

For collections, Doctrine tracks changes via the PersistentCollection class. The
strategies described on this page are implemented by the CollectionPersister
class. The ``increment`` strategy cannot be used for collections.

increment
---------

The ``increment`` strategy does not apply to collections but can be used for
``int`` and ``float`` fields. When using the ``increment`` strategy, the field
value will be updated using the `$inc`_ operator.

addToSet
--------

The ``addToSet`` strategy uses MongoDB's `$addToSet`_ operator to insert
elements into the array. This strategy is useful for ensuring that duplicate
values will not be inserted into the collection. Like the `pushAll`_ strategy,
elements are inserted in a separate query after removing deleted elements.

set
---

The ``set`` strategy uses MongoDB's `$set`_ operator to update the entire
collection with a single update query.

.. note::

    Doctrine's Collection interface is modeled after PHP's associative arrays,
    so they cannot always be represented as a BSON array. If the collection's
    keys are not sequential integers starting with zero, the ``set`` strategy
    will store the collection as a BSON object instead of an array. Use the
    `setArray`_ strategy if you want to ensure that the collection is always
    stored as a BSON array.

setArray
--------

The ``setArray`` strategy uses MongoDB's `$set`_ operator, just like the ``set``
strategy, but will first numerically reindex the collection to ensure that it is
stored as a BSON array.

pushAll
-------

The ``pushAll`` strategy uses MongoDB's `$pushAll`_ operator to insert
elements into the array. MongoDB does not allow elements to be added and removed
from an array in a single operation, so this strategy relies on multiple update
queries to remove and insert elements (in that order).

.. _atomic_set:

atomicSet
---------

The ``atomicSet`` strategy uses MongoDB's `$set`_ operator to update the entire
collection with a single update query. Unlike with ``set`` strategy there will
be only one query for updating both parent document and collection itself. This
strategy can be especially useful when dealing with high concurrency and 
:ref:`versioned documents <annotations_reference_version>`.

.. note::

    The ``atomicSet`` and ``atomicSetArray`` strategies may only be used for 
    collections mapped directly in a top-level document.

.. _atomic_set_array:

atomicSetArray
--------------

The ``atomicSetArray`` strategy works exactly like ``atomicSet`` strategy,  but 
will first numerically reindex the collection to ensure that it is stored as a 
BSON array.

.. note::

    The ``atomicSet`` and ``atomicSetArray`` strategies may only be used for 
    collections mapped directly in a top-level document.

.. _`$addToSet`: https://docs.mongodb.com/manual/reference/operator/update/addToSet/
.. _`$inc`: https://docs.mongodb.com/manual/reference/operator/update/inc/
.. _`$pushAll`: https://docs.mongodb.com/manual/reference/operator/update/pushAll/
.. _`$set`: https://docs.mongodb.com/manual/reference/operator/update/set/
.. _`$unset`: https://docs.mongodb.com/manual/reference/operator/update/unset/
