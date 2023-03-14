<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

class Set extends AddFields
{
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->isSet();
    }
}
