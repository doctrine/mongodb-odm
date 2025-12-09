<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Specifies a field to use for pessimistic locking
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Lock instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Lock extends \Doctrine\ODM\MongoDB\Mapping\Attribute\Lock implements Annotation
{
}
