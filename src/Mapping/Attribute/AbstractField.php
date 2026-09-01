<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

/** @internal */
abstract class AbstractField
{
    /** @param mixed[] $options */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly bool $nullable = false,
        public readonly array $options = [],
        public readonly ?string $strategy = null,
        public readonly bool $notSaved = false,
    ) {
    }
}
