<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation as BaseAnnotation;

use function class_alias;

/**
 * Specifies a list of indexes for a document
 *
 * @deprecated class was deprecated in doctrine/mongodb-odm 2.2 and will be removed in 3.0. Specify all Index and UniqueIndex annotations on a class level.
 *
 * @Annotation
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Indexes extends BaseAnnotation implements Annotation
{
    /** @var AbstractIndex[]|AbstractIndex */
    public $value = [];
}

// @phpstan-ignore class.notFound
class_alias(Indexes::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\Indexes::class);
