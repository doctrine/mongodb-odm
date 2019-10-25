# UPGRADE FROM 1.x to 2.0

## PHP requirements

* MongoDB ODM 2.0 requires PHP 7.2 or newer. If you're not running PHP 7.2 yet,
  it's recommended that you upgrade to PHP 7.2 before upgrading ODM. You can use
  `alcaeus/mongo-php-adapter` to use ODM 1.x with PHP 7.
* Most methods have been updated to include type hints where applicable. Please
  check your extension points to make sure the function signatures are correct.
* All files in ODM 2.0 use strict typing. Please make sure to not rely on type
  coercion.

## MongoDB driver change

* MongoDB ODM 2.0 now requires the new MongoDB extension, `ext-mongodb`. If
  you've been using `alcaeus/mongo-php-adapter` you can remove it completely and
  use ODM 2.0 directly.
* `doctrine/mongodb` is no longer used by ODM. If you've been relying on its
  functionality, please update accordingly. Most utility classes from 
  `doctrine/mongodb` have been merged into their ODM counterparts. Classes
  handling connections to MongoDB servers are being replaced by the MongoDB 
  library (`mongodb/mongodb`).
* The constructor signature of `Doctrine\ODM\MongoDB\DocumentManager` as well as
  the `create`, `getClient`, `getDocumentDatabase`, `getDocumentCollection`, and
  `getDocumentCollections` methods have been updated to handle classes from
  `mongodb/mongodb` instead of `doctrine/mongodb`.
  
## Commands

* The `--depth` option in the `odm:query` command was dropped without
  replacement.
* The `--timeout` option for all schema commands was dropped. You should use the
  `--maxTimeMs` option instead.

## Aggregation builder
* The `debug` method in `Doctrine\ODM\MongoDB\Aggregation\Stage\Match` was dropped
  without replacement.

## Configuration

* The `setDefaultRepositoryClassName` and `getDefaultRepositoryClassName` methods
  in `Doctrine\ODM\MongoDB\Configuration` have been renamed to
  `setDefaultDocumentRepositoryClassName` and
  `getDefaultDocumentRepositoryClassName` respectively.
* The `setAutoGenerateProxyClasses` and `setAutoGenerateHydratorClasses` methods
  no longer accept `bool` arguments. Use one of the `AUTOGENERATE_*` constants
  from the `Configuration` class instead.
* The `setRetryConnect` and `setRetryQuery` methods have been dropped without
  replacement. You should implement proper error handling instead of simply
  re-running queries or connection attempts.
* The `AUTOGENERATE_ALWAYS` and `AUTOGENERATE_NEVER` generation strategies for
  proxy objects have been removed. Use `AUTOGENERATE_EVAL` and
  `AUTOGENERATE_FILE_NOT_EXISTS` instead. This does not affect hydrator or
  collection generation strategies.

## Cursor changes

* Methods that previously returned a `MongoCursor` instance no longer return a
  cursor directly but rather compose the cursor in iterators implementing the
  `Doctrine\ODM\MongoDB\Iterator\Iterator` interface. This class provides a
  `toArray` method in addition to the methods provided in PHP core's `Iterator`
  interface.
* The `Doctrine\ODM\MongoDB\Cursor`, `Doctrine\ODM\MongoDB\EagerCursor`, and
  `Doctrine\ODM\MongoDB\CommandCursor` classes has been dropped without
  replacement. You should always use the iterator interface mentioned above in
  type hints.

## Document manager

* The `flush` method in `Doctrine\ODM\MongoDB\DocumentManager` no longer takes a
  `$document` as its first argument. Flushing single documents has been removed.
  If you don't want to implicitly flush changes in all documents to the 
  database, consider using a different changeset computation strategy (e.g. 
  explicit).
* The `createDBRef` method has been dropped in favor of `createReference`. This
  new method handles the creation of different kinds of references, not only
  `DBRef`.
* The `$id` argument in `Doctrine\ODM\MongoDB\DocumentPersister::refresh` has
  been dropped as it was never used.

## GridFS

GridFS support has been adapted to the new GridFS specification. The following
are no longer possible:
* Metadata must be stored in a `metadata` embedded document. Storing additional 
  metadata in the root document is no longer supported. If you have documents 
  that store metadata in the root document, migrate those documents to the new 
  format.
* New files are no longer persisted by flushing the DocumentManager. Instead,
  they are uploaded using special methods in the new
  `Doctrine\ODM\MongoDB\Repository\GridFSRepository` class.
* The mapping of GridFS files has changed significantly: GridFS files are no
  longer mapped as documents but as files and there are specific annotations for
  the GridFS metadata fields.
* The file's binary data can be downloaded by using special methods in the
  `Doctrine\ODM\MongoDB\Repository\GridFSRepository` class. It is no longer
  directly accessible from the document.

## Mapping

### General mapping changes

* The `Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo` class was dropped and its
  functionality integrated into `Doctrine\ODM\MongoDB\Mapping\ClassMetadata`.
* The `safe` option for index creation has been dropped without replacement as
  this is no longer applicable to current versions of MongoDB.
* Combining `repositoryMethod` with `sort` or `limit` options on inverse
  references is no longer supported due to changes in the MongoDB driver.
* Repository methods used with `repositoryMethod` in inverse references now have
  to return an iterator of type `Doctrine\ODM\MongoDB\Iterator\Iterator` instead
  of a cursor.
* References use `dbref` as the default type when storing references instead of
  `dbRefWithDB`. This omits the `$db` key in the `DBRef` object when possible.
  This only has implications if you consume documents outside of ODM and require
  the `$db` key to be set or if you are using multiple databases to store data.
* The `dropDups` option on indexes has been dropped without replacement since it
  is no longer respected by MongoDB.

### YAML mapping

The YAML mapping drivers have been removed. Please map your documents using XML
or annotations. To migrate away from YAML mappings, first update to MongoDB ODM
1.3 and convert your mappings using the provided command.

### XML mapping

* The XML driver validates the schema before applying the mapping. If your XML
  mapping file is invalid, ODM will not load it and throw an exception.
* Most mapping types now use `choice` instead of `sequence`, allowing you to map
  your documents in the order you prefer.
* The `writeConcern` attribute in the `document` element has been renamed to
  `write-concern` for consistency with other attributes.
* The `require-indexes` attribute in the `document` element has been dropped
  without replacement.
* The `slave-okay` attribute in the `document` element has been dropped. Use the
  `read-preference` element for more fine-grained read control.
* The `field` element no longer supports mapping identifiers. Use the `id`
  element instead. The `id` and `id-generator-options` attributes of `field`
  have been dropped in the process.
* The `file` attribute of `field` has been dropped. More information on this is
  available in the GridFS update chapter.
* The `distance` attribute of `field` has been dropped without replacement. If
  you've been using `geoNear` queries, you should refactor them to use the 
  aggregation framework and `$geoNear` pipeline operator.
* The `fieldName` attribute of the `field`, `embed-one`, `embed-many`,
  `reference-one`, and `reference-many` elements has been renamed to
  `field-name` for consistency.
* The `field`, `embed-one`, `embed-many`, `reference-one`, and `reference-many`
  elements now support the `not-saved`, `nullable`, and `also-load` mapping
  options.
* The `strategy` attribute for `embed-one` and `reference-one` has been dropped.
  It was never used and does not apply to single-document relationships.
* The `cascade` element for documents now supports cascading `detach` operations
  to related documents.
* The `safe` attribute of `index` has been dropped without replacement.

### Annotation mapping

* Combining `@Document`, `@EmbeddedDocument`, `@File`, `@MappedSuperclass` and
  `@QueryResultDocument` annotations on a single class will result in a 
  `MappingException`.
* The `$safe` property in the `@Index` and `@UniqueIndesx` annotations has been 
  dropped without replacement.
* The following annotation classes have been dropped in favor of specifying the
  `type` attribute in the `Field` annotation: `Bin`, `BinCustom`, `BinFunc`,
  `BinMD5`, `BinUUID`, `BinUUIDRFC4122`, `Bool`, `Boolean`, `Collection`,
  `Date`, `Float`, `Hash`, `Increment`, `Int`, `Integer`, `Key`, `ObjectId`,
  `Raw`, `String`, `Timestamp`.
* The `NotSaved` annotation has been dropped in favor of the `notSaved`
  attribute on the `Field` annotation. The `notSaved` attribute can also be
  applied to reference and embed mappings.
* The `$name` and `$fieldName` properties in the `DiscriminatorField` annotation
  class have been dropped. The field name is now passed via the default `$value`
  property.
* The `Distance` annotation class has been dropped without replacement. If
  you've been using `geoNear` queries, you should refactor them to use the 
  aggregation framework and `$geoNear` pipeline operator.
* The `DoctrineAnnotations.php` loader has been removed. You should register a
  class loader in the `AnnotationRegistry` instead if you are using the ODM
  without a framework integration (e.g. `doctrine/mongodb-odm-bundle`).
* The `requireIndexes` option in the `Document` annotation has been dropped
  without replacement.
* The `slaveOkay` option in the `Document` annotation has been dropped. Use a
  `ReadPreference` annotation for more fine-grained read control.
* The `File` annotation class is no longer a field-level annotation but now used
  on a class. More information on this is available in the GridFS update
  chapter.
* The `simple` option for `ReferenceOne` and `ReferenceMany` annotations has
  been dropped. Use the `storeAs` option with an appropriate value instead.
* The `registerAnnotationClasses` method in
  `Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver` has been dropped
  without replacement. You should register a class loader in the
  `AnnotationRegistry` instead.

### Same-namespace resolution dropped

With same-namespace resolution, the metadata driver would look for a class of
that name in the same namespace if the given class name didn't contain a
namespace separator (`\`). This is no longer supported, use fully qualified
class names or the `::class` constant instead:

```php
/**
 * @ODM\Document(repositoryClass=UserRepository::class)
 */
class User
{
    /**
     * @ODM\ReferenceMany(targetDocument=Group::class)
     */
    private $groups;
}
```

This affects the `repositoryClass` attribute in documents, `targetDocument` in
references and embedded relationships as well as class names in discriminator
maps.

## Proxy objects

The proxy implementation no longer relies on Doctrine proxies but rather
the [Proxy Manager](https://github.com/ocramius/ProxyManager) library by
ocramius. If you are checking for proxies, the following changed:
* Proxies no longer implement `Doctrine\ODM\MongoDB\Proxy\Proxy` or
  any other Doctrine proxy interface. To check whether a returned object is a
  proxy, check for the `ProxyManager\Proxy\GhostObjectInterface` interface.
* The `__load` method has been replaced by `initializeProxy`.
* The `__isInitialized` method has been replaced by `isProxyInitialized`.
* To resolve the original class name for a proxy object, you can no longer use
  the `Doctrine\Common\Util\ClassUtils` class. Instead, fetch the class name
  resolver from the document manager:
  ```php
  $dm->getClassNameResolver()->getRealClass($className);
  ```
* The return value of the `getClassNameResolver` method was updated in
  2.0.0. The previously returned
  `Doctrine\ODM\MongoDB\Proxy\ClassNameResolver` class was dropped in favour of
  the `Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver` interface. This
  BC break was necessary to mitigate a performance regression. The `getClass`
  method was dropped from the interface as it wasn't being used.

## Repository

* The `Doctrine\ODM\MongoDB\DocumentRepository` class has been renamed to
  `Doctrine\ODM\MongoDB\Repository\DocumentRepository`.
* The `findBy*` and `findOneBy*` magic methods have been dropped. Please create
  explicit methods or use the `findBy` and `findOneBy` methods instead.
* The `Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory` class has been
  made `final`.

## Query

* The `requireIndexes` method in `Doctrine\ODM\MongoDB\Query\Builder` has been
  dropped without replacement. If you want to require indexes for queries, use
  the `notablescan` option in the MongoDB server.
* Running `geoNear` commands through the `geoNear` helper in the query builder
  is no longer supported. Please refactor your queries to use the aggregation
  framework and `$geoNear` pipeline operator.
* Running `group` and `mapReduce` commands through the query builder is no
  longer supported. Please either refactor your queries to use the aggregation
  framework or use the MongoDB library (`mongodb/mongodb`) to execute these
  commands.
* The `Doctrine\ODM\MongoDB\Query\FieldExtractor` class was dropped entirely.
* The `getIterator` method in `Doctrine\ODM\MongoDB\Query\Query` returns an
  iterator of type `Doctrine\ODM\MongoDB\Iterator\Iterator` instead of a MongoDB
  cursor.
* The `execute` method in `Doctrine\ODM\MongoDB\Query\Query` now returns an
  iterator of type `Doctrine\ODM\MongoDB\Iterator\Iterator` for find queries,
  and a plain array for distinct queries.
* The `eagerCursor` helper in `Doctrine\ODM\MongoDB\Query\Builder` and its logic
  have been removed entirely without replacement.
* Querying for a mapped superclass in a complex inheritance chain will now only
  return children of that specific class instead of all classes in the
  inheritance tree.

## Schema manager

* The schema manager no longer implicitly creates indexes covering the shard key
  when sharding a collection. While MongoDB creates this index automatically
  when sharding an empty collection, it is recommended users explicitly map an
  index covering the shard key.
* The `createDatabases` and `createDocumentDatabases` methods have been removed
  from `Doctrine\ODM\MongoDB\SchemaManager`. Databases are created implicitly in
  MongoDB 3.0.
* The `--db` argument to the `odm:schema:create` console command has been
  removed.

## Tools

* The `Doctrine\ODM\MongoDB\Tools\DisconnectedClassMetadataFactory` class has
  been dropped without replacement.
* Document and repository generation was removed completely.

## Types

* The `Doctrine\ODM\MongoDB\Types\FileType` class was removed completely.
* The `Doctrine\ODM\MongoDB\Types\IncrementType` class was removed completely.
  Use an `increment` strategy on the field mapping for `int` and `float` fields
  instead.

## Unit of work

* The `commit` method in `Doctrine\ODM\MongoDB\UnitOfWork` no longer takes a
  `$document` as its first argument. Flushing single documents has been removed.
  If you don't want to implicitly flush changes in all documents to the
  database, consider using a different changeset computation strategy (e.g. 
  explicit).
* Triggering a `commit` while one is already in progress will now cause an
  exception. This would usually happen if you flushed the document manager from
  within a lifecycle event handler. Since data integrity can't be guaranteed, it
  is no longer allowed to nest flushes to the database.
* The `isScheduledForDirtyCheck` and `scheduleForDirtyCheck` methods have been
  renamed to `isScheduledForSynchronization` and `scheduleForSynchronization`,
  respectively.

## Internal classes and methods

Number of public methods and classes saw an `@internal` annotation added. This
marks places which are considered private to ODM but can not become ones due to
language limitations. Those methods can still be used (at your own risk) however
the backward compatibility promise for them is relaxed: we reserve the right to
change internal method's signatures and/or remove them altogether in *minor*
releases. Should such change be made, a note shall be included in the `UPGRADE.md`
file describing changes contained in the release.

## Final classes

Following classes have been made `final`:

 * `Doctrine\ODM\MongoDB\DocumentNotFoundException`
 * `Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs`
 * `Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs`
 * `Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs`
 * `Doctrine\ODM\MongoDB\Event\OnClearEventArgs`
 * `Doctrine\ODM\MongoDB\Event\OnFlushEventArgs`
 * `Doctrine\ODM\MongoDB\Event\PostCollectionLoadEventArgs`
 * `Doctrine\ODM\MongoDB\Event\PostFlushEventArgs`
 * `Doctrine\ODM\MongoDB\Event\PreFlushEventArgs`
 * `Doctrine\ODM\MongoDB\Event\PreLoadEventArgs`
 * `Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs`
 * `Doctrine\ODM\MongoDB\Hydrator\HydratorException`
 * `Doctrine\ODM\MongoDB\Hydrator\HydratorFactory`
 * `Doctrine\ODM\MongoDB\Id\AlnumGenerator`
 * `Doctrine\ODM\MongoDB\Id\AutoGenerator`
 * `Doctrine\ODM\MongoDB\Id\IncrementGenerator`
 * `Doctrine\ODM\MongoDB\Id\UuidGenerator`
 * `Doctrine\ODM\MongoDB\LockException`
 * `Doctrine\ODM\MongoDB\LockMode`
 * `Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory`
 * `Doctrine\ODM\MongoDB\Mapping\MappingException`
 * `Doctrine\ODM\MongoDB\PersistentCollection`
 * `Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException`
 * `Doctrine\ODM\MongoDB\Persisters\CollectionPersister`
 * `Doctrine\ODM\MongoDB\Persisters\DocumentPersister`
 * `Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder`
 * `Doctrine\ODM\MongoDB\Proxy\Factory\StaticProxyFactory`
 * `Doctrine\ODM\MongoDB\Query\CriteriaMerger`
 * `Doctrine\ODM\MongoDB\Query\FilterCollection`
 * `Doctrine\ODM\MongoDB\Query\Query`
 * `Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor`
 * `Doctrine\ODM\MongoDB\Query\ReferencePrimer`
 * `Doctrine\ODM\MongoDB\SchemaManager`
 * `Doctrine\ODM\MongoDB\UnitOfWork`
 * `Doctrine\ODM\MongoDB\Utility\CollectionHelper`
 * `Doctrine\ODM\MongoDB\Utility\LifecycleEventManager`
