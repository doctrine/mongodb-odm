# UPGRADE FROM 2.15 to 2.16

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

## Override `Type::closureToPHP()` for custom type classes

The default implementation of `Doctrine\ODM\MongoDB\Types\Type::closureToPHP()`
will change in MongoDB ODM 3.0 to call `convertToPHPValue()`. If you have custom
type classes, use the `Doctrine\ODM\MongoDB\Types\ClosureToPHP` trait or
implement `closureToPHP()`.

## Deprecate `Type::closureToMongo()`

The method `Doctrine\ODM\MongoDB\Types\Type::closureToMongo()` is not used,
and will be removed in MongoDB ODM 3.0. Don't call this method, but use
`convertToDatabaseValue()` instead.
