.. _collection_strategies:

Collection Strategies
=====================

Doctrine MongoDB ODM implements four different strategies for persisting changes
to collections of embedded documents or references. These strategies apply to
the following mapping types:

- :ref:`embed_many`
- :ref:`reference_many`

Internally, Doctrine tracks changes via the PersistentCollection class. The
strategies described on this page are implemented by the CollectionPersister
class.

``addToSet``
------------

The ``addToSet`` strategy uses MongoDB's `$addToSet`_ operator to insert
elements into the array. This strategy is useful for ensuring that duplicate
values will not be inserted into the collection. Like the `pushAll`_ strategy,
elements are inserted in a separate query after removing deleted elements.

``set``
-------

The ``set`` strategy uses MongoDB's `$set`_ operator to update the entire
collection with a single update query.

.. note::

    Doctrine's Collection interface is modeled after PHP's associative arrays,
    so they cannot always be represented as a BSON array. If the collection's
    keys are not sequential integers starting with zero, the ``set`` strategy
    will store the collection as a BSON object instead of an array. Use the
    `setArray`_ strategy if you want to ensure that the collection is always
    stored as a BSON array.

``setArray``
------------

The ``setArray`` strategy uses MongoDB's `$set`_ operator, just like the ``set``
strategy, but will first numerically reindex the collection to ensure that it is
stored as a BSON array.

``pushAll``
------------

The ``pushAll`` strategy uses MongoDB's `$pushAll`_ operator to insert
elements into the array. MongoDB does not allow elements to be added and removed
from an array in a single operation, so this strategy relies on multiple update
queries to remove and insert elements (in that order).

.. _`$addToSet`: http://docs.mongodb.org/manual/reference/operator/addToSet/
.. _`$pushAll`: http://docs.mongodb.org/manual/reference/operator/pushAll/
.. _`$set`: http://docs.mongodb.org/manual/reference/operator/set/
.. _`$unset`: http://docs.mongodb.org/manual/reference/operator/unset/
