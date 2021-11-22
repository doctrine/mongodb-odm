<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\ViewRepository;

/**
 * @template-extends DocumentRepository<UserName>
 * @template-implements ViewRepository<UserName>
 */
class UserNameRepository extends DocumentRepository implements ViewRepository
{
    public function createViewAggregation(Builder $builder): void
    {
        $builder->project()
            ->includeFields(['username']);
    }
}
