<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

/** @internal */
abstract readonly class AbstractIndex
{
    /**
     * @param string[]             $keys
     * @param mixed[]              $options
     * @param array<string, mixed> $partialFilterExpression
     */
    public function __construct(
        public array $keys = [],
        public ?string $name = null,
        public ?bool $background = null,
        public ?int $expireAfterSeconds = null,
        public int|string|null $order = null,
        public bool $unique = false,
        public bool $sparse = false,
        public array $options = [],
        public array $partialFilterExpression = [],
    ) {
    }
}
