<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specify a map of discriminator values and classes
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DiscriminatorMap implements MappingAttribute
{
    /** @param array<class-string> $value */
    public function __construct(public readonly array $value)
    {
    }
}
