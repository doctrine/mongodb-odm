<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Must be set on a document class to instruct Doctrine to check for lifecycle
 * callback annotations on public methods.
 *
 * @Annotation
 */
final class HasLifecycleCallbacks extends Annotation
{
}
