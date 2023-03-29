<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface CompoundSearchOperatorInterface extends SupportsCompoundableSearchOperators
{
    public function must(): Compound;

    public function mustNot(): Compound;

    public function should(?int $minimumShouldMatch = null): Compound;

    public function filter(): Compound;
}
