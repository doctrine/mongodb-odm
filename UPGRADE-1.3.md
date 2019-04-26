# UPGRADE FROM 1.2 TO 1.3

## Aggregation builder

 * The `debug`, `maxDistance` and `minDistance` methods in
   `Doctrine\ODM\MongoDB\Aggregation\Stage\Match` have been deprecated and will
   be removed in 2.0.
 * The `Doctrine\ODM\MongoDB\Aggregation\Expr::ensureArray` method will be
   private in 2.0.
 * The `Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket::convertExpression` method
   will be private in 2.0.
 * The `Doctrine\ODM\MongoDB\Aggregation\Stage\BucketAuto::convertExpression`
   method will be private in 2.0.
 * The following methods in `Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup`
   will be private in 2.0: `convertExpression`, `convertTargetFieldName`.
 * The `Doctrine\ODM\MongoDB\Aggregation\Stage\ReplaceRoot::convertExpression`
   method will be private in 2.0.
 * Calling `Doctrine\MongoDB\Aggregation\Stage\Match::geoWithinPolygon` with
   fewer than 3 arguments was deprecated and will cause errors in 2.0. A polygon
   must have at least 3 edges to be considered valid.

## Configuration

 * The following methods in `Doctrine\ODM\MongoDB\Configuration` have been
   deprecated and will be removed in MongoDB ODM 2.0: `getLoggerCallable`,
   `getMongoCmd`, `getRetryConnect`, `getRetryQuery`, `setLoggerCallable`,
   `setMongoCmd`, `setRetryConnect`, `setRetryQuery`.
 * The `attributes` property will be private in MongoDB ODM 2.0 - if you are
   extending the configuration class you should no longer rely on it.
 * The `getDefaultRepositoryClassName` and `setDefaultRepositoryClassName`
   methods in `Doctrine\ODM\MongoDB\Configuration` have been deprecated in favor
   of `getDefaultDocumentRepositoryClassName` and
   `setDefaultDocumentRepositoryClassName`, respectively.

## Cursors

 * The `Doctrine\ODM\MongoDB\Cursor`, `Doctrine\ODM\MongoDB\CommandCursor`, and
   `Doctrine\ODM\MongoDB\EagerCursor` classes have been deprecated and will be
   removed in 2.0. Their functionality will be covered by basic iterators. To
   typehint an ODM specific iterator, use the new
   `Doctrine\ODM\MongoDB\Iterator\Iterator` interface.

## Document Class Generation

Functionality regarding generation of document and repository classes was
deprecated and will be dropped in 2.0. The following classes related to this
functionality have been deprecated:
 * `Doctrine\ODM\MongoDB\Query\FieldExtractor`
 * `Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand`
 * `Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand`
 * `Doctrine\ODM\MongoDB\Tools\DisconnectedClassMetadataFactory`
 * `Doctrine\ODM\MongoDB\Tools\DocumentGenerator`
 * `Doctrine\ODM\MongoDB\Tools\DocumentRepositoryGenerator`

## Document Manager

 * The `Doctrine\ODM\MongoDB\DocumentManager::createDBRef` method was deprecated
   in favor of `createReference`. It will be dropped in 2.0.
 * The `Doctrine\ODM\MongoDB\DocumentManager::getConnection` method was
   deprecated and will be dropped in 2.0. The replacement, `getClient` will
   return a `MongoDB\Client` instance, but is not available in 1.x.

## Document Persister

 * The `Doctrine\ODM\MongoDB\Persisters\DocumentPersister::prepareSortOrProjection`
   method was deprecated and will be dropped in 2.0. Use `prepareSort` or
   `prepareProjection` accordingly.

## Events

### `onClassMetadataNotFound` event added

The default ClassMetadataFactory now triggers an `onClassMetadataNotFound` event
when no metadata was found for a given class. The event args contain information
about the loaded class and can store a metadata object which can be used to load
custom metadata when none was found.

### `ResolveTargetDocumentListener` is now an event subscriber

The `Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener` class now
implements the `EventSubscriber` interface. You can still register it as a
listener as was necessary previously, but in order to use new functionality you
have to register it as an event subscriber.

### `ResolveTargetDocumentListener` resolves class names on document load

The `Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener` class not only
resolves class names when looking up the `targetDoccumet` of an association in
a document, but it also uses the new `onClassMetadataNotFound` event to resolve
class names and trigger a secondary metadata load cycle if no metadata was found
for the original class name. To use this functionality, either register an event
listener for the `onClassMetadataNotFound` event or register the entire class as
an event subscriber.

## GridFS

GridFS support will be rewritten for 2.0 and was deprecated in its current form.
A forward compatible layer cannot be provided due to differences in the
underlying API.
 * The `Doctrine\ODM\MongoDB\Mapping\Annotations\File` annotation was deprecated
   and will be changed to a class-level annotation in 2.0.

## Mapping changes

### Yaml driver deprecated

The yaml mapping driver has been deprecated and will be removed in 2.0. Please 
switch to using annotations or XML mapping files. The following classes have
been deprecated:
 * `Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver`
 * `Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver`

### `id` attribute in XML mappings deprecated

The `id` attribute to denote identifiers in XML mappings has been deprecated and
will be removed in 2.0. Instead, use the new `id` element to map identifiers
instead of mapping them with `field`.

### Same-namespace resolution dropped

With same-namespace resolution, the metadata driver would look for a class of
that name in the same namespace if the given class name didn't contain a
namespace separator (`\`). This has been deprecated and will be dropped. Use
fully qualified class names or the `::class` constant instead:

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

### `ClassMetadataInfo` class deprecated

The `Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo` class has been deprecated
in favor of `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` and will be dropped in
2.0.

### Obsolete features deprecated

 * The following methods in `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` were
   deprecated and will be removed in 2.0: `getDistance`, `getFile`,
   `getNamespace`, `isFile`, `mapFile`, `setDistance`, `setFile`,
   `setRequireIndexes`, `setSlaveOkay`.
 * The following properties `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` were
   deprecated and will be removed in 2.0: `distance`, `file`, `namespace`,
   `requireIndexes`, `slaveOkay`.

### Annotation mappings

 * The `@NotSaved` annotation was deprecated and will be dropped in 2.0. Use the
   `notSaved` option on the `@Field`, `@ReferenceOne`, `@ReferenceMany`,
   `@EmbedOne` or `@EmbedMany` annotations instead.
 * Using more than one class-level document annotation (e.g. `@Document`,
   `@MappedSuperclass`) is deprecated and will throw an exception in 2.0.
   Classes should only be annotated with a single document annotation.
 * The `dropDups` option on the `@Index` annotation was deprecated and will be 
   dropped without replacement in 2.0. This functionality is no longer
   available.
 * The `simple` option on the `@ReferenceOne` and `@ReferenceMany` annotations
   was deprecated and will be dropped in 2.0. Use `storeAs="id"` instead.
 * The `@Distance` annotation was deprecated and will be dropped in 2.0. GeoNear
   queries will no longer be supported, use the aggregation pipeline instead.

### XML mappings

 * The `writeConcern` attribute in document mappings has been deprecated and
   will be dropped in 2.0. Use `write-concern` instead.
 * The `fieldName` attribute in field mappings has been deprecated and will be
   dropped in 2.0. Use `field-name` instead.
 * The `drop-dups` attribute in the `index` element was deprecated and will be
   dropped without replacement in 2.0. This functionality is no longer
   available.

### Full discriminator maps required

When using a discriminator map on a reference or embedded relationship,
persisting or loading a class that is not in the map is deprecated and will
cause an exception in 2.0. The discriminator map must contain all possible
classes that can be referenced or be omitted completely.

### Duplicate field names in mappings

Mapping two fields with the same name in the database is deprecated and will
cause an exception in 2.0. It is possible to have multiple fields with the same
name in the database as long as all but one of them have the `notSaved` option
set.

## Persisters

 * The `delete` and `update` methods in
   `Doctrine\ODM\MongoDB\Persisters\CollectionPersister` are deprecated. Use
   `deleteAll` and `updateAll` instead. The method signatures will be adapted
   to match those of `deleteAll` and `updateAll` in 2.0.

## Proxies

 * The usage of proxies from Doctrine Common was deprecated and will be replaced
   with `ocramius/proxy-manager` in 2.0.
 * Using the `AUTOGENERATE_ALWAYS` and `AUTOGENERATE_NEVER` strategies is
   deprecated and will be dropped in 2.0. Use `AUTOGENERATE_FILE_NOT_EXISTS` and
   `AUTOGENERATE_EVAL` instead.
 * The `Doctrine\ODM\MongoDB\Proxy\ProxyFactory` class was deprecated and will
   be dropped in 2.0.
 * The `Doctrine\ODM\MongoDB\Proxy\Proxy` interface was deprecated and will be
   dropped in 2.0.

## Queries

 * The `eagerCursor` method in `Doctrine\ODM\MongoDB\Query\Builder` was
   deprecated and will be dropped in 2.0. This functionality is no longer
   available.
 * The `eager` option in `Doctrine\ODM\MongoDB\Query\Query` was deprecated and
   will be dropped in 2.0. This functionality is no longer available.
 * The `geoNear`, `group`, and `mapReduce` query types were deprecated and will
   be dropped in 2.0. Use aggregation pipeline features instead.
 * The following methods in `Doctrine\ODM\MongoDB\Query\Builder` were deprecated
   and will be dropped in 2.0: `mapReduce`, `map`, `reduce`, `finalize`, `out`,
   `mapReduceOptions`, `distanceMultiplier`, `geoNear`, `spherical`.
 * The following properties in `Doctrine\ODM\MongoDB\Query\Builder` will be
   private in MongoDB ODM 2.0: `collection`, `expr`, `query`.
 * The following properties in `Doctrine\ODM\MongoDB\Query\Expr` will be private
   in MongoDB ODM 2.0: `currentField`, `newObj`, `query`.
 * The `addManyToSet` and `pushAll` methods in
   `Doctrine\ODM\MongoDB\Query\Builder` and `Doctrine\ODM\MongoDB\Query\Expr`
   were deprecated and will be removed in 2.0. Use `each` in combination with
   `addToSet` and `push` respectively.
 * The following methods from `Doctrine\ODM\MongoDB\Query\Builder` and
   `Doctrine\ODM\MongoDB\Query\Expr` were deprecated and will be removed in 2.0:
   `maxDistance`, `minDistance`, `withinBox`, `withinCenter`,
   `withinCenterSphere`, `withinPolygon`.
 * The `Doctrine\MongoDB\Query\Query::count` method was deprecated and will be
   removed in MongoDB ODM 2.0. Iterators will not be countable in 2.0. Users
   should run separate count queries instead.
 * The `Doctrine\MongoDB\Query\Query::iterate` method was deprecated and will be
   removed in MongoDB ODM 2.0. Use `Doctrine\MongoDB\Query\Query::getIterator`
   instead.
 * The following properties in `Doctrine\ODM\MongoDB\Query\Query` will be
   private in MongoDB ODM 2.0: `iterator`, `options`, `query`.
 * The following constants in `Doctrine\ODM\MongoDB\Query\Query` will be removed
   in MongoDB ODM 2.0: `HINT_READ_PREFERENCE_TAGS`, `HINT_SLAVE_OKAY`,
   `TYPE_GEO_NEAR`.
 * The `Doctrine\ODM\MongoDB\Query\Query::prepareCursor` method will be removed
   in MongoDB ODM 2.0. You should wrap the returned cursor instead.
 * Calling `Doctrine\MongoDB\Query\Builder::geoWithinPolygon` and
   `Doctrine\MongoDB\Query\Expr::geoWithinPolygon` with fewer than 3 arguments
   was deprecated and will cause errors in 2.0. A polygon must have at least 3
   edges to be considered valid.

## Repositories
 * The `Doctrine\ODM\MongoDB\DocumentRepository` class was deprecated in favor
   of `Doctrine\ODM\MongoDB\Repository\DocumentRepository` and will be dropped
   in 2.0.

## Schema manager

 * The `timeout` option in the `odm:schema:create` and `odm:schema:update`
   commands was deprecated and will be dropped in 2.0. Use the `maxTimeMs`
   option instead.
 * The `indexOptions` argument in the `ensureSharding` and
   `ensureDocumentSharding` methods was deprecated and will be dropped in 2.0.

## Types

 * The following classes in the `Doctrine\ODM\MongoDB\Types` namespace were
   deprecated and will be removed in 2.0: `FileType`, `IncrementType`.
 * The `FILE` and `INCREMENT` constants in `Doctrine\ODM\MongoDB\Types\Type`
   were deprecated and will be removed in 2.0.

## UnitOfWork

 * The `isScheduledForDirtyCheck` and `scheduleForDirtyCheck` methods in
   `Doctrine\ODM\MongoDB\UnitOfWork` have been deprecated and will be dropped in
   2.0. Use `isScheduledForSynchronization` and `scheduleForSynchronization`
   instead.
