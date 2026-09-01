<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ShardKey implements MappingAttribute
{
    /** @param string[] $keys */
    public function __construct(
        public readonly array $keys = [],
        public readonly ?bool $unique = null,
        public readonly ?int $numInitialChunks = null,
    ) {
    }
}
