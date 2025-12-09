<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specify a map of discriminator values and classes
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorMap instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DiscriminatorMap extends \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorMap implements Annotation
{
}
