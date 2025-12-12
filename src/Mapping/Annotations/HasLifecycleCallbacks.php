<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks as HasLifecycleCallbacksAttribute;

/**
 * Must be set on a document class to instruct Doctrine to check for lifecycle
 * callback annotations on public methods.
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class HasLifecycleCallbacks extends HasLifecycleCallbacksAttribute implements Annotation
{
}
