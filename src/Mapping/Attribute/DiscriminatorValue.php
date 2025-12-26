<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Use the specified discriminator for this class
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorValue implements MappingAttribute
{
    public function __construct(public string $value)
    {
    }
}
