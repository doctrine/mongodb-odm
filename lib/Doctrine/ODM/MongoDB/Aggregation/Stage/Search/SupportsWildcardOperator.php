<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsWildcardOperator
{
    public function wildcard(): Wildcard;
}
