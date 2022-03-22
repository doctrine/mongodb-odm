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

## BC Break: `AttributeDriver` and `AnnotationDriver` no longer extends parent class from `doctrine/persistence`

Both these classes used to extend an abstract `AnnotationDriver` class defined
in `doctrine/persistence`, and no longer do.

## Deprecate `AttributeDriver::getReader()` and `AnnotationDriver::getReader()`

That method was inherited from the abstract `AnnotationDriver` class of
`doctrine/persistence`, and does not seem to serve any purpose.
