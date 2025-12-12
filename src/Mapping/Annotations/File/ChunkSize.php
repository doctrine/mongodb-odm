<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Annotation;
use Doctrine\ODM\MongoDB\Mapping\Attribute\File\ChunkSize as ChunkSizeAttribute;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\File\ChunkSize instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ChunkSize extends ChunkSizeAttribute implements Annotation
{
}
