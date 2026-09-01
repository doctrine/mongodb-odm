<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a default discriminator value to be used when the discriminator
 * field is not set in a document
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DefaultDiscriminatorValue implements MappingAttribute
{
    public function __construct(public readonly string $value)
    {
    }
}
