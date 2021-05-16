# UPGRADE FROM 2.2 to 2.3

## Proxy Class Name Resolution

The `Doctrine\ODM\MongoDB\Proxy\Resolver\ClassNameResolver` interface has been
deprecated in favor of the `Doctrine\Persistence\Mapping\ProxyClassNameResolver`
interface.

The `getClassNameResolver` method in `DocumentManager` is deprecated and should
not be used. To retrieve the mapped class name for any object or class string,
fetch metadata for the class and read the class using `$metadata->getName()`.
The metadata layer is aware of these proxy namespace changes and how to resolve
them, so users should always go through the metadata layer to retrieve mapped
class names.
