<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specify a field name to store a discriminator value
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorField instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorField extends \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorField implements Annotation
{
}
