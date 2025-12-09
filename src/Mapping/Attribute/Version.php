<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a field to use for optimistic locking
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Version implements MappingAttribute
{
}
