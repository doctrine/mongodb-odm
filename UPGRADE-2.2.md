# UPGRADE FROM 2.1 to 2.2

## Aggregation

The new `Doctrine\ODM\MongoDB\Aggregation\Builder::getAggregation()` method
returns an `Doctrine\ODM\MongoDB\Aggregation\Aggregation` instance, comparable
to the `Query` class.

The `Doctrine\ODM\MongoDB\Aggregation\Builder::execute()` method was deprecated
and will be removed in ODM 3.0.

## Aannotations

### Base class

All ODM's annotations no longer extend the `Doctrine\Common\Annotations\Annotation`
class and may require passing arguments to their constructors.

### `@Indexes`

Using `@Index` annotation(s) on a class level is a preferred way for defining
indexes for documents. Using `@Index` in the `@Indexes` annotation or an `indexes`
property of other annotations was deprecated and will be removed in ODM 3.0.

### `@Inheritance`

The annotation class served no purpose and was removed.

