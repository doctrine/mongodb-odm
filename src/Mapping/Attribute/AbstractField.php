<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

/** @internal */
abstract readonly class AbstractField
{
    /** @param mixed[] $options */
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public bool $nullable = false,
        public array $options = [],
        public ?string $strategy = null,
        public bool $notSaved = false,
    ) {
    }
}
