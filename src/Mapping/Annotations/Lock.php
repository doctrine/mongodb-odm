<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\Lock::class);

return;

/**
 * Specifies a field to use for pessimistic locking
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Lock instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Lock extends \Doctrine\ODM\MongoDB\Mapping\Attribute\Lock
{
}
