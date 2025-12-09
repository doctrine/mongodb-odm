<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Loads data from a different field if the original field is not set
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class AlsoLoad implements MappingAttribute
{
    /** @param string|string[] $value */
    public function __construct(public $value, public ?string $name = null)
    {
    }
}
