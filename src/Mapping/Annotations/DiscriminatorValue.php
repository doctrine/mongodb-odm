<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorValue as DiscriminatorValueAttribute;

/**
 * Use the specified discriminator for this class
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorValue instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorValue extends DiscriminatorValueAttribute implements Annotation
{
}
