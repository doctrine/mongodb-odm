# UPGRADE FROM 2.3 to 2.4

## Typed properties as default mapping metadata

When using typed properties on Document classes, Doctrine will use these types to set defaults mapping types.

If you have defined some properties like:

```php
#[Field]
private int $myProp;
```

This property will be stored in DB as `string` but casted back to `int`. Please note that at this
time, due to backward compatibility reasons, nullable type does not imply `nullable` mapping.

## doctrine/persistence ^2.4 || ^3.0

ODM now supports two major versions of `doctrine/persistence` and provides forward compatibility where possible.
We strongly recommend checking [package's upgrade notes](https://github.com/doctrine/persistence/blob/3.0.x/UPGRADE.md)
and [ODM's todo list](https://github.com/doctrine/mongodb-odm/issues/2419). Please require 2.x version of
`doctrine/persistence` in your composer.json should you need to use functionalities without a forward
compatibility layer. Most notable examples are:

* removed `LifecycleEventArgs::getEntity()`
* removed support for short namespace aliases

## Deprecate `AttributeDriver::getReader()` and `AnnotationDriver::getReader()`

That method was inherited from the abstract `AnnotationDriver` class of
`doctrine/persistence`, and does not seem to serve any purpose.

## Deprecate `DocumentManager::clear()` with an argument

Detaching all documents of a given class has been deprecated. We deem the process fragile and suggest
detaching your documents one-by-one using `DocumentManager::detach()`. This effectively deprecates
`OnClearEventArgs::getDocumentClass` and `OnClearEventArgs::clearsAllDocuments`.

## Deprecate `NOTIFY` change tracking policy

The `NOTIFY` change tracking policy has been deprecated. We suggest to use `DEFERRED_EXPLICIT`
strategy instead. This effectively deprecates `ClassMetadata::isChangeTrackingNotify` and
`ClassMetadata::CHANGETRACKING_NOTIFY`.
