# Pagination

## 1. What paginators are for

Four paginator classes live in `Doctrine\ODM\MongoDB\Pagination` (since ODM 2.18). Two styles are available, each for the query builder and the aggregation builder:

| Style | Query builder | Aggregation builder |
|---|---|---|
| Offset | `QueryOffsetPaginator` | `AggregationOffsetPaginator` |
| Cursor | `QueryCursorPaginator` | `AggregationCursorPaginator` |

All implement `IteratorAggregate`, and each one clones the builder it receives, so the builder is not mutated and the paginator can be iterated more than once. They are read-only helpers: they do not persist anything.

Use offset pagination for numbered pages and random access. Use cursor pagination for infinite scroll or previous/next navigation on large collections.

## 2. Offset pagination

Takes a `page` number, where the first page is 1, a `perPage` value (24 by default), and implements `Countable`. `count($paginator)` returns the **number of pages**, not the number of items or the total number of results. Counting runs a separate query: a `count` query for the query builder, or an appended `$count` stage writing to a `numDocuments` field for the aggregation builder. The page itself uses `skip`/`limit` options or `$skip`/`$limit` stages.

```php
use Doctrine\ODM\MongoDB\Pagination\QueryOffsetPaginator;

$qb = $dm->createQueryBuilder(User::class)
    ->field('status')->equals('active')
    ->sort('createdAt', 'desc');

$paginator = new QueryOffsetPaginator($qb, page: 2, perPage: 20);

foreach ($paginator as $user) {
    // 20 users
}

$pageCount = count($paginator); // number of pages
```

## 3. Cursor pagination

Takes `after` (`null` for the first page), `perPage` (24 by default) and `field` (`id` by default, mapped to `_id`). Both paginators read the sort direction from the builder and keep the documents after the cursor (`>` for ascending, `<` for descending). The builder must sort on `field` as the primary sort. Extract the cursor of the next page from the last item.

```php
use Doctrine\ODM\MongoDB\Pagination\QueryCursorPaginator;

$qb = $dm->createQueryBuilder(User::class)
    ->field('status')->equals('active')
    ->sort('id', 'asc');

$paginator = new QueryCursorPaginator($qb, perPage: 20);

$last = null;
foreach ($paginator as $user) {
    $last = $user;
}

$next = new QueryCursorPaginator($qb, after: $last->id, perPage: 20);
```

## 4. Gotchas

- Always add a deterministic sort, otherwise pages can duplicate or skip documents.
- Do not add skip/limit yourself, because the paginator adds them for you and an existing limit also affects the count.
- Both paginators read the sort direction from the builder and assume ascending if nothing is sorted. The cursor field must be the primary sort and should be unique.
- For aggregations, results follow the builder hydration setting. Call `hydrate()` on the builder before creating the paginator to get objects instead of arrays.
- `count()` on an offset paginator runs one extra query the first time and caches the page count on the instance.
