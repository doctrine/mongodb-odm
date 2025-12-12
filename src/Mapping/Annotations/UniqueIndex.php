<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\UniqueIndex as UniqueIndexAttribute;

/**
 * Specifies a unique index on a field
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\UniqueIndex instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class UniqueIndex extends UniqueIndexAttribute implements Annotation
{
}
