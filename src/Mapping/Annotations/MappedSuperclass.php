<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\MappedSuperclass instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass extends \Doctrine\ODM\MongoDB\Mapping\Attribute\MappedSuperclass implements Annotation
{
}
