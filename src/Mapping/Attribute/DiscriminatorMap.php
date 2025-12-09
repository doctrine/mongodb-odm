<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specify a map of discriminator values and classes
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class DiscriminatorMap implements MappingAttribute
{
    /** @var array<class-string> */
    public $value;

    /** @param array<class-string> $value */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
