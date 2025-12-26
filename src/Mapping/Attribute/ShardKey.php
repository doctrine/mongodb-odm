<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ShardKey implements MappingAttribute
{
    /** @param string[] $keys */
    public function __construct(
        public array $keys = [],
        public ?bool $unique = null,
        public ?int $numInitialChunks = null,
    ) {
    }
}
