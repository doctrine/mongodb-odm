<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * @phpstan-type EmptyConfiguration array{}
 * @phpstan-type AllUsersConfiguration array{ allUsers: true }
 * @phpstan-type SpecificUsersConfiguration array{ users: array{ user: string, db: string } }
 * @phpstan-type ListLocalSessionsConfiguration EmptyConfiguration|AllUsersConfiguration|SpecificUsersConfiguration
 */
class ListLocalSessions extends Stage
{
    /**
     * @var ListLocalSessionsConfiguration
     */
    private array $config = [];

    /**
     * @param ListLocalSessionsConfiguration $config
     */
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
