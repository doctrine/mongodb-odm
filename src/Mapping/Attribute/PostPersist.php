<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Marks a method as a postPersist lifecycle callback
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PostPersist implements Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(PostPersist::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\PostPersist::class);
