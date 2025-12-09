<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Validation instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Validation extends \Doctrine\ODM\MongoDB\Mapping\Attribute\Validation implements Annotation
{
}
