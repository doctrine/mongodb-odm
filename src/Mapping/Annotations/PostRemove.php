<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Marks a method as a postRemove lifecycle callback
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\PostRemove instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PostRemove extends \Doctrine\ODM\MongoDB\Mapping\Attribute\PostRemove implements Annotation
{
}
