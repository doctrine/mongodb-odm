<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorField::class);

return;

/**
 * Specify a field name to store a discriminator value
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorField instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorField extends \Doctrine\ODM\MongoDB\Mapping\Attribute\DiscriminatorField
{
}
