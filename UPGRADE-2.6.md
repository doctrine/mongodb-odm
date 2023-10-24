# UPGRADE FROM to 2.6

## PHP requirements

* MongoDB ODM 2.6 requires PHP 8.1 or newer. If you're not running PHP 8.1 yet,
  it's recommended that you upgrade to PHP 8.1 before upgrading ODM.

## `Match` classes were removed

Minimal requirement of PHP 8.1 has rendered `Match` classes useless as one may
not use them for backward compatibility purposes as `match` is a reserved keyword.

Following classes were removed:
- `\Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup\Match`
- `\Doctrine\ODM\MongoDB\Aggregation\Stage\Match`
