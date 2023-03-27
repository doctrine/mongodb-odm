<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsCompoundOperator
{
    public function compound(): Compound;
}
