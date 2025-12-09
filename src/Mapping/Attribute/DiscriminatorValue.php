<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Use the specified discriminator for this class
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DiscriminatorValue implements MappingAttribute
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
