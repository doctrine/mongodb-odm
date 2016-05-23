CHANGELOG for 1.1.x
===================

This changelog references the relevant changes done in 1.1 minor versions. If upgrading from
1.0.x branch, please review
[Upgrade Path](https://github.com/doctrine/mongodb-odm/blob/master/CHANGELOG-1.1.md#upgrade-path).

1.1.0
-----

All issues and pull requests in this release may be found under the
[1.1.0 milestone](https://github.com/doctrine/mongodb-odm/issues?q=milestone%3A1.1.0).

#### Sharding support

https://github.com/doctrine/mongodb-odm/pull/1385

#### Custom Collections

https://github.com/doctrine/mongodb-odm/pull/1219

#### Event is dispatched for missing referenced documents

https://github.com/doctrine/mongodb-odm/pull/1336

#### Partial indexes

https://github.com/doctrine/mongodb-odm/pull/1303

#### More powerful lifecycle callbacks

https://github.com/doctrine/mongodb-odm/pull/1222

#### Ease using objects as search criterion

https://github.com/doctrine/mongodb-odm/pull/1363
https://github.com/doctrine/mongodb-odm/pull/1333

#### Read-Only mode for fetching documents

https://github.com/doctrine/mongodb-odm/pull/1403

#### EmbedMany and ReferenceMany fields included in the change set

https://github.com/doctrine/mongodb-odm/pull/1339

PHP 7 compatibility
-------------------

@todo

Upgrade Path
------------

#### PHP requirement changed

PHP 5.3, 5.4 and 5.5 support has been dropped due to their [end of life](http://php.net/eol.php)
(or getting close to it in case of 5.5).

#### preLoad lifecycle callback signature change

`preLoad` lifecycle callback now receives `PreLoadEventArgs` object instead of an array of data
passed by reference. For reasoning why the change was made please see relevant pull request
([#1222](https://github.com/doctrine/mongodb-odm/pull/1222)).

Deprecations
------------

#### `@Field` preferred way of mapping field

Due to PHP 7 reserving keywords such as `int`, `string`, `bool` and `float` their respective
field annotations are no longer valid. To avoid having large inconsistencies
[#1318](https://github.com/doctrine/mongodb-odm/pull/1318) deprecates all annotations which
only purpose was setting mapped field's type. Deprecated classes will be removed in version 2.0.

#### `@Increment` superseded by storage strategies

[#1352](https://github.com/doctrine/mongodb-odm/pull/1352) deprecates `@Increment` field type
in favour of more robust `strategy` field option. To learn more about storage strategies
please see relevant chapter in [documentation](http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/storage-strategies.html).
`increment` field type will be removed in version 2.0.

1.0.x End-of-Life
-----------------

ODM 1.1 drops older PHP versions which may be problematic for some users. Although we strongly
recommend running latest PHP we understand this may not be easy change therefore we will still
support ODM 1.0.x and release bugfix versions for **6 months** from now on.
