<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface ScoredSearchOperator
{
    public function boostScore(?float $value = null, ?string $path = null, ?float $undefined = null): static;

    public function constantScore(float $value): static;
}
