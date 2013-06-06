CHANGELOG for 1.0.x
===================

This changelog references the relevant changes done in 1.0 minor versions.

To get the diff for a specific change, go to
https://github.com/doctrine/mongodb-odm/commit/XXX where XXX is
the commit hash. To get the diff between two versions, go to
https://github.com/doctrine/mongodb-odm/compare/VERSION1...VERSION2

To generate a changelog summary since the last version, run
`git log --no-merges --oneline LASTVERSION...HEAD`

* 1.0.0-BETA9 (2013-06-06)

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
