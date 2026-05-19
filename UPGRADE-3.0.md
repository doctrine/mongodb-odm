# UPGRADE FROM 2.x to 3.0

## Aggregation

The new `Doctrine\ODM\MongoDB\Aggregation\Builder::getAggregation()` method
returns an `Doctrine\ODM\MongoDB\Aggregation\Aggregation` instance, comparable
to the `Query` class.

The `Doctrine\ODM\MongoDB\Aggregation\Builder::execute()` method was removed.

## ID generators

The `Doctrine\ODM\MongoDB\Id\AbstractIdGenerator` class has been removed. Custom
ID generators must implement the `Doctrine\ODM\MongoDB\Id\IdGenerator`
interface.

The `Doctrine\ODM\MongoDB\Id\UuidGenerator` class has been removed. Use a custom
generator to generate string UUIDs. For more efficient storage of UUIDs, use the
`Doctrine\ODM\MongoDB\Types\BinaryUuidType` type in combination with the
`Doctrine\ODM\MongoDB\Id\SymfonyUuidGenerator` generator.

## Metadata
The `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` class has been marked final and
will no longer be extendable.

The `boolean`, `integer`, and `int_id` mapping types have been removed. Use the
`bool`, `int`, and `int` types, respectively. These types behave exactly the
same.

The `NOTIFY` change tracking policy has been removed, we suggest switching to
`DEFERRED_EXPLICIT` instead. Consequentially `ClassMetadata::isChangeTrackingNotify` 
and `ClassMetadata::CHANGETRACKING_NOTIFY` have been removed as well. `UnitOfWork`
no longer implements the `PropertyChangedListener` interface.

`AttributeDriver` and `AnnotationDriver` no longer extend an abstract 
`AnnotationDriver` class defined in `doctrine/persistence` (or in ODM's 
compatibility layer)

## Proxy Class Name Resolution

The `Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver` interface has been
dropped in favor of the `Doctrine\Persistence\Mapping\ProxyClassNameResolver`
interface.

The `getClassNameResolver` method in `DocumentManager` has been removed. To
retrieve the mapped class name for any object or class string,  fetch metadata
for the class and read the class using `$metadata->getName()`. The metadata
layer is aware of these proxy namespace changes and how to resolve them, so
users should always go through the metadata layer to retrieve mapped class
names.

## Clearing all documents of a specific class

Clearing all documents of a given class with `DocumentManager::clear(Document::class)`
has been removed. Use `DocumentManager::detach` passing documents to be detached
to retain the functionality.

`Doctrine\ODM\MongoDB\Event\OnClearEventArgs`' methods `getDocumentClass` and 
`clearsAllDocuments` have been removed.

## Flush now consolidates writes per collection via `bulkWrite`

`UnitOfWork::commit()` previously issued separate driver calls for the
different write kinds within a single commit phase: an `insertMany` per
class for inserts, an `updateOne` *per document* for updates and upserts,
a `deleteOne` *per document* for removals, plus additional `updateOne`
calls per parent for `CollectionPersister` writes that fan out from
embedded / reference-many changes. As of 3.0, every scheduled write
against the same MongoDB collection is consolidated into a single
`Collection::bulkWrite()` call per commit phase, regardless of whether
the operations are inserts, updates, deletes, or collection-change
follow-ups.

Concretely:

- **Updates and deletes were one driver call per document.** Each
  scheduled update fired its own `updateOne` and each scheduled removal
  its own `deleteOne`. These are now folded into a single per-collection
  `bulkWrite` per phase.
- **`CollectionPersister` follow-up writes used to be separate.**
  Embedded / reference-many changes on a parent document previously
  emitted additional `updateOne` calls *after* the parent's own update.
  Those follow-ups are now queued onto the same `bulkWrite` as the
  parent's update when they target the same collection.
- **Inserts were already batched by `insertMany`** and remain a single
  command per class on the wire. The change for inserts is structural,
  not a wire-level reduction: an insert is now appended to the same
  per-collection `bulkWrite` that carries that collection's updates,
  deletes, and collection-change follow-ups within the same commit
  phase, rather than going through a dedicated `insertMany` path.

The visible effects:

- **Lifecycle event ordering.** Within a phase, `postPersist`,
  `postUpdate` and `postRemove` now fire **after** the entire phase's
  `bulkWrite` has succeeded, rather than interleaved per document.
  Listeners that relied on observing one document's post-event before
  another document in the same phase had been written must be reviewed.
  The per-document `preUpdate` callback still fires *before* the
  bulkWrite, so listeners can still mutate the change set there.
- **`LockException` semantics.** Versioned and lockable documents still
  raise `LockException` with the same timing as before. Internally, the
  refactor bypasses the consolidated bulk for these documents and issues
  a single-op `bulkWrite` per such document so the matched / modified /
  deleted counts can be inspected precisely.
- **Partial-failure exceptions.** A failed write surfaces as
  `MongoDB\Driver\Exception\BulkWriteException` (with `getWriteResult()`
  describing every op in the batch), where the 2.x ODM might have
  surfaced the same condition through the analogous error from
  `insertMany` / `updateOne` / `deleteOne`. The exception class and
  hierarchy are unchanged; only the aggregation of error info inside
  the result is broader.
- **Command count.** Tools and tests that count emitted MongoDB
  commands (e.g. via `Doctrine\ODM\MongoDB\APM\CommandLogger`) will see
  fewer commands per `flush()` whenever a commit touches the same
  collection with a mix of inserts, updates, deletes, or collection-
  change follow-ups: those now collapse to one `bulkWrite` per
  collection per phase instead of one driver call per document or per
  fan-out write.

The internal `DocumentPersister` and `CollectionPersister` classes are
`@internal` and `final`; their public method names are unchanged but the
queueing model now lives in `Doctrine\ODM\MongoDB\Persisters\BulkWriteQueue`.
Applications must not extend these classes.
