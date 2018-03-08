<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Marks a method as a postRemove lifecycle callback
 *
 * @Annotation
 */
final class PostRemove extends Annotation
{
}
