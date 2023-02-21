# UPGRADE FROM 2.4 to 2.5

## PHP requirements

* MongoDB ODM 2.5 requires PHP 7.4 or newer. If you're not running PHP 7.4 yet,
  it's recommended that you upgrade to PHP 7.4 before upgrading ODM.

## `doctrine/collections` v2 compatibility

ODM supports doctrine/collections 2.x along 1.x. Please note that we added
`findFirst(...)` and `reduce(...)` methods to comply with the new `Collection`
interface. Make sure signatures of new methods comply with your own ones,
should you have implemented such methods before.
