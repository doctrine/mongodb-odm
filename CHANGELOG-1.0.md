CHANGELOG for 1.0.x
===================

This changelog references the relevant changes done in 1.0 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/mongodb-odm/commit/XXX where XXX is the commit hash.
To get the diff between two versions, go to
https://github.com/doctrine/mongodb-odm/compare/XXX...YYY where XXX and YYY are
the older and newer versions, respectively.

To generate a changelog summary since the last version, run
`git log --no-merges --oneline XXX...HEAD`

1.0.x-dev
---------

#### Added RFC 4122 UUID binary data type

A new field type (`bin_uuid_rfc4122`) and annotation (`@BinUUIDRFC4122`) were
added in [#1004](https://github.com/doctrine/mongodb-odm/pull/1004). This
should be used instead of the `bin_uuid` type, which is deprecated in the BSON
specification. PHP driver 1.5+ will validate RFC 4122 UUIDs, which should
improve compatibility with other languages that may have native UUID types.

#### `__clone()` method no longer called when documents are instantiated via `ClassMetadata::newInstance()`

As of [#956](https://github.com/doctrine/mongodb-odm/pull/956), ClassMetadata
uses the [Doctrine Instantiator](https://github.com/doctrine/instantiator)
library to create new document instances. This library avoids calling
`__clone()` or any public API on instantiated objects. This is a BC break for\
code that may have relied upon the previous behavior.

1.0.0-BETA11 (2014-06-06)
-------------------------

All issues and pull requests in this release may be found under the
[1.0.0-BETA11 milestone](https://github.com/doctrine/mongodb-odm/issues?milestone=5&state=closed).

#### Ensure cascade mapping option is always set

ClassMetadataInfo's handling of cascade options was refactored in
[#888](https://github.com/doctrine/mongodb-odm/pull/888) to be more consistent
with ORM. These changes ensure that `$mapping["cascade"]` is always set, which
is required by ResolveTargetDocumentListener.

#### Use Reflection API to create document instances in PHP 5.4+

PHP 5.4.29 and 5.5.13 introduced a BC-breaking change to `unserialize()`, which
broke ODM's ability to instantiate document classes without invoking their
constructor (used for hydration). The suggested work-around is to use
`ReflectionClass::newInstanceWithoutConstructor()`, which is available in 5.4+.
This change was implemented in
[#893](https://github.com/doctrine/mongodb-odm/pull/893).

1.0.0-BETA10 (2014-05-05)
-------------------------

All issues and pull requests in this release may be found under the
[1.0.0-BETA10 milestone](https://github.com/doctrine/mongodb-odm/issues?milestone=2&state=closed).

#### ResolveTargetDocumentListener

[#663](https://github.com/doctrine/mongodb-odm/pull/663) added a new
ResolveTargetDocumentListener service, which allows `targetDocument` metadata to
be resolved at runtime. This is based on a corresponding class in ORM, which has
existed since version 2.2. This promotes loose coupling by allowing interfaces
or abstract classes to be specified in the owning model's metadata. The service
will then resolve those values to a concrete class upon the ODM's request.

#### Improved support for differentiating identifier types and non-scalar values

ODM previously required that documents use scalar identifier values. Also, the
identity map, which UnitOfWork uses to track managed documents, was unable to
differentiate between numeric strings and actual numeric types. After internal
refactoring in [#444](https://github.com/doctrine/mongodb-odm/pull/444), it
should now be possible to use complex types such as MongoBinData and associative
arrays (i.e. `hash` type) and the identity map should no longer confuse strings
and numeric types. Embedded documents and references are still not supported as
identifier values.

#### Classes not listed in discriminator maps

When a discriminator map is used, ODM will store the object's short key instead
of its FQCN in the discriminator field. Previously, ODM might leave that field
blank when dealing with a class that was not defined in the map. ODM will now
fall back to storing the FQCN in this case. This primarily affects embedded
documents and references.

#### Criteria API

The base DocumentRepository class now implements the Selectable interface from
the Criteria API in Doctrine Collections 1.1. This brings some consistency with
ORM; however, ODM's PersistentCollection class does not yet support Selectable.
This functionality was introduced in
[#699](https://github.com/doctrine/mongodb-odm/pull/699).

#### Read preferences and Cursor refactoring

ODM's Cursor class was refactored to compose the Doctrine MongoDB Cursor. It
still extends the base class for backwards compatibility, but methods are now
proxied rather than simply overridden. This solved a few bugs where the cursor
states might become inconsistent.

With this refactoring, ODM now has proper support for read preferences in its
query builder and the UnitOfWork hints, which previously only supported the
deprecated `slaveOkay` option. Much of this work was implemented in
[#565](https://github.com/doctrine/mongodb-odm/pull/565).

#### DocumentRepository findAll() and findBy()

The `findAll()` and `findBy()` methods in DocumentRepository previously returned
a Cursor object, which was not compatible with the ObjectRepository interface in
Doctrine Common. This has been changed in
[#752](https://github.com/doctrine/mongodb-odm/pull/752), so these methods now
return a numerically indexed array. The change also affects the magic repository
methods, which utilize `findBy()` internally. If users require a Cursor, they
should utilize the query builder or a custom repository method.

#### Lifecycle callbacks and AlsoLoad

The `@HasLifecycleCallbacks` class annotation is now required for lifecycle
annotations on methods *declared within that class* to be registered. If a
parent and child close both register the same lifecycle callback, ODM will only
invoke it once. Previously, the same callback could be registered and invoked
multiple times (see [#427](https://github.com/doctrine/mongodb-odm/pull/427),
[#474](https://github.com/doctrine/mongodb-odm/pull/474), and
[#695](https://github.com/doctrine/mongodb-odm/pull/695)).

The `@AlsoLoad` method annotation does not require `@HasLifecycleCallbacks` on
the class in which it is declared. If the method considers multiple fields, it
will only be invoked once for the first field found. This is consistent with how
`@AlsoLoad` works for properties. Previously, the method might be invoked
multiple times.

#### MongoBinData subtype change for BinDataType

BinDataType (type `bin`) now defaults to `0` for its MongoBinData subtype. The
various binary type classes have all been refactored to extend BinDataType.
BinDataType's previous subtype, `2`, is available via the BinDataByteArrayType
class (type `bin_bytearray`); however, users should note that subtype `2` is
deprecated in the [BSON specification](http://bsonspec.org/#/specification).

#### Priming references

`Builder::prime()` now allows any callable to be registered, where previously
only Closures were supported. The method will throw an InvalidArgumentException
if the argument is not a boolean or callable. Boolean `true` may still be passed
to utilize the default primer. Priming is now deferred until the very end of
`Query::execute()`, so it will now apply to findAndModify command results.

The signature for primer callables has changed to:

```
function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints)
```

A ClassMetadata instance (of the class to be primed) is now passed as the second
argument instead of the class name string. The reference field name, which was
formerly the third argument, has been removed.

1.0.0-BETA9 (2013-06-06)
------------------------

 * 26750bc: Use target class' DocumentPersister when preparing referenceMany criteria
 * bab17db: Improve regression test for #593 to check proxy state
 * c1f3f8b: Optimize class lookups if mapping has targetDocument defined
 * fa06edf: Refactor UnitOfWork::cascadePostPersist()
 * 73cd30a: Dispatch postPersist callbacks/events for upserted documents (fixes #560)
 * ab39f55: Refactor test for #560 to use an event subscriber
 * 081ccb0: Added a test case for GitHub issue #560
 * 5ac7dff: Add filter/discriminator criteria at Query-level, not Expr (fixes #596)
 * eb2f156: Do not unset EmbedMany fields in PersistenceBuilder
 * fb3ef47: added test case for ReferenceMany mapping
 * 622f34a: unset when new value is null only
 * 49bd0bd: Rename DocumentPersister::prepareSort() to prepareSortOrProjection()
 * 486a25e: $unset empty array collections or null embed many field values.
 * 7e4925a: Regression test for reference-many collections and filter criteria
 * 8c3dad7: DocumentPersister::prepareQueryOrNewObj() method is now used instead of DocumentPersister::prepareSubQuery().
 * 2d6da1d: prepare collection/filter queries
 * 7a5f0c4: remove filter criteria from query as it is handled in the DocumentPersister
 * 0d042ec: Add regression test for #566
 * e98fda9: mappedby field of embedded document throws notice
 * 45cbc71: Remove unused properties in DocumentPersister
 * 54e522f: Clean up DocumentPersister's queued inserts on error
 * 59b82e9: Add unit tests for DateType
 * c36c060: Throw exception in DateType when conversion to MongoDate fails
 * dda08d6: Use Type::getType() instead of calling private Type constructor
 * 9874a4a: Move Type classes from Mapping to Types namespace
 * 58ca354: Regression test DocumentPersister::loadAll() and cursor recreation
 * 9ec2c7d: Fix sort/limit/skip preparation in DocumentPersister load methods
 * b2597b9: Prepare projections in Query\Builder
 * 626faab: Fix several instances of doubled words
 * b12a806: Strip leading backslash for doc classes in DocumentManager
 * a0961f3: missing loggable cursor
 * f05002d: Throw exception if removed document encountered during flush
 * 0ea0d36: Refactor if/else in DocumentRepository::find()
 * 0179cbc: Remove unused args/code in UnitOfWork::doMerge()
 * d2678f7: CS and documentation fixes
 * e18a312: Fixed some typos on basic-mapping.rst
 * 870f53a: Fix field annotation wrong name
 * 7d4c1ee: Fix return type in docblocks for setter methods (closes #575)
 * 14c883a: Missing Semicolon
 * 7561238: Fixes typo in query builder api
 * 661b05c: Refactor unset embedded test in RemoveTest
 * 68a337e: Test cascade/unset behavior for references (see: #557)
 * a3c0a05: updated composer.json for Symfony 2.3
 * 40f7cdb: Add theme submodule
 * 40bdafb: example code fixed
 * 58fa319: Preserve BaseCursor hints when wrapping with EagerCursor
 * a0f9d39: Preserve BaseCursor hydrate option when wrapping with EagerCursor
 * 517e0a5: Create GridFS collections in SchemaManager (closes #486)
 * 12faf23: Check MongoId preparation for operator tests
 * cd1c49d: Prepare multiple query operators for targetDocument IDs
 * 59fcd66: Refactor targetDocument processing in prepareQueryElement()
 * 5b7f0c6: Return a tuple instead of modifying $fieldName param by reference
 * 2a937dc: Refactor DocumentPersister::prepareQueryElement()
 * 76c7811: Test nested collection key queries with set strategy
 * 5653347: Remove unnecessary ternary statement
 * 9026751: fixed prepareQueryElement() "set" strategy issue
 * c8e9a6e: Support both integers and floats in IncrementType (closes #526)
 * 4a08c18: Increment fields should respect null values (fixes #528)
 * fc38c3f: Refactor UnitOfWorkTest and DocumentPersisterMock
 * 546163b: Fix persist() loop in GH453Test
 * cf408b8: Use consistent types for identifiers in tests
 * 8b5bb39: Test that generated IDs are available after flush (closes #529)
 * 88a7dbc: Do not schedule documents with inconsistent IDs for upsert
 * e1b9f0d: Ignore MongoId constructor exceptions in IdType
 * 65c0e36: fix also load if the database name is not equal to field name
 * d1b33c8: add fallback for timestamp in date type
 * 87adfb8: added also load and not saved option to xml driver
 * 796977f: use non-strict equality check for existing indexes
 * ba287a4: Fix some instances of monospace formatting
 * 78a06c3: IncrementType can extend IntType until #526 is implemented
 * 5283c18: IntIdType can simply extend IntType
 * df33490: Add integer ID type and default to it for increment generator.
 * 516be36: Document alternative ID generator strategies
 * e6018d6: Clarify that increment type uses integers
 * 0162265: Force driver install for Travis
 * 946adf8: fixed generating proxy classes to specified dir
 * 27153e0: Update eager-cursors.rst
