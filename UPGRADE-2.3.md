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

## Annotation Mapping

In order to make annotations usable as PHP 8 attributes, their classes no 
longer extend `Doctrine\Common\Annotations\Annotation` class and are now using 
`@NamedArgumentConstructor` which provides more type safety. 
This does not apply to `@Indexes` which is deprecated and can't be used as 
Attribute. Use `@Index` and `@UniqueIndex` instead.

`@Inheritance` annotation has been removed as it was never used.

## Deprecated: Document Namespace Aliases

Document namespace aliases are deprecated, use the magic ::class constant to abbreviate full class names
in DocumentManager and DocumentRepository.

```diff
-  $documentManager->find('MyBundle:User', $id);
+  $documentManager->find(User::class, $id);
```
