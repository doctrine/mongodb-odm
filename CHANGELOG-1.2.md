CHANGELOG for 1.2.x
===================

This changelog references the relevant changes done in 1.2 patch versions.

1.2.1 (2017-12-08)
------------------

All issues and pull requests in this release may be found under the
[1.2.1 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.2.1).

* [#1681](https://github.com/doctrine/mongodb-odm/pull/1681) hardens checks for storage strategy when mapping relationships
* [#1687](https://github.com/doctrine/mongodb-odm/pull/1687) fixes query preparation when using a reference as shard key
* [#1688](https://github.com/doctrine/mongodb-odm/pull/1688) fixes the `pushAll` collection strategy when running on MongoDB 3.6

1.2.0 (2017-10-24)
------------------

All issues and pull requests in this release may be found under the
[1.2 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.2).

* [#1448](https://github.com/doctrine/mongodb-odm/pull/1448) adds a builder for aggregation pipeline queries, similar to the query builder.
* [#1513](https://github.com/doctrine/mongodb-odm/pull/1513) adds a trait to avoid re-implementing `closureToPhp` in custom type classes.
* [#1518](https://github.com/doctrine/mongodb-odm/pull/1518) adds `updateOne` and `updateMany` methods to the query builder. 
* [#1519](https://github.com/doctrine/mongodb-odm/pull/1519) adds a command to validate mapping.
* [#1577](https://github.com/doctrine/mongodb-odm/pull/1577) allows priming fields in inverse references without specifying a repositoryMethod.
* [#1600](https://github.com/doctrine/mongodb-odm/pull/1600) adds an `AbstractRepositoryFactory` as base class when creating an own repository factory.
* [#1612](https://github.com/doctrine/mongodb-odm/pull/1612) adds support for immutable documents via the `readOnly` mapping option.
* [#1620](https://github.com/doctrine/mongodb-odm/pull/1620) adds support for specifying `readPreference` on a document level, replacing `slaveOkay`.
* [#1623](https://github.com/doctrine/mongodb-odm/pull/1623) adds a generic reference object as successor to `dbRef`.
* [#1654](https://github.com/doctrine/mongodb-odm/pull/1654) adds support for aggregation pipeline stages added in MongoDB 3.4.
* [#1661](https://github.com/doctrine/mongodb-odm/pull/1661) allows specifying a custom starting ID for `IncrementGenerator`.
