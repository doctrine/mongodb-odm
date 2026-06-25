<?php

declare(strict_types=1);

namespace Documents\Functional;

class ValueObjectChild
{
    public function __construct(
        public readonly int $prop1,
        public readonly int $prop2,
    ) {
    }
}
