<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id as IdAttribute;

/**
 * Special field mapping to map document identifiers
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Id instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id extends IdAttribute implements Annotation
{
}
