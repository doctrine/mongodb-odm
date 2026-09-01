<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specify a field name to store a discriminator value
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorField implements MappingAttribute
{
    public function __construct(public readonly string $value)
    {
    }
}
