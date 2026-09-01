<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Loads data from a different field if the original field is not set
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class AlsoLoad implements MappingAttribute
{
    /** @param string|string[] $value */
    public function __construct(public readonly string|array $value, public readonly ?string $name = null)
    {
    }
}
