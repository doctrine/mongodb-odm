# Querying & Aggregation

## Table of contents

1. The Query Builder
2. findAndUpdate / findAndModify
3. Upserting documents
4. Geospatial queries
5. Filters
6. The Aggregation Builder
7. Gotchas

## 1. The Query Builder

```php
$qb = $dm->createQueryBuilder(User::class);
```

The class can also be supplied later via `find()`, `updateOne()`, `updateMany()`, or `remove()` (`update()` is deprecated). For simple lookups, skip the builder: `$dm->find(User::class, $id)`, `$dm->getRepository(User::class)->findBy(['type' => 'employee'])`. `Query::execute()` returns an `Iterator`; use `getSingleResult()` for one document.

### Field / conditional operators

`field($name)` then: `where($javascript)`, `in($values)`, `notIn($values)`, `equals($value)`, `notEqual($value)`, `gt/gte/lt/lte($value)`, `range($start, $end)`, `size($size)`, `exists($bool)`, `type($type)`, `all($values)`, `mod($mod)`, `addOr($expr)`, `references($document)`, `includesReferenceTo($document)`.

```php
$qb = $dm->createQueryBuilder(User::class)->field('type')->equals('admin')->field('active')->equals(true);

// $or, via addOr() + expr()
$qb = $dm->createQueryBuilder(User::class);
$qb->addOr($qb->expr()->field('subscriber')->equals(true));
$qb->addOr($qb->expr()->field('inTrial')->equals(true));

// owning-side reference matches
$qb = $dm->createQueryBuilder(Article::class)->field('user')->references($user);
$qb = $dm->createQueryBuilder(User::class)->field('accounts')->includesReferenceTo($account);
```

### Text search

Requires a `text` index (`#[Index(keys: ['description' => 'text'])]`):

```php
$qb = $dm->createQueryBuilder(Document::class)->text('words you are looking for');
$qb = $dm->createQueryBuilder(Document::class)->selectMeta('score', 'textScore')->text('words'); // computed relevance score
$qb = $dm->createQueryBuilder(Document::class)->text('parole che stai cercando')->language('it'); // stemming language
```

### Selecting, hints, distinct, limit/skip/sort

```php
$qb = $dm->createQueryBuilder(User::class)->select('username', 'password');
$qb = $dm->createQueryBuilder(User::class)->hint('user_pass_idx'); // combine with select() for a covered query
$ages = $dm->createQueryBuilder(User::class)->distinct('age')->getQuery()->execute(); // plain array, NOT an Iterator

$posts = $dm->createQueryBuilder(BlogPost::class)->limit(20)->skip(40)->getQuery()->execute(); // page 3, 20/page
$qb = $dm->createQueryBuilder(Article::class)->sort('createdAt', 'desc'); // repeated calls stack in call order
```

Gotchas on `distinct()`: it can't combine with `sort()` (MongoDB's `distinct` doesn't support server-side sorting) — sort the PHP array yourself; and `execute()` hands back a plain `array`, so calling `->toArray()` on it is a fatal error.

### Debugging

`$dm->createQueryBuilder(User::class)->getQuery()->debug()` returns the fully prepared array actually sent to the driver (field renames, discriminators, applied filters) — useful when a fluent query misbehaves.

### Refresh / read-only / hydration

- **`refresh()`** — forces ODM to overwrite an already-managed instance with fresh data instead of keeping the (possibly stale) in-memory copy.
- **`readOnly()`** — like `refresh()` but returns a *new*, unmanaged instance; references still managed normally (not deep). Re-attach later with `merge()`, not `persist()`.
- Both are no-ops with `hydrate(false)`.
- **`hydrate(false)`** — returns raw arrays instead of objects.
- **`setRewindable(false)`** — skips the caching-iterator ODM normally wraps around non-rewindable Mongo cursors, saving memory but making a **second iteration throw**.

### Update queries (atomic modifiers)

`set($name, $value, $atomic = true)`, `setNewObj($newObj)`, `inc`, `unsetField`, `push`, `addToSet`, `popFirst`, `popLast`, `pull`, `pullAll`. Gotcha: updates default to **one document** — call `updateMany()` for more.

```php
$dm->createQueryBuilder(User::class)->updateOne()->field('password')->set('newpassword')->field('username')->equals('jwage')->getQuery()->execute();
$dm->createQueryBuilder(User::class)->updateMany()->field('someField')->set('newValue')->field('username')->equals('sgoettschkes')->getQuery()->execute();

// $atomic=false replaces the matched value field-by-field instead of via $set
$dm->createQueryBuilder(User::class)->updateOne()->field('username')->set('jwage', false)->field('username')->equals('jwage')->getQuery()->execute();

// whole-document replace
$dm->createQueryBuilder(User::class)->setNewObj(['username' => 'jwage'])->field('username')->equals('jwage')->getQuery()->execute();

$dm->createQueryBuilder(Package::class)->field('id')->equals('theid')->field('downloads')->inc(1)->getQuery()->execute();
$dm->createQueryBuilder(User::class)->updateMany()->field('login')->unsetField()->exists(true)->getQuery()->execute();
$dm->createQueryBuilder(Article::class)->updateOne()->field('tags')->push('tag5')->field('id')->equals('theid')->getQuery()->execute();
$dm->createQueryBuilder(Article::class)->updateMany()->field('tags')->pullAll(['tag1', 'tag2'])->getQuery()->execute();
```

`pipeline()` (since 2.15) accepts an Aggregation Builder, `Pipeline`, or array for atomic updates — but only with `updateOne()`, `updateMany()`, `findAndUpdate()`. This is the way to compute a new field value from other fields on the document server-side, instead of loading and flushing.

```php
$dm->createQueryBuilder(User::class)->remove()->field('num_logins')->equals(0)->getQuery()->execute();
```

## 2. findAndUpdate / findAndModify

Atomic "find, modify, return" of **at most one document** — returns the pre-update document by default; add `returnNew()` for the post-update one.

```php
$job = $dm->createQueryBuilder(Job::class)
    ->findAndUpdate()->returnNew()
    ->field('in_progress')->equals(false)
    ->sort('priority', 'desc')
    ->field('started')->set(new \MongoDB\BSON\UTCDateTime())
    ->field('in_progress')->set(true)
    ->getQuery()->execute();

$job = $dm->createQueryBuilder(Job::class)->findAndRemove()->sort('priority', 'desc')->getQuery()->execute();
```

Gotcha: if you don't need the affected document returned, use a plain `updateMany()`/`remove()` instead — findAndModify-style calls only ever touch one document.

## 3. Upserting Documents

Set the identifier ahead of time and persist normally — ODM issues an upsert instead of an insert, so you skip fetching the document first:

```php
$article = new Article();
$article->setId($articleId);
$article->incrementNumViews();
$dm->persist($article);
$dm->flush();
```

## 4. Geospatial Queries

```php
#[Document]
#[Index(keys: ['coordinates' => '2d'])]
class City
{
    #[EmbedOne(targetDocument: Coordinates::class)]
    public ?Coordinates $coordinates;
}

$cities = $dm->createQuery(City::class)->field('coordinates')->near(-120, 40)->execute();
```

`near($x, $y, $minDistance = null, $maxDistance = null)` also accepts a single GeoJSON point for `2dsphere` (omit `$y`). `nearSphere(...)` is the spherical-distance equivalent. Other `field()` geo ops: `geoWithin($geometry)` (GeoJSON), `geoWithinBox`/`geoWithinCenter`/`geoWithinPolygon` (**`2d` only**), `geoWithinCenterSphere` (works with **both** `2d` and `2dsphere`), `geoIntersects($geometry)`. For nearest-first queries with a distance output, use the `$geoNear` **aggregation** stage instead — there's no query-builder equivalent.

## 5. Filters

A "filter" adds extra criteria to **every** query for matching documents, wherever it originates — even referenced-document loads.

```php
class MyLocaleFilter extends \Doctrine\ODM\MongoDB\Query\Filter\BsonFilter
{
    public function addFilterCriteria(\Doctrine\ODM\MongoDB\Mapping\ClassMetadata $targetDocument): array
    {
        if (!$targetDocument->reflClass->implementsInterface('LocaleAware')) {
            return [];
        }
        return ['locale' => $this->getParameter('locale')];
    }
}

$config->addFilter('locale', MyLocaleFilter::class, ['locale' => 'en']); // 3rd arg: optional default params

$filter = $dm->getFilterCollection()->enable('locale');
$filter->setParameter('locale', ['$in' => ['en', 'fr']]);
$dm->getFilterCollection()->disable('locale');
```

Gotcha: changing filters has **no effect on already-managed documents** — clear the `DocumentManager` and re-fetch.

## 6. The Aggregation Builder

```php
$builder = $dm->createAggregationBuilder(\Documents\Orders::class);
$builder
    ->match()
        ->field('purchaseDate')->gte($from)->lt($to)
        ->field('user')->references($user)
    ->group()
        ->field('id')->expression('$user')
        ->field('numPurchases')->sum(1)
        ->field('amount')->sum('$amount');

$result = $builder->getAggregation(); // or ->getPipeline() to inspect the raw array
```

Stage methods (`match()`, `group()`, etc.) each return a stage-specific builder exposing `field()` plus expression methods, chained fluently; `DateTime` values auto-convert to `UTCDateTime`. Nest expressions with `$builder->expr()`.

### Hydrating results as objects

By default results are plain arrays (a pipeline's shape can differ from the source document). Map a `#[QueryResultDocument]` (looks like a document, can't be persisted) and call `hydrate()`:

```php
#[QueryResultDocument]
class UserPurchases
{
    #[ReferenceOne(targetDocument: User::class, name: '_id')]
    private User $user;

    #[Field(type: 'int')]
    private int $numPurchases;
}

$builder->hydrate(\Documents\UserPurchases::class)->match()/* ... */->group()/* ... */;
```

`rewindable(false)` avoids the caching-iterator memory cost (same rationale as the query builder) but a second iteration throws; `getAggregation()` always returns a fresh, re-executable instance regardless.

### Supported raw pipeline stages

`$addFields`, `$bucket`, `$bucketAuto`, `$collStats`, `$count`, `$densify`, `$facet`, `$fill`, `$geoNear`, `$graphLookup`, `$group`, `$indexStats`, `$limit`, `$lookup`, `$match`, `$merge`, `$out`, `$project`, `$redact`, `$replaceRoot`, `$replaceWith`, `$sample`, `$search`, `$set`, `$setWindowFields`, `$skip`, `$sort`, `$sortByCount`, `$vectorSearch`, `$unionWith`, `$unset`, `$unwind`. Check your MongoDB server version supports a given stage (e.g. `$vectorSearch` needs Atlas).

Notable gotchas by stage:

- **`$geoNear`** — must be **first**, needs exactly one geo index, `distanceField` required: `->geoNear(120, 40)->spherical(true)->distanceField('distance')->distanceMultiplier(6378.137)`.
- **`$lookup`** — one-to-many on MongoDB 3.2 returns empty unless `unwind()`ed first and `group()`ed after; one-to-one still returns an array, flatten with `unwind()`; can't target a reference nested in an embedded document; can't be used with `DBRef`-stored references (use `id`/`ref`). Same DBRef limitation applies to `$graphLookup`, whose `connectFromField` target must be the same class the lookup runs against.
- **`$merge`/`$out`** — must be **last**. `$merge` upserts by match keys (needs a unique index on them); `$out` atomically replaces the target collection.
- **`$search`**/**`$vectorSearch`** — Atlas only, must be **first**, need `#[SearchIndex]`/`#[VectorSearchIndex]` (see `indexes-search.md`/`vector-search.md`).
- **`$replaceRoot`/`$replaceWith`** — replace the **entire** document, including `_id`.
- **`$unionWith`** — combine two pipelines' results, including duplicates.

## 7. Gotchas

1. **`distinct()` + `sort()` don't combine**, and `distinct()` returns a plain `array` — no `toArray()`.
2. **Updates default to a single document** — use `updateMany()`.
3. **Pipeline updates only work with `updateOne()`/`updateMany()`/`findAndUpdate()`.**
4. **`findAndUpdate` returns the pre-update document unless `returnNew()`.**
5. **Cursors aren't rewindable** — `setRewindable(false)`/`rewindable(false)` save memory but break a second iteration.
6. **`refresh()`/`readOnly()` are no-ops with `hydrate(false)`.**
7. **`readOnly()` isn't deep** — re-attach with `merge()`, not `persist()`.
8. **Filters don't retroactively affect managed documents.**
9. **Legacy geo operators (`geoWithinBox`/`Center`/`Polygon`) only work with `2d`**, not `2dsphere`. `geoWithinCenterSphere` works with both.
10. **`$geoNear`/`$search`/`$vectorSearch` must be first; `$merge`/`$out` must be last.**
11. **`$lookup`/`$graphLookup` can't use `DBRef`-stored references.**
