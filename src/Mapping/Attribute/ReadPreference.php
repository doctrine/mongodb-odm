<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ReadPreference implements MappingAttribute
{
    /** @param string[][]|null $tags */
    public function __construct(public string $value, public ?array $tags = null)
    {
    }
}
