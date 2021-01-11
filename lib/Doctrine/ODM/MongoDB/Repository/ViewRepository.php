<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\Persistence\ObjectRepository;

interface ViewRepository extends ObjectRepository
{
    /**
     * Appends the aggregation pipeline to the given builder
     */
    public function createViewAggregation(Builder $builder): void;
}
