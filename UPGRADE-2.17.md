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
