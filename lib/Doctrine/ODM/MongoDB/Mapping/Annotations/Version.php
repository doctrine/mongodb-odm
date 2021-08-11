<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Specifies a field to use for optimistic locking
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Version implements Annotation
{
}
