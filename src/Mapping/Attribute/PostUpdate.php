<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Marks a method as a postUpdate lifecycle callback
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PostUpdate implements Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(PostUpdate::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\PostUpdate::class);
