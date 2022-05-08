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
