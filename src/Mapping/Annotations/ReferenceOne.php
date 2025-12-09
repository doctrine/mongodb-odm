<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne::class);

return;

/**
 * Specifies a one-to-one relationship to a different document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceOne extends \Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne
{
}
