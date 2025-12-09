<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

use function class_alias;

/**
 * Must be set on a document class to instruct Doctrine to check for lifecycle
 * callback annotations on public methods.
 *
 * @Annotation
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasLifecycleCallbacks implements Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(HasLifecycleCallbacks::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks::class);
