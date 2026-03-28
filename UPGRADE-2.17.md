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
- `Type::getTypesMap()` → `TypeRegistry::getMap()`
- `Type::registerType($name, $class)` → `TypeRegistry::register($name, $class)`
- `Type::overrideType($name, $class)` → `TypeRegistry::register($name, $class)`
- `Type::getTypeFromPHPVariable($value)` → `TypeRegistry::guessTypeFromValue($value)`
- `Type::convertPHPToDatabaseValue($value)` → `TypeRegistry::convertToDatabaseValue($value)`

You can set and get the `TypeRegistry` instance from the `Doctrine\ODM\MongoDB\Configuration`
using `getTypeRegistry()` and `setTypeRegistry()`.

To access the type of a mapped field, use the `ClassMetadata::getFieldType()` method.
