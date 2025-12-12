<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReadPreference as ReadPreferenceAttribute;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ReadPreference instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ReadPreference extends ReadPreferenceAttribute implements Annotation
{
}
