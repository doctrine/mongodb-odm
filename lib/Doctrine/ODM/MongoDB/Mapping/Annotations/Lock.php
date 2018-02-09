<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Specifies a field to use for pessimistic locking
 *
 * @Annotation
 */
final class Lock extends Annotation
{
}
