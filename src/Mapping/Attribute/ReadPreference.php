<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/** @final */
#[Attribute(Attribute::TARGET_CLASS)]
class ReadPreference implements MappingAttribute
{
    /** @var string */
    public $value;

    /** @var string[][]|null */
    public $tags;

    /** @param string[][]|null $tags */
    public function __construct(string $value, ?array $tags = null)
    {
        $this->value = $value;
        $this->tags  = $tags;
    }
}
