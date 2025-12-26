<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Use the specified discriminator for this class
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorValue implements MappingAttribute
{
    public function __construct(public readonly string $value)
    {
    }
}
