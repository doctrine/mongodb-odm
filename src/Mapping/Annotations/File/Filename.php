<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Annotation;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\File\Filename instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Filename extends \Doctrine\ODM\MongoDB\Mapping\Attribute\File\Filename implements Annotation
{
}
