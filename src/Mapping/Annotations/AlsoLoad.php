<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Loads data from a different field if the original field is not set
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\AlsoLoad instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class AlsoLoad extends \Doctrine\ODM\MongoDB\Mapping\Attribute\AlsoLoad implements Annotation
{
}
