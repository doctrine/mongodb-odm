# UPGRADE FROM 2.15 to 2.16

## Attribute namespaces

Doctrine annotations are already deprecated in favor of PHP attributes.
As of MongoDB ODM 2.16, the namespace of attribute classes has been changed from
`Doctrine\ODM\MongoDB\Mapping\Annotations` to `Doctrine\ODM\MongoDB\Mapping\Attribute`.
The old classes continue to work, but they are deprecated and will be removed in 3.0.

```diff
- use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
+ use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

  #[ODM\Document]
  class User
  {
      #[ODM\Id]
      private string $id;

      #[ODM\Field]
      public string $name;
  }
```

The enum `Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery` has been moved to
`Doctrine\ODM\MongoDB\Mapping\Attribute\EncryptQuery`.

[BC break] The classes `Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractDocument`,
`AbstractField` and `AbstractIndex` does not implement
`Doctrine\ODM\MongoDB\Mapping\Annotations\Annotation` anymore;
they must not be used outside the `doctrine/mongodb-odm` package.
The new abstract classes in the `Doctrine\ODM\MongoDB\Mapping\Attribute`
are marked as `@internal`.

## Package `doctrine/cache` no longer required

If you use `Doctrine\ODM\MongoDB\Configuration::getMetadataCacheImpl()`,
then you need to require `doctrine/cache` explicitly in `composer.json`;
or use `Doctrine\ODM\MongoDB\Configuration::getMetadataCache()` instead.

## Lazy Proxy Directory

Using proxy classes with PHP 8.4+ is deprecated, only native lazy objects will
be supported in MongoDB ODM 3.0.

Calling `Doctrine\ODM\MongoDB\Configuration::setProxyDir()` or
`Doctrine\ODM\MongoDB\Configuration::getProxyDir()` is deprecated and triggers
a deprecation notice when using native lazy objects.

## `TypeRegistry` replace `Type::getType()` and `Type::hasType()`

A new `Doctrine\ODM\MongoDB\Types\TypeRegistry` class has been introduced
to manage custom types. The static methods of `Doctrine\ODM\MongoDB\Types\Type`
are deprecated and will be removed in MongoDB ODM 3.0:
- `Type::getType($name)` → `TypeRegistry::getType($name)`
- `Type::hasType($name)` → `TypeRegistry::hasType($name)`
- `Type::addType($name, $class)` → `TypeRegistry::addType($name, $class)`
- `Type::getTypesMap()` → `TypeRegistry::getTypesMap()`
- `Type::registerType()` → `TypeRegistry::registerType()`
- `Type::overrideType()` → `TypeRegistry::registerType()`
- `Type::getTypeFromPHPVariable()` → `TypeRegistry::guessTypeFromValue()`
- 'Type::convertPHPToDatabaseValue()` → `TypeRegistry::convertToDatabaseValue()`'

You can set and get the `TypeRegistry` instance from the `Doctrine\ODM\MongoDB\Configuration`
using `getTypeRegistry()` and `setTypeRegistry()`.

To access the type of mapped field, use the `ClassMetadata::getFieldType()` method.

## Override `Type::closureToPHP()` for custom type classes

The default implementation of `Doctrine\ODM\MongoDB\Types\Type::closureToPHP()`
will change in MongoDB ODM 3.0 to call `convertToPHPValue()`. If you have custom
type classes, use the `Doctrine\ODM\MongoDB\Types\ClosureToPHP` trait or
implement `closureToPHP()`.

## Deprecate `Type::closureToMongo()`

The method `Doctrine\ODM\MongoDB\Types\Type::closureToMongo()` is not used,
and will be removed in MongoDB ODM 3.0. Don't call this method, but use
`convertToDatabaseValue()` instead.

## `PersistentCollection` implements `Selectable`

The `PersistentCollection` and all generated persistent collection classes
implement the `Doctrine\Common\Collections\Selectable::matching()` method.
