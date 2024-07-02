<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\APM;

use LogicException;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use MongoDB\Driver\Server;
use Throwable;

final class Command
{
    private CommandStartedEvent $startedEvent;
    private CommandSucceededEvent|CommandFailedEvent $finishedEvent;

    private function __construct()
    {
    }

    public static function createForSucceededCommand(CommandStartedEvent $startedEvent, CommandSucceededEvent $succeededEvent): self
    {
        self::checkRequestIds($startedEvent, $succeededEvent);

        $instance                = new self();
        $instance->startedEvent  = $startedEvent;
        $instance->finishedEvent = $succeededEvent;

        return $instance;
    }

    public static function createForFailedCommand(CommandStartedEvent $startedEvent, CommandFailedEvent $failedEvent): self
    {
        self::checkRequestIds($startedEvent, $failedEvent);

        $instance                = new self();
        $instance->startedEvent  = $startedEvent;
        $instance->finishedEvent = $failedEvent;

        return $instance;
    }

    /** @param CommandSucceededEvent|CommandFailedEvent $finishedEvent */
    private static function checkRequestIds(CommandStartedEvent $startedEvent, $finishedEvent): void
    {
        if ($startedEvent->getRequestId() !== $finishedEvent->getRequestId()) {
            throw new LogicException('Cannot create APM command for events with different request IDs');
        }
    }

    public function getCommandName(): string
    {
        return $this->startedEvent->getCommandName();
    }

    public function getCommand(): object
    {
        return $this->startedEvent->getCommand();
    }

    public function getDurationMicros(): int
    {
        return $this->finishedEvent->getDurationMicros();
    }

    public function getRequestId(): string
    {
        return $this->startedEvent->getRequestId();
    }

    public function getServer(): Server
    {
        return $this->finishedEvent->getServer();
    }

    public function getReply(): object
    {
        return $this->finishedEvent->getReply();
    }

    public function getError(): ?Throwable
    {
        return $this->finishedEvent instanceof CommandFailedEvent ? $this->finishedEvent->getError() : null;
    }
}
