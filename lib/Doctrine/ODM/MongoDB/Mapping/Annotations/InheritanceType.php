<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Specifies which inheritance type to use for a document
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class InheritanceType extends Annotation
{
}
