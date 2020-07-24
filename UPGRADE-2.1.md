# UPGRADE FROM 2.0 to 2.1

## ID generators

The `Doctrine\ODM\MongoDB\Id\AbstractIdGenerator` class has been deprecated.
Custom ID generators must implement the `Doctrine\ODM\MongoDB\Id\IdGenerator`
interface.

## Metadata

The `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` class has been marked final and
will no longer be extendable in 3.0.

The `boolean`, `integer`, and `int_id` mapping types have been deprecated. Use
the `bool`, `int`, and `int` types, respectively. These types behave exactly the
same.
