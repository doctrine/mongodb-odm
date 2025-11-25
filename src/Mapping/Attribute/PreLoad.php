<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Marks a method as a preLoad lifecycle callback
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PreLoad implements Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(PreLoad::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\PreLoad::class);
