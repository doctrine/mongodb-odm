# UPGRADE FROM 1.2 TO 1.3

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

## Schema manager

 * The `timeout` option in the `odm:schema:create` and `odm:schema:update`
   commands was deprecated and will be dropped in 2.0. Use the `maxTimeMs`
   option instead.
 * The `indexOptions` argument in the `ensureSharding` and
   `ensureDocumentSharding` methods was deprecated and will be dropped in 2.0.
