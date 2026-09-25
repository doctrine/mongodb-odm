# UPGRADE FROM 2.17 to 2.18

## New `ChangeSets\ChangeSet` value object

`UnitOfWork::getChangeSet(object $document): ChangeSet` is a new method
returning a `Doctrine\ODM\MongoDB\ChangeSets\ChangeSet` object describing a
document's pending field changes, as an alternative to the existing
`getDocumentChangeSet(): array`. `getDocumentChangeSet()`'s return shape is
unchanged (`array<string, array{0: mixed, 1: mixed}>`), but the values it
reports for some fields have changed:

- **Change tracking NOTIFY**: the old value reported for a field is now
  always read from the document's original-data snapshot, never from the
  `$oldValue` argument a `propertyChanged()` notification carries. If you
  still use NOTIFY change tracking (deprecated), and rely on
  `getDocumentChangeSet()`/`PreUpdateEventArgs::getOldValue()` reporting
  exactly what your setter passed as the old value rather than the
  document's persisted value, check any code that reads old values for
  NOTIFY-tracked documents.
- An embedded document's change bubbling up to its parent's own changeset
  (a changed embed-one/embed-many child) used to report `[$value, $value]`
  for the parent's association field — the same instance as both old and
  new. It now reports the parent's real original-data snapshot value as the
  old value.

The following previously `@internal` method's signature and behavior also
changed, for anyone calling it directly despite the annotation:

- `UnitOfWork::clearDocumentChangeSet()` now takes the `object $document`
  instead of `int $oid`, and `unset()`s the document's changeset instead of
  replacing it with `[]` — so `isset()`-style checks against the internal
  changeset map now see it as absent afterwards rather than present-but-empty.

## Removed previously internal, deprecated `UnitOfWork` methods

The following methods, previously marked `@internal` have been removed from
`UnitOfWork`:

- `setParentAssociation()`
- `getParentAssociation()`
- `removeFromIdentityMap()`
- `getById()`
- `isInIdentityMap()`
- `containsId()`
- `getIdentityMap()`
- `setOriginalDocumentProperty()`
- `size()`

These methods were internal implementation details of the `UnitOfWork` and
were never intended for public use outside of the library itself. Their
functionality now lives on `Doctrine\ODM\MongoDB\Registry\DocumentRegistry`,
obtained via `DocumentManager::getDocumentRegistry()`:

```php
$registry = $documentManager->getDocumentRegistry();

$registry->isInIdentityMap($classMetadata, $document);
$registry->getById($id, $classMetadata);
$registry->containsId($id, $rootClassName);
$registry->getIdentityMap();
$registry->setOriginalDocumentProperty($document, $property, $value);
$registry->setParentAssociation($document, $mapping, $parent, $field);
$registry->getParentAssociation($document);
$registry->removeFromIdentityMap($classMetadata, $document);
```

Note that the document registry is also an implementation detail of the ODM not
designed for public use, and is thus marked as `@internal`. We do not provide
backward compatibility guarantees for internal code.

`UnitOfWork::size()` is replaced by `DocumentRegistry::count()` (the class
implements `Countable`, so `count($registry)` also works).

Note that `getParentAssociation()` now returns a
`Doctrine\ODM\MongoDB\Registry\ParentAssociation` object (with `mapping`,
`parent`, and `field` properties) instead of a `[$mapping, $parent, $field]`
array.

## Sort orders accept the `SortDirection` enum

Every sort order, in the Query Builder and the Aggregation Builder, now accepts
the global `SortDirection` enum in addition to the existing `asc`/`desc`
strings and `1`/`-1` integers:

```php
$qb->sort('createdAt', SortDirection::Descending);
```

The enum is provided natively by PHP 8.6, and the `symfony/polyfill-php86`
package provides it for PHP 8.1 and later.

## Invalid sort values now throw

Sort values are now validated centrally. Any value other than `1`, `-1`, the
`asc`/`desc` strings, a `SortDirection` case, an allowed `$meta` keyword or a
`$meta` expression throws an `InvalidArgumentException` naming the field.
Values that previously produced a silently wrong sort or a server error now
fail fast.

## Aggregation `$sort` stage meta keywords

The aggregation `$sort` stage now accepts the `searchScore` and
`vectorSearchScore` keywords as `$meta` expressions, in addition to
`textScore`. For example, `searchScore` was previously converted to a `-1`
direction and now produces the correct `$meta` expression.

## Query Builder `$meta` keywords

The Query Builder `sort()` also accepts the `textScore` keyword. It is
converted into a `$meta` expression, consistent with the sort `find()`
supports.

## `TypeRegistry` replaces `Type` static methods

A new `Doctrine\ODM\MongoDB\Types\TypeRegistry` class has been introduced
to manage custom types. The static methods of `Doctrine\ODM\MongoDB\Types\Type`
are deprecated and will be removed in MongoDB ODM 3.0:
- `Type::getType($name)` → `TypeRegistry::get($name)`
- `Type::hasType($name)` → `TypeRegistry::has($name)`
- `Type::addType($name, $class)` → `TypeRegistry::register($name, $class)`
- `Type::registerType($name, $class)` → `TypeRegistry::register($name, $class)`
- `Type::overrideType($name, $class)` → `TypeRegistry::register($name, $class)`
- `Type::getTypesMap()` → iterate over the `TypeRegistry`
- `Type::getTypeFromPHPVariable($value)` → `DocumentManager::getTypeGuesser()->guessTypeFromValue($value)`
- `Type::convertPHPToDatabaseValue($value)` → `DocumentManager::getTypeGuesser()->convertToDatabaseValue($value)`

You can set and get the type registry on `Doctrine\ODM\MongoDB\Configuration`
using `getTypeProvider()` and `setTypeProvider()`. Types registered on a registry
are scoped to the `DocumentManager` instances built from that `Configuration`,
instead of being global to the process. The registry can only be set once. A
second call to `setTypeProvider()` throws a `LogicException`, to avoid leaving
already loaded metadata pointing at the previous registry.

```php
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;

$config = new Configuration();
$config->setTypeProvider(new TypeRegistry([
    'date_with_timezone' => new DateTimeWithTimezoneType(),
]));
```

`Configuration::setTypeProvider()` accepts, and `getTypeProvider()` returns, any
`Doctrine\ODM\MongoDB\Types\TypeProvider`. The interface only requires `get()`,
`has()` and being traversable, so `TypeRegistry` can be replaced by your own
implementation. `TypeRegistry` itself is `final`.

A `TypeRegistry` always contains the built-in types. Types passed to the constructor,
by instance or by class name, are registered on top and override the built-in type of
the same name. Passing a PSR-11 container together with a map of type names to service
IDs resolves types lazily, on first use:

```php
$registry = new TypeRegistry($container, ['money' => 'app.odm_type.money']);
```

To access the type of a mapped field, use the `ClassMetadata::getFieldType()` method.
