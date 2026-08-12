# Storage Strategies, Transactions & Lifecycle

## Table of contents

1. GridFS
2. Capped collections
3. Custom collections
4. Sharding
5. Transactions & concurrency
6. Change tracking policies
7. Storage strategies
8. Events / lifecycle callbacks
9. Schema/migration techniques
10. Time series collections

Not here: batching, `flush()` frequency, `clear()`, detached documents and the memory growth of a long import loop are in `setup-architecture.md` (UnitOfWork, and detach/merge/clear).

## 1. GridFS

For files exceeding the 16 MB document limit (chunks + metadata collections).

```php
#[ODM\File(bucketName: 'image')]
class Image
{
    #[ODM\Id]
    private ?string $id;

    #[ODM\File\Filename]
    private ?string $name;

    #[ODM\File\Metadata(targetDocument: ImageMetadata::class)] // must be #[ODM\EmbeddedDocument]
    private ImageMetadata $metadata;
}
```

Writing goes through the repository, not `persist()`/`flush()`, and returns a **proxy object**:

```php
$file = $dm->getRepository(Documents\Image::class)->uploadFromFile('/tmp/path/to/image', 'image.jpg');
```

Reading metadata works like a normal document; file *content* needs the repository's stream methods (`downloadToStream()`, `openDownloadStream()`).

## 2. Capped Collections

```php
#[Document(collection: ['name' => 'collname', 'capped' => true, 'size' => 100000, 'max' => 1000])]
class Category { /* ... */ }
```

Must be created explicitly (`$dm->getSchemaManager()->createDocumentCollection(Category::class)` or `odm:schema:create`) — lazy first-insert won't apply capping, and Doctrine can't convert an existing collection to capped.

## 3. Custom Collections

`#[EmbedMany]`/`#[ReferenceMany]` default to `ArrayCollection`, wrapped in a `PersistentCollection` once loaded. Supply a custom class via `collectionClass`, extending `ArrayCollection` or implementing `Collection` directly. If it needs extra constructor dependencies, override `createFrom()` and register a custom `PersistentCollectionFactory` on `Configuration::setPersistentCollectionFactory()`.

## 4. Sharding

```php
#[Document]
#[Index(keys: ['username' => 'asc'])]
#[ShardKey(keys: ['username' => 'asc'])]
class User { /* ... */ }
```

Once a shard key is defined, those fields are treated as **immutable**. Enabling sharding on the collection requires `odm:schema:shard` — not run implicitly by `odm:schema:create`/`update`.

## 5. Transactions & Concurrency

```php
$config->setUseTransactionalFlush(true);
$dm->flush(['withTransaction' => true]); // or false, per-call override
```

Transactions only wrap writes inside `flush()`; manual queries need the driver's own mechanism. Lifecycle events during a transactional flush expose a `Session` that must be passed to any query run from a listener — listeners fire only on the first attempt, not on retries.

**Optimistic locking** — `#[Version]` (root documents only; `int`/`decimal128`/`date`/`date_immutable`/`object_id`):

```php
$document = $dm->find(User::class, $id, LockMode::OPTIMISTIC, $expectedVersion); // throws LockException on mismatch
$dm->lock($document, LockMode::OPTIMISTIC, $expectedVersion);
```

**Pessimistic locking** — `#[Lock]` (`int` only, Doctrine-managed, no native MongoDB support):

```php
$dm->find($className, $id, LockMode::PESSIMISTIC_WRITE); // blocks concurrent read+write
$dm->lock($document, LockMode::PESSIMISTIC_READ);         // blocks concurrent write/lock
```

Watch for stale locks on failed requests and deadlocks.

## 6. Change Tracking Policies

- **Deferred Implicit** (default) — compares every managed document at commit; convenient, costly at scale.
- **Deferred Explicit** — only for documents explicitly `persist()`ed/cascaded; cheaper, but you lose automatic dirty-checking.
- **Notify** (deprecated, removal planned for 3.0) — document implements `NotifyPropertyChanged`, calling `_onPropertyChanged()` in every setter; best performance, most plumbing.

```php
#[Document]
#[ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class User { /* ... */ }
```

## 7. Storage Strategies

Set via `#[Field(strategy: '...')]`. `increment` is scalar-only (`int`/`float`/`decimal128`, via `$inc`). For `#[EmbedMany]`/`#[ReferenceMany]` collections: `addToSet` (no duplicates, separate remove/insert queries), `set`/`setArray` (`$set`, `setArray` forces a BSON array), `pushAll` (default; `$push`/`$each`), `atomicSet`/`atomicSetArray` (parent + collection updated in one query — top-level fields only, useful under concurrency with versioned documents).

## 8. Events / Lifecycle Callbacks

Events (constants on `Events`): `preRemove`, `postRemove`, `prePersist`, `postPersist`, `preUpdate`, `postUpdate`, `preLoad`, `postLoad`, `preFlush`, `postFlush`, `onFlush`, `onClear`, `documentNotFound`, `postCollectionLoad`, etc.

```php
#[Document]
#[HasLifecycleCallbacks] // required, or callback attributes below are silently ignored
class User
{
    #[PrePersist]
    public function onPrePersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs): void { /* ... */ }
}
```

Or a separate listener: `$dm->getEventManager()->addEventListener([Events::preUpdate], new MyEventListener());`. Notes: modifying a document inside `preUpdate` requires `$dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($class, $document)`; 2.16's `onClear` args expose `getDocumentClass()`/`clearsAllDocuments()` since `$dm->clear($documentName)` can target one class (that argument is itself deprecated since 2.4 — prefer a plain `clear()`); `documentNotFound` fires when a **proxy** can't initialize, and listeners can call `disableException()`.

## 9. Schema/Migration Techniques

```php
$schemaManager->createCollections();                    // all classes
$schemaManager->createDocumentCollection(Person::class); // one class
$schemaManager->ensureIndexes();
```

Explicit creation is required for collections needing options (capped, validation, encryption) — otherwise MongoDB creates plain collections lazily on first insert.

Evolving mappings without a hard migration: `#[AlsoLoad('name')]` on a field (fall back to an old field name on hydration — **doesn't affect queries**, so query both field names during a migration window); `#[AlsoLoad([...])]` on a method (transform old fields into new ones); `#[Field(notSaved: true)]` (read a legacy field without writing it back); `#[PostLoad]`/`#[PrePersist]` for migration logic.

## 10. Time Series Collections

ODM 2.10+, MongoDB **5.0+**. Needs a time field and measurement field(s); metadata is fixed once created (fields *inside* an embedded metadata document can still be added later).

```php
#[ODM\Document]
#[ODM\TimeSeries(timeField: 'time', metaField: 'sensorId')]
readonly class Measurement
{
    #[ODM\Field(type: 'date_immutable')]
    public \DateTimeImmutable $time;

    #[ODM\Field(type: 'int')]
    public int $sensorId;
}
```

Persist/query/aggregate/remove normally. Granularity (default `seconds`) controls bucket size and must match write frequency — coarser granularity (`hours`) risks slower queries if buckets span too much time (e.g. a whole month).
