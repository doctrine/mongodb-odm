# UPGRADE FROM 2.x to 3.0

## Aggregation

The new `Doctrine\ODM\MongoDB\Aggregation\Builder::getAggregation()` method
returns an `Doctrine\ODM\MongoDB\Aggregation\Aggregation` instance, comparable
to the `Query` class.

The `Doctrine\ODM\MongoDB\Aggregation\Builder::execute()` method was removed.

## ID generators

The `Doctrine\ODM\MongoDB\Id\AbstractIdGenerator` class has been removed. Custom
ID generators must implement the `Doctrine\ODM\MongoDB\Id\IdGenerator`
interface.

## Metadata
The `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` class has been marked final and
will no longer be extendable.

The `boolean`, `integer`, and `int_id` mapping types have been removed. Use the
`bool`, `int`, and `int` types, respectively. These types behave exactly the
same.
