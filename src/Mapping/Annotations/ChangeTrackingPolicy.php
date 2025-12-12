<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ChangeTrackingPolicy as ChangeTrackingPolicyAttribute;

/**
 * Specifies the change tracking policy for a document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ChangeTrackingPolicy instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ChangeTrackingPolicy extends ChangeTrackingPolicyAttribute implements Annotation
{
}
