# UPGRADE FROM 2.16 to 2.17

## Deprecate `COLLECTION_PER_CLASS` inheritance type

The `COLLECTION_PER_CLASS` inheritance type has been deprecated with no replacement. It persisted one
collection per class, which is already the default behavior: every document class is mapped to its own
collection. The strategy provided no additional behavior and could not guarantee `_id` uniqueness across
the distinct collections of a hierarchy.

Remove the `InheritanceType` attribute/annotation (or the `inheritance-type` XML attribute) from the
affected classes. Each class remains mapped to its own collection.

```diff
 #[ODM\Document]
-#[ODM\InheritanceType('COLLECTION_PER_CLASS')]
 class Section {}
```

This effectively deprecates `ClassMetadata::isInheritanceTypeCollectionPerClass()` and
`ClassMetadata::INHERITANCE_TYPE_COLLECTION_PER_CLASS`.

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
- `Type::getTypeFromPHPVariable($value)` → no replacement
- `Type::convertPHPToDatabaseValue($value)` → no replacement

You can set and get the type registry on `Doctrine\ODM\MongoDB\Configuration`
using `getTypeRegistry()` and `setTypeRegistry()`. Types registered on a registry
are scoped to the `DocumentManager` instances built from that `Configuration`,
instead of being global to the process. The registry can only be set once, before
any metadata is loaded.

```php
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;

$config = new Configuration();
$config->setTypeRegistry(new TypeRegistry([
    'date_with_timezone' => new DateTimeWithTimezoneType(),
]));
```

`Configuration::setTypeRegistry()` accepts, and `getTypeRegistry()` returns, any
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
