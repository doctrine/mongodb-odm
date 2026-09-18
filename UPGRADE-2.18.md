# UPGRADE FROM 2.17 to 2.18

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
