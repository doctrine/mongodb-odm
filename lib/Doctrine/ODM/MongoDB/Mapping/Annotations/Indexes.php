<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation as BaseAnnotation;

/**
 * Specifies a list of indexes for a document
 *
 * @deprecated class was deprecated in doctrine/mongodb-odm 2.2 and will be removed in 3.0. Specify all Index and UniqueIndex annotations on a class level.
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Indexes extends BaseAnnotation implements Annotation
{
    /** @var AbstractIndex[]|AbstractIndex */
    public $value = [];
}
