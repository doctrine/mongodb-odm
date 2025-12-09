<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specifies the change tracking policy for a document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ChangeTrackingPolicy instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ChangeTrackingPolicy extends \Doctrine\ODM\MongoDB\Mapping\Attribute\ChangeTrackingPolicy implements Annotation
{
}
