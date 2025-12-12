<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Version as VersionAttribute;

/**
 * Specifies a field to use for optimistic locking
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Version instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Version extends VersionAttribute implements Annotation
{
}
