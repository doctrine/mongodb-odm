<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceMany as ReferenceManyAttribute;

/**
 * Specifies a one-to-many relationship to a different document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceMany instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceMany extends ReferenceManyAttribute implements Annotation
{
}
