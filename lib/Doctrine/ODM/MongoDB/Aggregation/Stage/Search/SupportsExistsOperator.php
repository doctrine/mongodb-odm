<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsExistsOperator
{
    public function exists(string $path): Exists;
}
