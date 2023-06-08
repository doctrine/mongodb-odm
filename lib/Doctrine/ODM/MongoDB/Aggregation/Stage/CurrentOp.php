<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

class CurrentOp extends Stage
{
    private bool $allUsers        = false;
    private bool $idleConnections = false;
    private bool $idleCursors     = false;
    private bool $idleSessions    = true;
    private bool $localOps        = false;
    private bool $backtrace       = false;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    public function reportAllUsers(bool $allUsers = true): self
    {
        $this->allUsers = $allUsers;

        return $this;
    }

    public function reportIdleConnections(bool $idleConnections = true): self
    {
        $this->idleConnections = $idleConnections;

        return $this;
    }

    public function reportIdleCursors(bool $idleCursors = true): self
    {
        $this->idleCursors = $idleCursors;

        return $this;
    }

    public function reportIdleSessions(bool $idleSessions = true): self
    {
        $this->idleSessions = $idleSessions;

        return $this;
    }

    public function setLocalOps(bool $localOps = true): self
    {
        $this->localOps = $localOps;

        return $this;
    }

    public function setBacktrace(bool $backtrace = true): self
    {
        $this->backtrace = $backtrace;

        return $this;
    }

    public function getExpression(): array
    {
        return [
            '$currentOp' => [
                'allUsers' => $this->allUsers,
                'idleConnections' => $this->idleConnections,
                'idleCursors' => $this->idleCursors,
                'idleSessions' => $this->idleSessions,
                'localOps' => $this->localOps,
                'backtrace' => $this->backtrace,
            ],
        ];
    }
}
