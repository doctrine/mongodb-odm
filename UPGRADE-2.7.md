# UPGRADE FROM 2.6 to 2.7

## Backward compatibility breaks

* `Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver` no longer extends
  `Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver`.

## doctrine/persistence

* MongoDB ODM 2.7 requires `doctrine/persistence` 3.2 or newer.

## `doctrine/annotations` is optional

ODM no longer requires `doctrine/annotations` to be installed. If you're using
annotations for mapping, you will need to install `doctrine/annotations`.
