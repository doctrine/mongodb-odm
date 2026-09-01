<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

/** @internal */
abstract class AbstractIndex
{
    /**
     * @param string[]             $keys
     * @param mixed[]              $options
     * @param array<string, mixed> $partialFilterExpression
     */
    public function __construct(
        public readonly array $keys = [],
        public readonly ?string $name = null,
        public readonly ?bool $background = null,
        public readonly ?int $expireAfterSeconds = null,
        public readonly int|string|null $order = null,
        public readonly bool $unique = false,
        public readonly bool $sparse = false,
        public readonly array $options = [],
        public readonly array $partialFilterExpression = [],
    ) {
    }
}
