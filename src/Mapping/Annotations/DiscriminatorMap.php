<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorMap::class);

return;

/**
 * Specify a map of discriminator values and classes
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorMap instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DiscriminatorMap extends \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorMap
{
}
