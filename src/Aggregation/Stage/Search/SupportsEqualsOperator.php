<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsEqualsOperator
{
    public function equals(string $path = '', mixed $value = null): Equals;
}
