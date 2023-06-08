<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

class ListLocalSessions extends Stage
{
    private array $config = [];

    public function __construct(Builder $builder, array $config = [])
    {
        parent::__construct($builder);
        $this->config = $config;
    }

    public function getExpression(): array
    {
        return ['$listLocalSessions' => $this->config];
    }
}
