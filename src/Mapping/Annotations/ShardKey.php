<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ShardKey as ShardKeyAttribute;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ShardKey instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ShardKey extends ShardKeyAttribute implements Annotation
{
}
