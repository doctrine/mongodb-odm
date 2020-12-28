# UPGRADE FROM 2.1 to 2.2

## Aggregation

The new `Doctrine\ODM\MongoDB\Aggregation\Builder::getAggregation()` method
returns an `Doctrine\ODM\MongoDB\Aggregation\Aggregation` instance, comparable
to the `Query` class.

The `Doctrine\ODM\MongoDB\Aggregation\Builder::execute()` method was deprecated
and will be removed in ODM 3.0.

The `Doctrine\ODM\MongoDB\Aggregation\Stage\Match` and 
`Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup\Match` classes were
deprecated and replaced by `Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage`
and `Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup\MatchStage`
respectively. This change was necessary due to `match` being a reserved keyword
in PHP 8. You must replace any usage of this class before you migrate to PHP 8.

## Document indexes (annotations)

Using `@Index` annotation(s) on a class level is a preferred way for defining
indexes for documents. Using `@Index` in the `@Indexes` annotation or an `indexes`
property of other annotations was deprecated and will be removed in ODM 3.0.

