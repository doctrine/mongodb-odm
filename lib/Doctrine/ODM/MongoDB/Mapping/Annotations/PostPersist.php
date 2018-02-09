<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Marks a method as a postPersist lifecycle callback
 *
 * @Annotation
 */
final class PostPersist extends Annotation
{
}
