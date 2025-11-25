<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

use function class_alias;

/**
 * Specifies a field to use for optimistic locking
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Version implements Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(Version::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\Version::class);
