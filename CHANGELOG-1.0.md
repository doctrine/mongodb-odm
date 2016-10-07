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

1.0.8 (2016-10-07)
------------------

All issues and pull requests in this release may be found under the
[1.0.8 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.8).

* [#1468](https://github.com/doctrine/mongodb-odm/pull/1468) fixes dropping of GridFS collections when calling `dropCollections()` on the SchemaManager
* [#1500](https://github.com/doctrine/mongodb-odm/pull/1500) fixes orphanRemoval when clearing an uninitialized collection
* [#1503](https://github.com/doctrine/mongodb-odm/pull/1503) fixes an error with `createDbRef` not working for objects with an ID that evaluated to false (e.g. empty string, 0)

1.0.7 (2016-07-27)
------------------

All issues and pull requests in this release may be found under the
[1.0.7 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.7).

* [#1461](https://github.com/doctrine/mongodb-odm/pull/1461) fixes overwriting data of documents contained in sorted collection
* [#1466](https://github.com/doctrine/mongodb-odm/pull/1466) fixes hydrating `null` value when field is nullable

1.0.6 (2016-06-09)
------------------

All issues and pull requests in this release may be found under the
[1.0.6 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.6).

* [#1382](https://github.com/doctrine/mongodb-odm/pull/1382) fixes the conversion `MongoDate` objects to `DateTime`.
* [#1406](https://github.com/doctrine/mongodb-odm/pull/1406) fixes queries to simple references.
* [#1416](https://github.com/doctrine/mongodb-odm/pull/1416) fixes name resolution when priming references contained in embedded documents.
* [#1417](https://github.com/doctrine/mongodb-odm/pull/1417) fixes unserializing PersistentCollection objects contained in documents.
* [#1431](https://github.com/doctrine/mongodb-odm/pull/1431) fixes potential object hash collisions with embedded documents by keeping a copy of the document in UnitOfWork.
* [#1432](https://github.com/doctrine/mongodb-odm/pull/1432) fixes the usage of embedded document field names when fixing document ownership.

1.0.5 (2016-02-16)
------------------

All issues and pull requests in this release may be found under the
[1.0.5 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.5).

* [#1266](https://github.com/doctrine/mongodb-odm/pull/1266) excludes changes to inverse side of relationship from change set.
* [#1313](https://github.com/doctrine/mongodb-odm/pull/1313) fixes querying for empty collection of simple references.
* [#1319](https://github.com/doctrine/mongodb-odm/pull/1319) fixes creation of discriminated documents during upserts.
* [#1320](https://github.com/doctrine/mongodb-odm/pull/1320) lets `ReflectionException` bubble when mapping not existing field.
* [#1328](https://github.com/doctrine/mongodb-odm/pull/1328) fixes compatibility with version 2.6 of `doctrine/common`.
* [#1341](https://github.com/doctrine/mongodb-odm/pull/1341) fixes XSD entry for `repository-method`.
* [#1355](https://github.com/doctrine/mongodb-odm/pull/1355) fixes initialization of mapped `public` properties when hydrating into `Proxy`.


1.0.4 (2015-12-15)
------------------

All issues and pull requests in this release may be found under the
[1.0.4 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.4).

* [#1276](https://github.com/doctrine/mongodb-odm/pull/1276) fixes a bug when the property name and database field name of an embedded document were different.
* [#1281](https://github.com/doctrine/mongodb-odm/pull/1281) fixes orphanRemoval in PersistentCollection objects when moving items within the collection.
* [#1298](https://github.com/doctrine/mongodb-odm/pull/1298) ensures that \MongoRegex instances are not converted to string when running queries.
* [#1300](https://github.com/doctrine/mongodb-odm/pull/1300) ensures that inherited properties and methods are not duplicated to child classes when generating documents.
* [#1306](https://github.com/doctrine/mongodb-odm/pull/1306) fixes an issue where changesets computed manually before flush could have been lost.


1.0.3 (2015-11-03)
-----------------

All issues and pull requests in this release may be found under the
[1.0.3 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.3).

 * [#1259](https://github.com/doctrine/mongodb-odm/pull/1259) makes it possible to access an embedded document's     parent association through the UnitOfWork as soon as it's owning document is persisted.
 * [#1259](https://github.com/doctrine/mongodb-odm/pull/1259) and [#1252](https://github.com/doctrine/mongodb-odm/pull/1252)
make it possible to simply reuse embedded documents and collections without manually cloning them. For more information
please see the [1.0.3 release blog post](http://www.doctrine-project.org/2015/11/03/doctrine-mongodb-odm-release-1-0-3.html).
 * [#1248](https://github.com/doctrine/mongodb-odm/pull/1248) fixes the state of an embedded document that is added back
into a collection after being removed. Also, documents overwritten by `set` method are now properly handled by orphan
removal.
 * [#1251](https://github.com/doctrine/mongodb-odm/pull/1251) ensures that references mapped with a `repositoryMethod` are
considered inverse-side relations.
 * [#1261](https://github.com/doctrine/mongodb-odm/pull/1261) adds a missing `--no-backup` option for the
`odm:generate:documents` command.

1.0.2 (2015-08-31)
------------------

All issues and pull requests in this release may be found under the
[1.0.2 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.2).

[#1223](https://github.com/doctrine/mongodb-odm/pull/1223) resolved a security
vulnerability related to file and directory creation in ODM. Doctrine Common and
ORM are also affected, so users are encouraged to update all libraries and
dependencies. The vulnerability has been assigned
[CVE-2015-5723](http://www.cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2015-5723)
and additional information on the issue may be found in
[this blog post](http://www.doctrine-project.org/2015/08/31/security_misconfiguration_vulnerability_in_various_doctrine_projects.html).

1.0.1 (2015-08-19)
------------------

All issues and pull requests in this release may be found under the
[1.0.1 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.1).

[#1211](https://github.com/doctrine/mongodb-odm/pull/1211) fixes a regression
where `Cursor::count()` returned the wrong count for the query by default.

1.0.0 (2015-08-18)
------------------

All issues and pull requests in this release may be found under the
[1.0.0 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.0).

#### Stricter mapping and slightly changed behaviour

For this stable release we introduced more checks while parsing of document
mappings as well as additional runtime sanity checks. This entailed some
modifications to UnitOfWork's previous behavior; however, it should ensure more
consistency with the database. If upgrading from a previous beta release, please
review the list of relevant changes below to ensure a smooth upgrade to 1.0.0:

 * [#1191](https://github.com/doctrine/mongodb-odm/pull/1191): ReferenceMany's
   `sort` option may only be used with inverse-side references or references
   using the `set`, `setArray`, `atomicSet`, and `atomicSetArray` strategies.
 * [#1190](https://github.com/doctrine/mongodb-odm/pull/1190): Identifiers using
   the `AUTO` strategy must be a valid ObjectId (either a `MongoId` object or a
   24-character hexadecimal string).
 * [#1177](https://github.com/doctrine/mongodb-odm/pull/1177): The `collection`
   field mapping no longer accepts a `strategy` property, which was previously
   unused.
 * [#1162](https://github.com/doctrine/mongodb-odm/pull/1162): Simple references
   must not target discriminated (also known as mixed type) documents.
 * [#1155](https://github.com/doctrine/mongodb-odm/pull/1155): Collection
   updates take place immediately after the owning document. Therefore,
   modifications done via `post*` events will no longer be saved to database.
   This change ensures that events are fired when they are meant to (as
   discussed in [#1145](https://github.com/doctrine/mongodb-odm/issues/1145)).
 * [#1147](https://github.com/doctrine/mongodb-odm/pull/1147): Identifier fields
   must always have an `id` field type; however, the field's data type (e.g.
   string, integer) may vary based on the strategy option.
 * [#1136](https://github.com/doctrine/mongodb-odm/pull/1136): Owning and inverse
   sides of reference relationship must specify `targetDocument` or `discriminatorMap`.
 * [#1130](https://github.com/doctrine/mongodb-odm/pull/1130): `EmbedMany` and
   `ReferenceMany` collections using `pushAll` and `addToSet` strategies are
   re-indexed after database synchronization to ensure consistency between the
   database and in-memory document.
 * [#1206](https://github.com/doctrine/mongodb-odm/pull/1206): ODM's Cursor
   class no longer extends `Doctrine\MongoDB\Cursor`. Instead, it implements the
   new `Doctrine\MongoDB\CursorInterface` interface, which was introduced in
   Doctrine MongoDB 1.2.0. Eager cursor behavior is now fully handled by
   Doctrine MongoDB, so the ODM EagerCursor class has been deprecated (to be
   removed in 2.0).

#### Parent association is available in `postLoad` events

[#1152](https://github.com/doctrine/mongodb-odm/pull/1152) makes it possible to
access an embedded document's parent association through the UnitOfWork. This
effectively allows you to set a reference to the parent document on an embedded
document field.

#### Performance optimizations

[#1112](https://github.com/doctrine/mongodb-odm/pull/1112) fixes a potential
performance and consistency issue by ensuring that reference-primed queries
always use an eager cursor.

[#1086](https://github.com/doctrine/mongodb-odm/pull/1086) improves `count()`
performance for uninitialized, inverse-side references by avoiding full document
hydration and calculating the count via the database command
(e.g. `MongoCursor::count()`).

[#1175](https://github.com/doctrine/mongodb-odm/pull/1175) optimized the
performance of `UnitOfWork::commit()`, which is good news for those working with
a large number of managed documents. As a technical detail, we reduced the
complexity from O(n^2) to to O(n), where n is number of documents scheduled for
synchronization. Additionally, we removed unneeded overhead for embedded
documents and did some general code cleanup in
[#782](https://github.com/doctrine/mongodb-odm/pull/782) and
[#1146](https://github.com/doctrine/mongodb-odm/pull/1146), respectively.

[#1155](https://github.com/doctrine/mongodb-odm/pull/1155) reduced the number of
queries issued to the database for document insertions, deletions, and clearing
of collections.

1.0.0-BETA13 (2015-05-21)
-------------------------

All issues and pull requests in this release may be found under the
[1.0.0-BETA13 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.0-BETA13).

#### `atomicSet` and `atomicSetArray` strategies for top-level collections

[#1096](https://github.com/doctrine/mongodb-odm/pull/1096) introduces two new
collection update strategies, `atomicSet` and `atomicSetArray`. Unlike existing
strategies (e.g. `pushAll` and `set`), which update collections in a separate
query after the parent document, the atomic strategy ensures that the collection
and its parent are updated in the same query. Any nested collections (within
embedded documents) will also be included in the atomic update, irrespective of
their update strategies.

Currently, atomic strategies may only be specified for collections mapped
directly in a document class (i.e. not collections within embedded documents).
This strategy may be especially useful for highly concurrent applications and/or
versioned document classes (see: [#1094](https://github.com/doctrine/mongodb-odm/pull/1094)).

#### Reference priming improvements

[#1068](https://github.com/doctrine/mongodb-odm/pull/1068) moves the handling of
primed references to the Cursor object, which allows ODM to take the skip and
limit options into account and avoid priming more references than are necessary.

[#970](https://github.com/doctrine/mongodb-odm/pull/970) now allows references
within embedded documents to be primed by fixing a previous parsing limitation
with dot syntax in field names.

#### New `defaultDiscriminatorValue` mapping

[#1072](https://github.com/doctrine/mongodb-odm/pull/1072) introduces a
`defaultDiscriminatorValue` mapping, which may be used to specify a default
discriminator value if a document or association has no discriminator set.

#### New `Integer` and `Bool` annotation aliases

[#1073](https://github.com/doctrine/mongodb-odm/pull/1073) introduces `Integer`
and `Bool` annotations, which are aliases of `Int` and `Boolean`, respectively.

#### Add millisecond precision to DateType

[#1063](https://github.com/doctrine/mongodb-odm/pull/1063) adds millisecond
precision to ODM's DateType class (note: although PHP supports microsecond
precision, dates in MongoDB are limited to millisecond precision). This should
now allow ODM to roundtrip dates from the database without a loss of precision.

#### New Hydrator generation modes

Previously, the `autoGenerateHydratorClasses` ODM configuration option was a
boolean denoting whether to always or never create Hydrator classes. As of
[#953](https://github.com/doctrine/mongodb-odm/pull/953), this option now
supports four modes:

 * `AUTOGENERATE_NEVER = 0` (same as `false`)
 * `AUTOGENERATE_ALWAYS = 1` (same as `true`)
 * `AUTOGENERATE_FILE_NOT_EXISTS = 2`
 * `AUTOGENERATE_EVAL = 3`

### Support for custom DocumentRepository factory

[#892](https://github.com/doctrine/mongodb-odm/pull/892) allows users to define
a custom repository class via the `defaultRepositoryClassName` configuration
option. Alternatively, a custom factory class may also be configured, which
allows users complete control over how repository classes are instantiated.

Custom repository and factory classes must implement
`Doctrine\Common\Persistence\ObjectRepository` and
`Doctrine\ODM\MongoDB\Repository\RepositoryFactory`, respectively.

1.0.0-BETA12 (2015-02-24)
-------------------------

All issues and pull requests in this release may be found under the
[1.0.0-BETA12 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.0-BETA12).

#### Allow filter parameters to be specified in Configuration

[#908](https://github.com/doctrine/mongodb-odm/pull/908) added an optional
second parameter to `Configuration::addFilter()`, which accepts an associative
array of parameters to set on the filter when it is enabled.

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

#### Simple references now require a target document

As of [#934](https://github.com/doctrine/mongodb-odm/pull/934/files), a
MappingException will be thrown if a target document is not specified for a
simple reference. Simple references always required a target document; this
change simply throws an error while parsing metadata instead of waiting for a
later error at runtime.

1.0.0-BETA11 (2014-06-06)
-------------------------

All issues and pull requests in this release may be found under the
[1.0.0-BETA11 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.0-BETA11).

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
[1.0.0-BETA10 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.0.0-BETA10).

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
