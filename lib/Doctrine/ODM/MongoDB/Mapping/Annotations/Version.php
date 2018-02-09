<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Specifies a field to use for optimistic locking
 *
 * @Annotation
 */
final class Version extends Annotation
{
}
