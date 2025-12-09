<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\DefaultDiscriminatorValue::class);

return;

/**
 * Specifies a default discriminator value to be used when the discriminator
 * field is not set in a document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\DefaultDiscriminatorValue instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DefaultDiscriminatorValue extends \Doctrine\ODM\MongoDB\Mapping\Attribute\DefaultDiscriminatorValue
{
}
