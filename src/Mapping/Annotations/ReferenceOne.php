<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne as ReferenceOneAttribute;

/**
 * Specifies a one-to-one relationship to a different document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceOne extends ReferenceOneAttribute implements Annotation
{
}
