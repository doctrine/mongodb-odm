<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\InheritanceType as InheritanceTypeAttribute;

/**
 * Specifies which inheritance type to use for a document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\InheritanceType instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class InheritanceType extends InheritanceTypeAttribute implements Annotation
{
}
