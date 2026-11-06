.. _pagination:

Pagination
==========

When an application lists documents, it usually displays one page at a time
instead of the whole collection. Loading everything wastes memory, bandwidth
and database time, and most screens only show a small part of the result set.
Paginators keep the cost of a page bounded by the number of items per page and
let MongoDB do the skipping, limiting and counting.

Doctrine MongoDB ODM provides 4 paginator classes. There are 2 pagination
styles, and each style is available for the query builder and the aggregation
builder:

- :ref:`Offset pagination <pagination_offset>` uses a page number and a number
  of items per page.
- :ref:`Cursor pagination <pagination_cursor>` uses the value of the last item
  of the previous page.

All paginators implement ``IteratorAggregate``, so they can be iterated directly
with ``foreach``. Each paginator clones the builder it receives, which means the
original builder is not modified and the paginator can be iterated more than
once.

.. note::

    Paginators are available since ODM 2.18.

.. _pagination_use_cases:

What paginators are for
-----------------------

Paginators are read-only query helpers. They do not change documents, they do
not persist anything, and they do not modify the builder you pass to them. They
are useful whenever an application lists documents:

- A list screen with numbered pages and previous/next links.
- An API endpoint that accepts ``page`` and ``perPage`` parameters, or an
  ``after`` cursor parameter.
- Infinite scrolling or a "load more" button.
- A large aggregation pipeline whose result must be displayed in chunks.

.. _pagination_offset:

Offset pagination
-----------------

Offset pagination takes a page number, where the first page is 1, and a number
of items per page (24 by default). ``QueryOffsetPaginator`` wraps a query builder and
``AggregationOffsetPaginator`` wraps an aggregation builder. Both classes
implement ``Countable``, and ``count()`` returns the number of pages.

The results of the current page are fetched with the ``skip`` and ``limit``
options of the query, or with the ``$skip`` and ``$limit`` stages of the
pipeline. Counting runs a separate query: a ``count`` query for the query
builder, or a ``$count`` stage that writes the result to a ``numDocuments``
field for the aggregation builder. Hydration is disabled for that count
pipeline.

Query builder
~~~~~~~~~~~~~

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Pagination\QueryOffsetPaginator;

    $queryBuilder = $dm->createQueryBuilder(User::class)
        ->field('status')->equals('active')
        ->sort('createdAt', 'desc');

    $paginator = new QueryOffsetPaginator($queryBuilder, page: 2, perPage: 20);

    foreach ($paginator as $user) {
        // use the 20 users of the second page
    }

    $pageCount = count($paginator); // number of pages

Aggregation builder
~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Pagination\AggregationOffsetPaginator;

    $aggregationBuilder = $dm->createAggregationBuilder(User::class);
    $aggregationBuilder
        ->match()
            ->field('status')->equals('active')
        ->sort('createdAt', -1);

    $paginator = new AggregationOffsetPaginator($aggregationBuilder, page: 2, perPage: 20);

    foreach ($paginator as $user) {
        // use the user
    }

    $pageCount = count($paginator); // number of pages

The results of the page are hydrated according to the hydration setting of the
builder. See :ref:`aggregation_builder_hydration` for details.

.. note::

    Always add a deterministic sort to the builder. Without it, MongoDB returns
    documents in natural order, which is not stable between two executions and
    can result in duplicated or skipped documents across pages.

    Do not add ``skip()``/``limit()`` (or ``$skip``/``$limit``) to the builder
    yourself. The paginator adds them for you, and a limit already present in
    the builder also affects the count.

.. _pagination_cursor:

Cursor pagination
-----------------

Cursor pagination returns the documents that come after a cursor value. It
scales better than offset pagination on large collections because MongoDB can
use an index on the cursor field instead of scanning the skipped documents.

``QueryCursorPaginator`` wraps a query builder and
``AggregationCursorPaginator`` wraps an aggregation builder. Their constructor
accepts:

- ``after``: the cursor value. Use ``null`` (the default) for the first page,
  then the value of the cursor field on the last item of the previous page.
- ``perPage``: the number of items per page (24 by default).
- ``field``: the field used as cursor (``id`` by default, which maps to the
  identifier field).

Both paginators read the sort direction from the builder. With an ascending
sort they keep the documents greater than ``after``; with a descending sort they
keep the documents lower than ``after``. If the builder does not sort, ascending
is assumed. Always sort on the cursor field as the primary sort: otherwise the
order of the results does not match the comparison and pages are inconsistent or
empty. The cursor field should be unique, for example the identifier field,
otherwise documents that share the same value can be skipped.

Query builder
~~~~~~~~~~~~~

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Pagination\QueryCursorPaginator;

    $queryBuilder = $dm->createQueryBuilder(User::class)
        ->field('status')->equals('active')
        ->sort('id', 'asc');

    $paginator = new QueryCursorPaginator($queryBuilder, perPage: 20);

    $lastUser = null;
    foreach ($paginator as $user) {
        // use the user
        $lastUser = $user;
    }

    // Build the paginator for the next page from the last item
    $nextPage = new QueryCursorPaginator($queryBuilder, after: $lastUser->id, perPage: 20);

Aggregation builder
~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Pagination\AggregationCursorPaginator;

    $aggregationBuilder = $dm->createAggregationBuilder(User::class);
    $aggregationBuilder
        ->match()
            ->field('status')->equals('active')
        ->sort('id', 1);

    $paginator = new AggregationCursorPaginator($aggregationBuilder, perPage: 20);

    $lastUser = null;
    foreach ($paginator as $user) {
        // use the user
        $lastUser = $user;
    }

    $nextPage = new AggregationCursorPaginator(
        $aggregationBuilder,
        after: $lastUser->id,
        perPage: 20,
    );

.. note::

    The results of an aggregation are hydrated according to the hydration
    setting of the builder. Call ``hydrate()`` on the builder before creating
    the paginator if you want document objects instead of arrays.

Choosing between offset and cursor pagination
---------------------------------------------

Use offset pagination when the application needs the total number of pages, or
when users can jump to an arbitrary page. The count query has a cost on large
collections, and the cost of ``skip`` grows with the page number because
MongoDB scans the skipped documents.

Use cursor pagination for infinite scrolling or previous/next navigation. Each
page costs roughly the same, whatever its position, but there is no page count
and no way to jump to an arbitrary page.

Practical tips
--------------

- Clamp the page number to at least 1 before creating an offset paginator.
- Keep ``perPage`` moderate. A page is held in memory, and very large pages
  defeat the point of paginating.
- Reuse the same builder for every page, without limit or offset.
- Do not call ``count()`` when the screen only needs previous/next navigation.
  Cursor pagination does not need a count at all.
- For cursor pagination, sort on the cursor field as the primary sort, in the
  direction you want. Both paginators read the direction from the builder. Use a
  unique field, and index it.
