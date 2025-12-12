<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field as FieldAttribute;

/**
 * Specifies a generic field mapping
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Field instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field extends FieldAttribute implements Annotation
{
}
