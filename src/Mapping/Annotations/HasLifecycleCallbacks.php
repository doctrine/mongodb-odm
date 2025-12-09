<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks::class);

return;

/**
 * Must be set on a document final class to instruct Doctrine to check for lifecycle
 * callback annotations on public methods.
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class HasLifecycleCallbacks extends \Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks
{
}
