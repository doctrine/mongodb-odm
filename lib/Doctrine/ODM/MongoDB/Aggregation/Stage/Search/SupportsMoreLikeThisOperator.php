<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsMoreLikeThisOperator
{
    /** @param array<string, mixed>|object $documents */
    public function moreLikeThis(...$documents): MoreLikeThis;
}
