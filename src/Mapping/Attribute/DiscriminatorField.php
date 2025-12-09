<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specify a field name to store a discriminator value
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DiscriminatorField implements MappingAttribute
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
